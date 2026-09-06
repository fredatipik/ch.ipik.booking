<?php
namespace CRM\Booking\Page;

use CRM\Booking\BAO\Location;
use CRM\Booking\BAO\Therapist;
use CRM\Booking\BAO\Workday;
use CRM\Booking\Utils;

/**
 * Page Workdays — calendrier de sélection des journées travaillées.
 *
 * Affiche six mois à la fois. Chaque jour se coche ou se décoche d'un clic ;
 * les horaires et le local se règlent dans un panneau latéral. Destiné aux
 * équipes dont les disponibilités varient d'une semaine à l'autre.
 */
class Workdays extends \CRM_Core_Page {

  private const MONTHS_SHOWN = 6;

  public function run(): void {
    \CRM_Core_Permission::check('access booking')
      || \CRM_Core_Error::statusBounce(ts('Accès refusé.'));

    $therapistId = (int) \CRM_Utils_Request::retrieve('therapist_id', 'Positive', $this, TRUE);
    $therapist   = Therapist::getById($therapistId);
    if (!$therapist) {
      \CRM_Core_Error::statusBounce(ts('Intervenant·e introuvable.'));
    }

    $contactId = (int) $therapist['contact_id'];

    \CRM_Utils_System::setTitle(
      ts('Jours de travail — %1', [1 => $therapist['display_name']])
    );
    Utils::setBreadCrumb([[
      'title' => ts('Intervenant·es'),
      'url'   => \CRM_Utils_System::url('civicrm/booking/therapists', 'reset=1'),
    ]]);

    // Point de départ : premier jour du mois demandé, ou du mois courant
    $offset = (int) \CRM_Utils_Request::retrieve('offset', 'Integer', $this, FALSE, 0);
    $start  = (new \DateTime('first day of this month'))
      ->modify(sprintf('%+d months', $offset * self::MONTHS_SHOWN));
    $end    = (clone $start)->modify('+' . self::MONTHS_SHOWN . ' months')->modify('-1 day');

    $workdays = Workday::getRange($therapistId, $start->format('Y-m-d'), $end->format('Y-m-d'));

    $this->assign('therapist',  $therapist);
    $this->assign('contactId',  $contactId);
    $this->assign('months',     $this->buildMonths($start, $workdays));
    $this->assign('locations',  Location::getAll());
    $this->assign('workdays',   $workdays);
    $this->assign('defaults', [
      'start' => Utils::getSetting('default_start_time', '09:00'),
      'end'   => Utils::getSetting('default_end_time', '17:00'),
    ]);

    $this->assign('prevURL', \CRM_Utils_System::url('civicrm/booking/workdays', [
      'therapist_id' => $therapistId, 'offset' => $offset - 1, 'reset' => 1,
    ]));
    $this->assign('nextURL', \CRM_Utils_System::url('civicrm/booking/workdays', [
      'therapist_id' => $therapistId, 'offset' => $offset + 1, 'reset' => 1,
    ]));
    $this->assign('saveURL', \CRM_Utils_System::url('civicrm/booking/workdays/save'));
    $this->assign('agendaURL', \CRM_Utils_System::url('civicrm/booking/therapist-agenda', [
      'cid' => $contactId, 'reset' => 1,
    ]));
    $this->assign('periodLabel', $this->formatPeriod($start, $end));

    parent::run();
  }

  // -------------------------------------------------------------------------

  /**
   * Construire la grille des six mois affichés.
   */
  private function buildMonths(\DateTime $start, array $workdays): array {
    $monthNames = ['', 'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                   'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
    $today  = date('Y-m-d');
    $months = [];

    for ($m = 0; $m < self::MONTHS_SHOWN; $m++) {
      $monthStart = (clone $start)->modify("+{$m} months");
      $daysInMonth = (int) $monthStart->format('t');

      // Décalage pour que la semaine commence le lundi
      $cells = array_fill(0, ((int) $monthStart->format('N')) - 1, NULL);

      for ($d = 1; $d <= $daysInMonth; $d++) {
        $date    = $monthStart->format('Y-m-') . sprintf('%02d', $d);
        $workday = $workdays[$date] ?? NULL;

        $cells[] = [
          'day'      => $d,
          'date'     => $date,
          'is_past'  => $date < $today,
          'is_today' => $date === $today,
          'selected' => $workday !== NULL,
          'start'    => $workday ? substr($workday['start_time'], 0, 5) : '',
          'end'      => $workday ? substr($workday['end_time'], 0, 5) : '',
          'location' => $workday['location_id'] ?? '',
          'color'    => $workday['location_color'] ?? '',
        ];
      }

      $months[] = [
        'label' => $monthNames[(int) $monthStart->format('n')] . ' ' . $monthStart->format('Y'),
        'cells' => $cells,
      ];
    }

    return $months;
  }

  private function formatPeriod(\DateTime $start, \DateTime $end): string {
    $names = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
              'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return sprintf(
      '%s %s – %s %s',
      $names[(int) $start->format('n')], $start->format('Y'),
      $names[(int) $end->format('n')],   $end->format('Y')
    );
  }
}
