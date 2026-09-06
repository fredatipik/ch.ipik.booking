<?php
namespace CRM\Booking\Page;

use CRM\Booking\Utils;

/**
 * Page ContactAppointments — onglet « Rendez-vous » de la fiche d'un patient.
 *
 * Liste ses rendez-vous, à venir puis passés, avec le intervenant·e et le statut.
 */
class ContactAppointments extends \CRM_Core_Page {

  public function run(): void {
    $contactId = (int) \CRM_Utils_Request::retrieve('cid', 'Positive', $this);
    if (!$contactId) {
      \CRM_Core_Error::statusBounce(ts('Contact non précisé.'));
    }

    $this->assign('upcoming', $this->fetch($contactId, TRUE));
    $this->assign('past',     $this->fetch($contactId, FALSE));
    $this->assign('contactId', $contactId);

    parent::run();
  }

  /**
   * Rendez-vous à venir (ASC) ou passés (DESC, 20 derniers).
   */
  private function fetch(int $contactId, bool $upcoming): array {
    $where = $upcoming ? 'a.start_datetime >= NOW()' : 'a.start_datetime < NOW()';
    $order = $upcoming ? 'ASC' : 'DESC';
    $limit = $upcoming ? '' : 'LIMIT 20';

    $dao = \CRM_Core_DAO::executeQuery(
      "SELECT a.*, at.label AS type_label, at.color AS type_color,
              t.contact_id AS therapist_contact_id,
              tc.display_name AS therapist_name
       FROM civicrm_booking_appointment a
       JOIN civicrm_booking_appointment_type at ON at.id = a.appointment_type_id
       JOIN civicrm_booking_therapist t ON t.id = a.therapist_id
       JOIN civicrm_contact tc ON tc.id = t.contact_id
       WHERE a.contact_id = %1
         AND a.status != 'cancelled'
         AND {$where}
       ORDER BY a.start_datetime {$order}
       {$limit}",
      [1 => [$contactId, 'Integer']]
    );

    $rows = [];
    while ($dao->fetch()) {
      $row   = $dao->toArray();
      $start = new \DateTime($row['start_datetime']);
      $row['date_label'] = $this->formatDateFr($start);
      $row['time_label'] = $start->format('H:i');
      $row['end_label']  = (new \DateTime($row['end_datetime']))->format('H:i');
      $rows[] = $row;
    }
    return $rows;
  }

  /**
   * « lundi 28 septembre 2026 »
   */
  private function formatDateFr(\DateTime $date): string {
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois  = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
              'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
    return sprintf(
      '%s %d %s %d',
      $jours[(int) $date->format('w')],
      (int) $date->format('j'),
      $mois[(int) $date->format('n')],
      (int) $date->format('Y')
    );
  }
}
