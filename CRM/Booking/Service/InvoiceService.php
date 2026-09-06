<?php
namespace CRM\Booking\Service;

use CRM\Booking\BAO\Appointment;
use CRM\Booking\Utils;

/**
 * InvoiceService — pont vers l’extension de facturation QR suisse.
 *
 * Phase 1 : présent mais passif (méthodes no-op si extension absente).
 * Phase 2 : génération et envoi de la facture QR depuis le RDV.
 *
 * Déclenchement prévu :
 *  - Manuel : bouton "Générer la facture" dans l'agenda intervenant·e
 *  - (Optionnel Phase 2) Automatique au passage en statut "completed"
 */
class InvoiceService {

  /**
   * Vérifier si la génération de facture est disponible.
   */
  public function isAvailable(): bool {
    return Utils::isSwissQRInvoiceActive();
  }

  /**
   * Générer une facture QR pour un rendez-vous terminé.
   * Retourne l'ID de la facture créée, ou null si non disponible.
   *
   * @param int   $appointmentId  ID du RDV dans civicrm_booking_appointment
   * @param array $lines          Lignes de facturation [['label'=>'...','amount'=>0.00], ...]
   *                              Si vide, une ligne par défaut est générée depuis le type de RDV.
   */
  public function generate(int $appointmentId, array $lines = []): ?int {
    if (!$this->isAvailable()) {
      Utils::logError('InvoiceService::generate() — extension de facturation non installée');
      return NULL;
    }

    $appointment = Appointment::getById($appointmentId);
    if (!$appointment) {
      return NULL;
    }

    // Vérifier qu'une facture n'existe pas déjà
    if (!empty($appointment['swissqr_invoice_id'])) {
      Utils::logError('InvoiceService::generate() — facture déjà existante', ['appointment_id' => $appointmentId]);
      return (int) $appointment['swissqr_invoice_id'];
    }

    // Lignes par défaut : une ligne = type de RDV
    if (empty($lines)) {
      $lines = [[
        'label'    => $appointment['type_label'] ?? 'Consultation',
        'quantity' => 1,
        'amount'   => 0.00, // À saisir manuellement en Phase 2
      ]];
    }

    try {
      // Appel à la BAO de swissQRinvoice
      // La classe est chargée dynamiquement pour ne pas créer de dépendance dure
      $invoiceBao = new \CRM\SwissQRInvoice\BAO\Invoice();
      $invoiceId  = $invoiceBao->create([
        'contact_id'  => $appointment['contact_id'],
        'date'        => date('Y-m-d'),
        'due_date'    => date('Y-m-d', strtotime('+30 days')),
        'lines'       => $lines,
        'reference'   => 'RDV-' . $appointmentId,
        'notes'       => sprintf(
          'Rendez-vous du %s',
          Utils::formatDatetime($appointment['start_datetime'])
        ),
      ]);

      if ($invoiceId) {
        // Lier la facture au RDV
        \CRM_Core_DAO::executeQuery(
          'UPDATE civicrm_booking_appointment SET swissqr_invoice_id = %1 WHERE id = %2',
          [
            1 => [$invoiceId, 'Integer'],
            2 => [$appointmentId, 'Integer'],
          ]
        );
      }

      return $invoiceId ?: NULL;
    }
    catch (\Throwable $e) {
      Utils::logError('InvoiceService::generate() failed', [
        'appointment_id' => $appointmentId,
        'error'          => $e->getMessage(),
      ]);
      return NULL;
    }
  }

  /**
   * Envoyer la facture existante par email.
   */
  public function send(int $appointmentId): bool {
    if (!$this->isAvailable()) return FALSE;

    $appointment = Appointment::getById($appointmentId);
    if (!$appointment || empty($appointment['swissqr_invoice_id'])) {
      return FALSE;
    }

    try {
      $invoiceBao = new \CRM\SwissQRInvoice\BAO\Invoice();
      $invoiceBao->sendByEmail((int) $appointment['swissqr_invoice_id']);
      return TRUE;
    }
    catch (\Throwable $e) {
      Utils::logError('InvoiceService::send() failed', ['error' => $e->getMessage()]);
      return FALSE;
    }
  }
}
