<?php
namespace CRM\Booking\Page;

use CRM\Booking\BAO\Therapist;
use CRM\Booking\Utils;

/**
 * Dashlet — prochains rendez-vous de l'intervenant·e connecté·e.
 *
 * Confidentialité : le dashlet n'affiche que les rendez-vous dont
 * l'intervenant·e est le contact CiviCRM de la personne connectée. Un
 * administrateur qui n'est pas intervenant ne voit aucun rendez-vous,
 * quelles que soient ses permissions par ailleurs — le nom des patients
 * n'a pas à circuler au-delà de la personne qui les reçoit.
 */
class Dashlet extends \CRM_Core_Page {

  /** Nombre de rendez-vous affichés */
  private const LIMIT = 10;

  public function run(): void {
    // Double barrière : la route exige déjà « access booking », mais un
    // dashlet peut être appelé par d'autres chemins. Sans permission,
    // rien n'est affiché.
    if (!\CRM_Core_Permission::check('access booking')) {
      $this->assign('isTherapist', FALSE);
      $this->assign('denied', TRUE);
      parent::run();
      return;
    }

    $contactId = (int) \CRM_Core_Session::getLoggedInContactID();
    $therapist = $contactId ? Therapist::getByContactId($contactId) : NULL;

    $this->assign('denied', FALSE);
    $this->assign('isTherapist', $therapist !== NULL);

    if (!$therapist) {
      parent::run();
      return;
    }

    $therapistId = (int) $therapist['id'];

    $this->assign('appointments', $this->fetchUpcoming($therapistId));
    $this->assign('agendaURL', \CRM_Utils_System::url(
      'civicrm/booking/therapist-agenda',
      ['cid' => $contactId, 'reset' => 1]
    ));

    parent::run();
  }

  /**
   * Prochains rendez-vous, en incluant ceux du jour déjà écoulés : en fin
   * de journée, ils restent utiles pour saisir une facturation.
   */
  private function fetchUpcoming(int $therapistId): array {
    $dao = \CRM_Core_DAO::executeQuery(
      "SELECT a.id, a.start_datetime, a.end_datetime, a.status,
              a.contact_id, a.swissqr_invoice_id,
              at.label AS type_label, at.color AS type_color,
              c.display_name AS contact_name,
              l.name AS location_name, l.color AS location_color
       FROM civicrm_booking_appointment a
       JOIN civicrm_booking_appointment_type at ON at.id = a.appointment_type_id
       JOIN civicrm_contact c ON c.id = a.contact_id
       LEFT JOIN civicrm_booking_location l ON l.id = a.location_id
       WHERE a.therapist_id = %1
         AND a.status != 'cancelled'
         AND DATE(a.start_datetime) >= CURDATE()
       ORDER BY a.start_datetime
       LIMIT " . self::LIMIT,
      [1 => [$therapistId, 'Integer']]
    );

    $rows  = [];
    $today = date('Y-m-d');

    while ($dao->fetch()) {
      $row   = $dao->toArray();
      $start = new \DateTime($row['start_datetime']);
      $date  = $start->format('Y-m-d');

      $row['is_today'] = $date === $today;
      $row['is_past']  = $row['start_datetime'] < date('Y-m-d H:i:s');
      $row['day']      = $row['is_today'] ? ts('Aujourd\'hui') : $this->formatDayFr($start);
      $row['time']     = $start->format('H:i');
      $row['end']      = (new \DateTime($row['end_datetime']))->format('H:i');
      $row['url']      = \CRM_Utils_System::url('civicrm/contact/view', ['cid' => $row['contact_id']]);

      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * « lun. 28 sept. »
   */
  private function formatDayFr(\DateTime $date): string {
    $jours = ['dim.', 'lun.', 'mar.', 'mer.', 'jeu.', 'ven.', 'sam.'];
    $mois  = ['', 'janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin',
              'juil.', 'août', 'sept.', 'oct.', 'nov.', 'déc.'];
    return sprintf(
      '%s %d %s',
      $jours[(int) $date->format('w')],
      (int) $date->format('j'),
      $mois[(int) $date->format('n')]
    );
  }
}
