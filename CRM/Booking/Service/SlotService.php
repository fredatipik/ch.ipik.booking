<?php
namespace CRM\Booking\Service;

use CRM\Booking\BAO\Availability;
use CRM\Booking\BAO\Therapist;
use CRM\Booking\BAO\AppointmentType;
use CRM\Booking\BAO\Workday;
use CRM\Booking\Service\CalendarProvider\CalendarProviderInterface;
use CRM\Booking\Service\CalendarProvider\NullCalendarProvider;
use CRM\Booking\Service\CalendarProvider\InformaniakCalDavProvider;
use CRM\Booking\Utils;

/**
 * SlotService — calcul des créneaux disponibles.
 *
 * Un créneau est proposé si toutes ces conditions sont réunies :
 *   - l'intervenant travaille ce jour-là, sur la plage horaire concernée
 *   - le créneau n'est pas déjà pris par un rendez-vous (+ tampon)
 *   - son agenda personnel est libre
 *   - le local où il travaille ce jour-là est libre, le cas échéant
 *
 * Deux modes de disponibilité coexistent :
 *   weekly   — horaires hebdomadaires réguliers + exceptions
 *   workdays — journées déclarées une par une, chacune avec ses horaires
 *
 * Les appels CalDAV sont mis en cache : un seul par agenda et par période.
 */
class SlotService {

  private CalendarProviderInterface $calendarProvider;
  private int $defaultInterval;

  /** Créneaux occupés, par agenda et par période */
  private array $busyCache = [];

  public function __construct(?CalendarProviderInterface $calendarProvider = NULL) {
    if ($calendarProvider === NULL) {
      try {
        $provider = new InformaniakCalDavProvider();
        $this->calendarProvider = $provider->isAvailable() ? $provider : new NullCalendarProvider();
      }
      catch (\Throwable $e) {
        Utils::logError('Initialisation du fournisseur de calendrier', ['error' => $e->getMessage()]);
        $this->calendarProvider = new NullCalendarProvider();
      }
    }
    else {
      $this->calendarProvider = $calendarProvider;
    }
    $this->defaultInterval = max(5, (int) Utils::getSetting('slot_interval_minutes', 15));
  }

  // -------------------------------------------------------------------------
  // API publique
  // -------------------------------------------------------------------------

  /**
   * Créneaux disponibles pour un type de rendez-vous sur une période.
   * Agrège tous les intervenants éligibles, sans révéler lequel est libre.
   *
   * @return string[] Datetimes 'Y-m-d H:i:s', triés, sans doublon.
   */
  public function getAvailableSlots(int $appointmentTypeId, string $dateFrom, string $dateTo): array {
    $type       = AppointmentType::getById($appointmentTypeId);
    $therapists = Therapist::getByAppointmentType($appointmentTypeId);

    if (!$type || empty($therapists)) {
      return [];
    }

    $duration = (int) $type['duration_minutes'];
    $interval = $this->intervalForType($type);
    $now      = new \DateTime();
    $allSlots = [];

    // Précharger, par intervenant : rendez-vous, agenda personnel, journées
    $context = [];
    foreach ($therapists as $t) {
      $tid = (int) $t['id'];
      $context[$tid] = [
        'therapist' => $t,
        'mode'      => $this->modeFor($t),
        'existing'  => $this->existingAppointments($tid, $dateFrom, $dateTo, (int) $t['buffer_minutes']),
        'busy'      => $this->busySlots($t['calendar_url'] ?? '', $dateFrom, $dateTo),
        'workdays'  => Workday::getRange($tid, $dateFrom, $dateTo),
      ];
    }

    $current = new \DateTime($dateFrom);
    $end     = (new \DateTime($dateTo))->modify('+1 day');

    while ($current < $end) {
      $dateStr = $current->format('Y-m-d');

      foreach ($context as $tid => $ctx) {
        $t = $ctx['therapist'];

        // Horizon de réservation
        $maxDate = (new \DateTime())->modify('+' . (int) $t['max_advance_days'] . ' days');
        if ($current > $maxDate) {
          continue;
        }

        [$ranges, $locationId] = $this->dayRanges($tid, $dateStr, $ctx);
        if (empty($ranges)) {
          continue;
        }

        // Agenda du local, chargé à la demande et mis en cache
        $locationBusy = $locationId
          ? $this->locationBusySlots($locationId, $dateFrom, $dateTo)
          : [];

        foreach ($ranges as $range) {
          $slots = $this->generateSlots(
            $dateStr, $range['start'], $range['end'],
            $duration, $interval,
            [$ctx['existing'], $ctx['busy'], $locationBusy],
            $now
          );
          foreach ($slots as $slot) {
            $allSlots[$slot] = TRUE;
          }
        }
      }

      $current->modify('+1 day');
    }

    $result = array_keys($allSlots);
    sort($result);
    return $result;
  }

  /**
   * Un intervenant précis est-il libre sur ce créneau ?
   * Vérification finale, au moment de la réservation.
   */
  public function isSlotAvailable(int $appointmentTypeId, int $therapistId, string $startDatetime): bool {
    $type = AppointmentType::getById($appointmentTypeId);
    if (!$type) return FALSE;

    $therapist = Therapist::getById($therapistId);
    if (!$therapist) return FALSE;

    $start    = new \DateTime($startDatetime);
    $dateStr  = $start->format('Y-m-d');
    $duration = (int) $type['duration_minutes'];
    $interval = $this->intervalForType($type);

    $ctx = [
      'therapist' => $therapist,
      'mode'      => $this->modeFor($therapist),
      'workdays'  => Workday::getRange($therapistId, $dateStr, $dateStr),
    ];

    [$ranges, $locationId] = $this->dayRanges($therapistId, $dateStr, $ctx);
    if (empty($ranges)) return FALSE;

    $existing     = $this->existingAppointments($therapistId, $dateStr, $dateStr, (int) $therapist['buffer_minutes']);
    $busy         = $this->busySlots($therapist['calendar_url'] ?? '', $dateStr, $dateStr);
    $locationBusy = $locationId ? $this->locationBusySlots($locationId, $dateStr, $dateStr) : [];

    $target = $start->format('Y-m-d H:i:s');
    $now    = new \DateTime();

    foreach ($ranges as $range) {
      $slots = $this->generateSlots(
        $dateStr, $range['start'], $range['end'],
        $duration, $interval,
        [$existing, $busy, $locationBusy],
        $now
      );
      if (in_array($target, $slots, TRUE)) {
        return TRUE;
      }
    }
    return FALSE;
  }

  /**
   * Local où l'intervenant travaille à cette date, NULL sinon.
   * Utilisé à la création du rendez-vous pour savoir où poser l'événement.
   */
  public function locationForDate(int $therapistId, string $date): ?int {
    $therapist = Therapist::getById($therapistId);
    if (!$therapist) return NULL;

    // La journée déclarée fait foi lorsqu'elle existe
    if ($this->modeFor($therapist) === 'workdays') {
      $workday = Workday::getForDate($therapistId, $date);
      if ($workday) {
        return !empty($workday['location_id']) ? (int) $workday['location_id'] : NULL;
      }
    }

    // Sinon, le local habituel de l'intervenant·e. Ce repli couvre le mode
    // hebdomadaire et les rendez-vous posés hors des journées déclarées.
    return !empty($therapist['default_location_id'])
      ? (int) $therapist['default_location_id']
      : NULL;
  }

  // -------------------------------------------------------------------------
  // Disponibilité du jour
  // -------------------------------------------------------------------------

  /**
   * Plages horaires d'un intervenant pour une date, et local associé.
   *
   * @return array{0: array, 1: ?int} [plages, id du local]
   */
  private function dayRanges(int $therapistId, string $date, array $ctx): array {
    if (($ctx['mode'] ?? 'weekly') === 'workdays') {
      $workday = $ctx['workdays'][$date] ?? NULL;
      if (!$workday) {
        return [[], NULL];
      }
      $locationId = !empty($workday['location_id'])
        ? (int) $workday['location_id']
        : NULL;

      return [
        [['start' => $workday['start_time'], 'end' => $workday['end_time']]],
        $locationId,
      ];
    }

    // Mode hebdomadaire : horaires réguliers, congés et créneaux exceptionnels.
    // Le local habituel s'applique, faute de journée pour en désigner un.
    $default = !empty($ctx['therapist']['default_location_id'])
      ? (int) $ctx['therapist']['default_location_id']
      : NULL;

    return [Availability::getSlotsForDate($therapistId, $date), $default];
  }

  /**
   * Mode de disponibilité d'un intervenant : le sien, sinon le réglage global.
   */
  private function modeFor(array $therapist): string {
    $own = trim((string) ($therapist['availability_mode'] ?? ''));
    if ($own === 'weekly' || $own === 'workdays') {
      return $own;
    }
    $global = (string) Utils::getSetting('availability_mode', 'weekly');
    return $global === 'workdays' ? 'workdays' : 'weekly';
  }

  // -------------------------------------------------------------------------
  // Génération des créneaux
  // -------------------------------------------------------------------------

  /**
   * Créneaux libres dans une plage, en écartant toutes les périodes occupées.
   *
   * @param array $busySets Liste de jeux de plages occupées à écarter.
   */
  private function generateSlots(
    string    $date,
    string    $rangeStart,
    string    $rangeEnd,
    int       $durationMinutes,
    int       $intervalMinutes,
    array     $busySets,
    \DateTime $now
  ): array {
    $slots   = [];
    $current = new \DateTime($date . ' ' . $rangeStart);
    $end     = new \DateTime($date . ' ' . $rangeEnd);

    while (TRUE) {
      $slotEnd = (clone $current)->modify("+{$durationMinutes} minutes");
      if ($slotEnd > $end) break;

      if ($current > $now && !$this->isBusy($current, $slotEnd, $busySets)) {
        $slots[] = $current->format('Y-m-d H:i:s');
      }

      $current->modify("+{$intervalMinutes} minutes");
    }

    return $slots;
  }

  /**
   * Le créneau chevauche-t-il l'une des plages occupées ?
   */
  private function isBusy(\DateTime $start, \DateTime $end, array $busySets): bool {
    foreach ($busySets as $ranges) {
      foreach ($ranges as $busy) {
        $busyStart = new \DateTime($busy['start']);
        $busyEnd   = new \DateTime($busy['end']);
        if ($start < $busyEnd && $end > $busyStart) {
          return TRUE;
        }
      }
    }
    return FALSE;
  }

  // -------------------------------------------------------------------------
  // Sources d'occupation
  // -------------------------------------------------------------------------

  /**
   * Rendez-vous déjà pris par un intervenant, tampon inclus.
   */
  private function existingAppointments(
    int    $therapistId,
    string $dateFrom,
    string $dateTo,
    int    $bufferMinutes
  ): array {
    $dao = \CRM_Core_DAO::executeQuery(
      "SELECT start_datetime, end_datetime
       FROM civicrm_booking_appointment
       WHERE therapist_id = %1
         AND DATE(start_datetime) BETWEEN %2 AND %3
         AND status != 'cancelled'",
      [
        1 => [$therapistId, 'Integer'],
        2 => [$dateFrom, 'String'],
        3 => [$dateTo, 'String'],
      ]
    );

    $rows = [];
    while ($dao->fetch()) {
      $start = new \DateTime($dao->start_datetime);
      $end   = new \DateTime($dao->end_datetime);
      if ($bufferMinutes > 0) {
        $start->modify("-{$bufferMinutes} minutes");
        $end->modify("+{$bufferMinutes} minutes");
      }
      $rows[] = [
        'start' => $start->format('Y-m-d H:i:s'),
        'end'   => $end->format('Y-m-d H:i:s'),
      ];
    }
    return $rows;
  }

  /**
   * Occupations d'un agenda CalDAV, mises en cache par URL et période.
   */
  private function busySlots(string $calendarUrl, string $dateFrom, string $dateTo): array {
    $url = preg_replace('/\?.*$/', '', trim($calendarUrl));
    if ($url === '' || !$this->calendarProvider->isAvailable()) {
      return [];
    }

    $key = md5($url . '|' . $dateFrom . '|' . $dateTo);
    if (isset($this->busyCache[$key])) {
      return $this->busyCache[$key];
    }

    try {
      $slots = $this->calendarProvider->getBusySlotsForUrl(
        $url,
        $dateFrom . ' 00:00:00',
        $dateTo . ' 23:59:59'
      );
    }
    catch (\Throwable $e) {
      Utils::logError('Lecture des occupations CalDAV', [
        'url'   => $url,
        'error' => $e->getMessage(),
      ]);
      $slots = [];
    }

    $this->busyCache[$key] = $slots;
    return $slots;
  }

  /**
   * Occupations de l'agenda d'un local.
   */
  private function locationBusySlots(int $locationId, string $dateFrom, string $dateTo): array {
    $url = \CRM\Booking\BAO\Location::getCalendarUrl($locationId);
    return $url ? $this->busySlots($url, $dateFrom, $dateTo) : [];
  }

  /**
   * Intervalle entre créneaux : propre au type, sinon réglage global.
   */
  private function intervalForType(array $type): int {
    $own = (int) ($type['slot_interval_minutes'] ?? 0);
    return $own > 0 ? $own : $this->defaultInterval;
  }
}
