<?php
namespace CRM\Booking\Page;

use CRM\Booking\BAO\Appointment;
use CRM\Booking\BAO\Availability;
use CRM\Booking\Service\InvoiceService;
use CRM\Booking\Utils;

/**
 * Page d'action — traitement des actions sur RDV via URL.
 * Routes : cancel, complete, delete availability, delete exception, generate invoice.
 * Toutes ces routes redirigent après traitement (pas de rendu HTML propre).
 */
class AppointmentAction extends \CRM_Core_Page {

  public function run(): void {
    \CRM_Core_Permission::check('access booking') || \CRM_Core_Error::statusBounce(ts('Accès refusé.'));

    $path = \CRM_Utils_System::currentPath();
    $cid  = (int) \CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);

    // Déduire l'action depuis l'URL
    if (str_contains($path, 'appointment/cancel')) {
      $this->handleCancel($cid);
    }
    elseif (str_contains($path, 'appointment/complete')) {
      $this->handleComplete($cid);
    }
    elseif (str_contains($path, 'availability/delete')) {
      $this->handleDeleteAvailability($cid);
    }
    elseif (str_contains($path, 'exception/delete')) {
      $this->handleDeleteException($cid);
    }
    elseif (str_contains($path, 'invoice/generate')) {
      $this->handleGenerateInvoice($cid);
    }
    else {
      \CRM_Core_Error::statusBounce(ts('Action inconnue.'));
    }
  }

  // -------------------------------------------------------------------------

  private function handleCancel(int $cid): void {
    $id = (int) \CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $appt = Appointment::getById($id);
    if (!$appt) \CRM_Core_Error::statusBounce(ts('Rendez-vous introuvable.'));

    Appointment::cancel($id);
    \CRM_Core_Session::setStatus(ts('Rendez-vous annulé.'), ts('OK'), 'success');
    $this->redirectBack($cid, $appt);
  }

  private function handleComplete(int $cid): void {
    $id = (int) \CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    $appt = Appointment::getById($id);
    if (!$appt) \CRM_Core_Error::statusBounce(ts('Rendez-vous introuvable.'));

    Appointment::complete($id);
    \CRM_Core_Session::setStatus(ts('Rendez-vous marqué comme terminé.'), ts('OK'), 'success');
    $this->redirectBack($cid, $appt);
  }

  private function handleDeleteAvailability(int $cid): void {
    $id = (int) \CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    \CRM_Core_DAO::executeQuery(
      'DELETE FROM civicrm_booking_availability WHERE id = %1',
      [1 => [$id, 'Integer']]
    );
    \CRM_Core_Session::setStatus(ts('Disponibilité supprimée.'), ts('OK'), 'success');
    $redirect = $cid
      ? \CRM_Utils_System::url('civicrm/booking/therapist-agenda', ['cid' => $cid])
      : \CRM_Utils_System::url('civicrm/booking/therapists');
    \CRM_Utils_System::redirect($redirect);
  }

  private function handleDeleteException(int $cid): void {
    $id = (int) \CRM_Utils_Request::retrieve('id', 'Positive', $this, TRUE);
    Availability::deleteException($id);
    \CRM_Core_Session::setStatus(ts('Exception supprimée.'), ts('OK'), 'success');
    $redirect = $cid
      ? \CRM_Utils_System::url('civicrm/booking/therapist-agenda', ['cid' => $cid])
      : \CRM_Utils_System::url('civicrm/booking/therapists');
    \CRM_Utils_System::redirect($redirect);
  }

  private function handleGenerateInvoice(int $cid): void {
    $appointmentId = (int) \CRM_Utils_Request::retrieve('appointment_id', 'Positive', $this, TRUE);
    $invoiceService = new InvoiceService();

    if (!$invoiceService->isAvailable()) {
      \CRM_Core_Session::setStatus(ts('L\'extension de facturation n\'est pas installée.'), ts('Erreur'), 'error');
    }
    else {
      $invoiceId = $invoiceService->generate($appointmentId);
      if ($invoiceId) {
        \CRM_Core_Session::setStatus(ts('Facture #%1 générée.', [1 => $invoiceId]), ts('Succès'), 'success');
      }
      else {
        \CRM_Core_Session::setStatus(ts('Erreur lors de la génération de la facture.'), ts('Erreur'), 'error');
      }
    }

    $appt = Appointment::getById($appointmentId);
    $this->redirectBack($cid, $appt ?? []);
  }

  // -------------------------------------------------------------------------

  private function redirectBack(int $cid, array $appt): void {
    if ($cid) {
      \CRM_Utils_System::redirect(\CRM_Utils_System::url('civicrm/booking/therapist-agenda', ['cid' => $cid]));
    }
    \CRM_Utils_System::redirect(\CRM_Utils_System::url('civicrm/booking/appointments'));
  }
}
