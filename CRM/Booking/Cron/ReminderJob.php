<?php
namespace CRM\Booking\Cron;

use CRM\Booking\BAO\Appointment;
use CRM\Booking\Service\NotificationService;
use CRM\Booking\Utils;

/**
 * ReminderJob — envoi des rappels email aux patients (à appeler par cron).
 *
 * Usage via WP-CLI :
 *   wp eval 'CRM\Booking\Cron\ReminderJob::run();' --path=/var/www/html
 *
 * Ou via CiviCRM scheduled job (Admin → Scheduled Jobs → Add Job)
 *   Class : CRM_Booking_Cron_ReminderJob
 *   Method : run
 */
class ReminderJob {

  public static function run(): void {
    $hoursBeforeStr = Utils::getSetting('reminder_hours_before', 24);
    $hoursBefore    = (int) $hoursBeforeStr;

    // Fenêtre : RDV dont le début est dans [hoursBeforeStr-1h .. hoursBeforeStr+1h]
    $from = (new \DateTime())->modify("+{$hoursBefore} hours")->modify('-30 minutes')->format('Y-m-d H:i:s');
    $to   = (new \DateTime())->modify("+{$hoursBefore} hours")->modify('+30 minutes')->format('Y-m-d H:i:s');

    $dao = \CRM_Core_DAO::executeQuery(
      "SELECT a.*,
              at.label AS type_label,
              at.duration_minutes,
              c.display_name AS contact_name,
              e.email AS contact_email,
              t_contact.display_name AS therapist_name,
              l.name AS location_name,
              l.address AS location_address
       FROM civicrm_booking_appointment a
       JOIN civicrm_booking_appointment_type at ON at.id = a.appointment_type_id
       JOIN civicrm_contact c ON c.id = a.contact_id
       JOIN civicrm_booking_therapist t ON t.id = a.therapist_id
       JOIN civicrm_contact t_contact ON t_contact.id = t.contact_id
       LEFT JOIN civicrm_booking_location l ON l.id = a.location_id
       LEFT JOIN civicrm_email e ON e.contact_id = c.id AND e.is_primary = 1
       WHERE a.status = 'confirmed'
         AND a.start_datetime BETWEEN %1 AND %2",
      [
        1 => [$from, 'String'],
        2 => [$to, 'String'],
      ]
    );

    $notifier = new NotificationService();
    $sent = 0;

    while ($dao->fetch()) {
      $appointment = $dao->toArray();
      if (!empty($appointment['contact_email'])) {
        $notifier->sendReminder($appointment);
        $sent++;
      }
    }

    \Civi::log()->info("[ch.ipik.booking] ReminderJob : {$sent} rappel(s) envoyé(s).");
  }
}
