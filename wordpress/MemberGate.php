<?php
namespace CRM\Booking\WordPress;

use CRM\Booking\BAO\AppointmentType;

/**
 * MemberGate — vérifie les droits d'accès au formulaire de booking
 * selon le type de rendez-vous et le statut du visiteur.
 */
class MemberGate {

  /**
   * Vérifier si le visiteur peut accéder à un type de RDV.
   * Retourne ['allowed' => bool, 'reason' => string|null]
   */
  public static function check(int $appointmentTypeId, ?string $email = NULL): array {
    $type = AppointmentType::getById($appointmentTypeId);
    if (!$type || !$type['is_active']) {
      return ['allowed' => FALSE, 'reason' => 'invalid_type'];
    }

    // Type réservé aux contacts CiviCRM existants
    if ($type['requires_existing_contact']) {
      if (empty($email)) {
        return ['allowed' => FALSE, 'reason' => 'email_required'];
      }
      $exists = self::contactExistsInCiviCRM($email);
      if (!$exists) {
        return ['allowed' => FALSE, 'reason' => 'contact_not_found'];
      }
    }

    return ['allowed' => TRUE, 'reason' => NULL];
  }

  /**
   * Vérifier l'existence d'un contact dans CiviCRM par email.
   * Utilisé côté frontend pour feedback immédiat (AJAX).
   */
  public static function contactExistsInCiviCRM(string $email): bool {
    try {
      $result = \civicrm_api3('Contact', 'getcount', [
        'email'      => $email,
        'is_deleted' => 0,
      ]);
      return (int) $result > 0;
    }
    catch (\Throwable $e) {
      return FALSE;
    }
  }

  /**
   * Récupérer le contact CiviCRM lié à l'utilisateur WP connecté.
   * Retourne null si non connecté ou pas de lien CiviCRM.
   */
  public static function getLoggedInCiviContact(): ?array {
    if (!function_exists('get_current_user_id')) return NULL;
    $wpUserId = get_current_user_id();
    if (!$wpUserId) return NULL;

    try {
      $result = \civicrm_api3('UFMatch', 'get', [
        'uf_id'  => $wpUserId,
        'return' => 'contact_id',
      ]);
      if ($result['count'] > 0) {
        $contactId = (int) reset($result['values'])['contact_id'];
        $contact   = \civicrm_api3('Contact', 'getsingle', [
          'id'     => $contactId,
          'return' => 'id,first_name,last_name,email',
        ]);
        return $contact;
      }
    }
    catch (\Throwable $e) {}

    return NULL;
  }
}
