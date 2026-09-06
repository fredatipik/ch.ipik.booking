<?php
/**
 * API v3 BookingReminder.run — appelée par le scheduled job CiviCRM.
 * Délègue à CRM\Booking\Cron\ReminderJob::run().
 */

/**
 * @param array $params
 * @return array
 */
function civicrm_api3_booking_reminder_run(array $params): array {
  try {
    \CRM\Booking\Cron\ReminderJob::run();
    return civicrm_api3_create_success([], $params, 'BookingReminder', 'run');
  }
  catch (\Throwable $e) {
    return civicrm_api3_create_error($e->getMessage());
  }
}

/**
 * Spec pour le scheduled job.
 */
function _civicrm_api3_booking_reminder_run_spec(array &$spec): void {
  // Pas de paramètres requis
}
