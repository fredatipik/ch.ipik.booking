<?php
namespace CRM\Booking\Page;

use CRM\Booking\BAO\Workday;
use CRM\Booking\Utils;

/**
 * Point d'entrée AJAX pour l'enregistrement d'une journée travaillée.
 *
 * Reçoit une action (set ou unset), une date, et le cas échéant les horaires
 * et le local. Répond en JSON.
 */
class WorkdaySave extends \CRM_Core_Page {

  public function run(): void {
    \CRM_Core_Permission::check('access booking')
      || $this->respond(FALSE, ts('Accès refusé.'));

    $therapistId = (int) ($_POST['therapist_id'] ?? 0);
    $date        = trim((string) ($_POST['date'] ?? ''));
    $action      = (string) ($_POST['action_type'] ?? 'set');

    if (!$therapistId || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      $this->respond(FALSE, ts('Paramètres incomplets.'));
    }

    try {
      if ($action === 'unset') {
        Workday::remove($therapistId, $date);
        $this->respond(TRUE, ts('Journée retirée.'), ['selected' => FALSE]);
      }

      $startTime = trim((string) ($_POST['start_time'] ?? ''));
      $endTime   = trim((string) ($_POST['end_time'] ?? ''));

      if (!preg_match('/^\d{1,2}:\d{2}$/', $startTime)
        || !preg_match('/^\d{1,2}:\d{2}$/', $endTime)) {
        $this->respond(FALSE, ts('Horaires attendus au format HH:MM.'));
      }
      if ($startTime >= $endTime) {
        $this->respond(FALSE, ts('L\'heure de fin doit suivre l\'heure de début.'));
      }

      $locationId = (int) ($_POST['location_id'] ?? 0);

      Workday::save($therapistId, $date, [
        'start_time'  => $startTime,
        'end_time'    => $endTime,
        'location_id' => $locationId ?: NULL,
      ]);

      $this->respond(TRUE, ts('Journée enregistrée.'), [
        'selected'    => TRUE,
        'start'       => $startTime,
        'end'         => $endTime,
        'location_id' => $locationId,
      ]);
    }
    catch (\Throwable $e) {
      Utils::logError('Enregistrement d\'une journée travaillée', [
        'therapist_id' => $therapistId,
        'date'         => $date,
        'error'        => $e->getMessage(),
      ]);
      $this->respond(FALSE, ts('Enregistrement impossible.'));
    }
  }

  /**
   * Répondre en JSON et interrompre l'exécution.
   */
  private function respond(bool $success, string $message, array $data = []): void {
    \CRM_Utils_System::setHttpHeader('Content-Type', 'application/json; charset=utf-8');
    echo json_encode(array_merge([
      'success' => $success,
      'message' => $message,
    ], $data));
    \CRM_Utils_System::civiExit();
  }
}
