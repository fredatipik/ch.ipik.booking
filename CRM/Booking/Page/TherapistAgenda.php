<?php
namespace CRM\Booking\Page;

use CRM\Booking\BAO\Availability;
use CRM\Booking\BAO\Therapist;
use CRM\Booking\Utils;

/**
 * Page TherapistAgenda — onglet « Agenda » de la fiche contact d'un intervenant·e.
 *
 * Présente les rendez-vous à venir sous forme de liste chronologique groupée
 * par mois, paginée, suivie des disponibilités récurrentes et des exceptions.
 */
class TherapistAgenda extends \CRM_Core_Page {

  private const PER_PAGE = 20;

  public function run(): void {
    $contactId = (int) \CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    $therapist = Therapist::getByContactId($contactId);

    if (!$therapist) {
      \CRM_Core_Error::statusBounce(ts('Ce contact n\'est pas un intervenant·e enregistré.'));
    }
    $therapistId = (int) $therapist['id'];

    Utils::setBreadCrumb([[
      'title' => ts('Intervenant·es'),
      'url'   => \CRM_Utils_System::url('civicrm/booking/therapists', 'reset=1'),
    ]]);

    $showPast = (bool) \CRM_Utils_Request::retrieve('past', 'Boolean', $this, FALSE, FALSE);
    $page     = max(1, (int) \CRM_Utils_Request::retrieve('page', 'Positive', $this, FALSE, 1));

    $total  = $this->countAppointments($therapistId, $showPast);
    $pages  = max(1, (int) ceil($total / self::PER_PAGE));
    $page   = min($page, $pages);
    $offset = ($page - 1) * self::PER_PAGE;

    $appointments = $this->fetchAppointments($therapistId, $showPast, $offset);

    $this->assign('therapist',      $therapist);
    $this->assign('months',         $this->groupByMonth($appointments));
    $this->assign('total',          $total);
    $this->assign('currentPage',    $page);
    $this->assign('pages',          $pages);
    $this->assign('showPast',       $showPast);
    $this->assign('pageLinks',      $this->buildPageLinks($contactId, $showPast, $pages, $page));
    $this->assign('toggleURL',      \CRM_Utils_System::url(
      'civicrm/booking/therapist-agenda',
      ['cid' => $contactId, 'past' => $showPast ? 0 : 1, 'reset' => 1]
    ));
    // « mode » entre en collision avec une variable du contexte Smarty
    // de CiviCRM : le nom doit rester spécifique.
    $this->assign('availMode', $this->modeFor($therapist));
    $this->assign('availabilities', Availability::getForTherapist($therapistId));
    $this->assign('exceptions',     Availability::getExceptions($therapistId));
    $this->assign('workdayCount',   \CRM\Booking\BAO\Workday::countUpcoming($therapistId));
    $this->assign('workdaysURL',    \CRM_Utils_System::url('civicrm/booking/workdays', [
      'therapist_id' => $therapistId, 'reset' => 1,
    ]));
    $this->assign('invoiceAvailable', Utils::isSwissQRInvoiceActive());
    $this->assign('dayNames', [
      0 => ts('Dimanche'), 1 => ts('Lundi'),   2 => ts('Mardi'),
      3 => ts('Mercredi'), 4 => ts('Jeudi'),   5 => ts('Vendredi'),
      6 => ts('Samedi'),
    ]);

    \CRM_Core_Resources::singleton()->addStyleFile('ch.ipik.booking', 'css/booking.css');

    parent::run();
  }

  // -------------------------------------------------------------------------

  /**
   * Mode de disponibilité : celui de l'intervenant, sinon le réglage général.
   */
  private function modeFor(array $therapist): string {
    $own = trim((string) ($therapist['availability_mode'] ?? ''));
    if ($own === 'weekly' || $own === 'workdays') {
      return $own;
    }
    return Utils::getSetting('availability_mode', 'weekly') === 'workdays'
      ? 'workdays' : 'weekly';
  }

  private function countAppointments(int $therapistId, bool $showPast): int {
    $where = $showPast
      ? 'a.start_datetime < NOW()'
      : 'a.start_datetime >= NOW()';

    return (int) \CRM_Core_DAO::singleValueQuery(
      "SELECT COUNT(*) FROM civicrm_booking_appointment a
       WHERE a.therapist_id = %1 AND a.status != 'cancelled' AND {$where}",
      [1 => [$therapistId, 'Integer']]
    );
  }

  private function fetchAppointments(int $therapistId, bool $showPast, int $offset): array {
    $where = $showPast
      ? 'a.start_datetime < NOW()'
      : 'a.start_datetime >= NOW()';
    $order = $showPast ? 'DESC' : 'ASC';

    $dao = \CRM_Core_DAO::executeQuery(
      "SELECT a.*, at.label AS type_label, at.color AS type_color,
              c.display_name AS contact_name
       FROM civicrm_booking_appointment a
       JOIN civicrm_booking_appointment_type at ON at.id = a.appointment_type_id
       JOIN civicrm_contact c ON c.id = a.contact_id
       WHERE a.therapist_id = %1
         AND a.status != 'cancelled'
         AND {$where}
       ORDER BY a.start_datetime {$order}
       LIMIT " . self::PER_PAGE . " OFFSET " . (int) $offset,
      [1 => [$therapistId, 'Integer']]
    );

    $rows = [];
    while ($dao->fetch()) {
      $row = $dao->toArray();
      $start = new \DateTime($row['start_datetime']);
      $row['day_label']  = $this->formatDayFr($start);
      $row['time_label'] = $start->format('H:i');
      $row['end_label']  = (new \DateTime($row['end_datetime']))->format('H:i');
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * Regrouper les rendez-vous par mois, en préservant l'ordre.
   */
  private function groupByMonth(array $appointments): array {
    $months = [];
    foreach ($appointments as $appt) {
      $date = new \DateTime($appt['start_datetime']);
      $key  = $date->format('Y-m');
      if (!isset($months[$key])) {
        $months[$key] = [
          'label'        => $this->formatMonthFr($date),
          'appointments' => [],
        ];
      }
      $months[$key]['appointments'][] = $appt;
    }
    return array_values($months);
  }

  private function buildPageLinks(int $contactId, bool $showPast, int $pages, int $current): array {
    if ($pages <= 1) return [];

    $links = [];
    for ($p = 1; $p <= $pages; $p++) {
      $links[] = [
        'num'     => $p,
        'current' => $p === $current,
        'url'     => \CRM_Utils_System::url('civicrm/booking/therapist-agenda', [
          'cid'   => $contactId,
          'past'  => $showPast ? 1 : 0,
          'page'  => $p,
          'reset' => 1,
        ]),
      ];
    }
    return $links;
  }

  /**
   * « lundi 28 septembre »
   */
  private function formatDayFr(\DateTime $date): string {
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois  = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
              'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return sprintf(
      '%s %d %s',
      $jours[(int) $date->format('w')],
      (int) $date->format('j'),
      $mois[(int) $date->format('n')]
    );
  }

  /**
   * « Septembre 2026 »
   */
  private function formatMonthFr(\DateTime $date): string {
    $mois = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
             'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    return $mois[(int) $date->format('n')] . ' ' . $date->format('Y');
  }
}
