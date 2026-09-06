<?php
namespace CRM\Booking\BAO;

/**
 * BAO Therapist — CRUD et helpers pour les intervenant·es.
 */
class Therapist {

  /**
   * Récupérer tous les intervenant·es actifs avec leur nom CiviCRM.
   */
  public static function getAll(bool $activeOnly = TRUE): array {
    $sql = 'SELECT t.*, c.display_name, c.sort_name
            FROM civicrm_booking_therapist t
            JOIN civicrm_contact c ON c.id = t.contact_id';
    if ($activeOnly) {
      $sql .= ' WHERE t.is_active = 1';
    }
    $sql .= ' ORDER BY c.sort_name';

    $dao = \CRM_Core_DAO::executeQuery($sql);
    $results = [];
    while ($dao->fetch()) {
      $results[] = $dao->toArray();
    }
    return $results;
  }

  /**
   * Récupérer un intervenant·e par son ID.
   */
  public static function getById(int $id): ?array {
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT t.*, c.display_name, c.sort_name, c.email_greeting_display
       FROM civicrm_booking_therapist t
       JOIN civicrm_contact c ON c.id = t.contact_id
       WHERE t.id = %1',
      [1 => [$id, 'Integer']]
    );
    return $dao->fetch() ? $dao->toArray() : NULL;
  }

  /**
   * Récupérer un intervenant·e par contact_id CiviCRM.
   */
  public static function getByContactId(int $contactId): ?array {
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT t.*, c.display_name
       FROM civicrm_booking_therapist t
       JOIN civicrm_contact c ON c.id = t.contact_id
       WHERE t.contact_id = %1',
      [1 => [$contactId, 'Integer']]
    );
    return $dao->fetch() ? $dao->toArray() : NULL;
  }

  /**
   * Vérifier si un contact CiviCRM est un intervenant·e enregistré.
   */
  public static function isTherapist(int $contactId): bool {
    $count = \CRM_Core_DAO::singleValueQuery(
      'SELECT COUNT(*) FROM civicrm_booking_therapist WHERE contact_id = %1 AND is_active = 1',
      [1 => [$contactId, 'Integer']]
    );
    return (int) $count > 0;
  }

  /**
   * Créer ou mettre à jour un intervenant·e.
   */
  public static function save(array $params): int {
    $id = $params['id'] ?? NULL;

    // CRM_Core_DAO refuse NULL sur un paramètre typé String. La colonne
    // accepte la chaîne vide, lue comme « réglage général » par modeFor().
    $mode = $params['availability_mode'] ?? '';
    if (!in_array($mode, ['weekly', 'workdays'], TRUE)) {
      $mode = '';
    }

    // Zéro plutôt que NULL : le type Integer refuse la valeur nulle, et
    // toute valeur non positive se lit comme « aucun local habituel ».
    $defaultLocation = !empty($params['default_location_id'])
      ? (int) $params['default_location_id']
      : 0;

    if ($id) {
      $wpU = !empty($params['wp_user_id']) ? (int) $params['wp_user_id'] : NULL;
      $calUrl = trim($params['calendar_url'] ?? '');
      if ($wpU !== NULL) {
        \CRM_Core_DAO::executeQuery(
          'UPDATE civicrm_booking_therapist SET color=%1,max_advance_days=%2,buffer_minutes=%3,is_active=%4,wp_user_id=%5,calendar_url=%6,availability_mode=%7,default_location_id=%8 WHERE id=%9',
          [1=>[$params['color']??'#3b82f6','String'],2=>[(int)($params['max_advance_days']??60),'Integer'],3=>[(int)($params['buffer_minutes']??0),'Integer'],4=>[(int)($params['is_active']??1),'Integer'],5=>[$wpU,'Integer'],6=>[$calUrl,'String'],7=>[$mode,'String'],8=>[$defaultLocation,'Integer'],9=>[(int)$id,'Integer']]
        );
      } else {
        \CRM_Core_DAO::executeQuery(
          'UPDATE civicrm_booking_therapist SET color=%1,max_advance_days=%2,buffer_minutes=%3,is_active=%4,wp_user_id=NULL,calendar_url=%5,availability_mode=%6,default_location_id=%7 WHERE id=%8',
          [1=>[$params['color']??'#3b82f6','String'],2=>[(int)($params['max_advance_days']??60),'Integer'],3=>[(int)($params['buffer_minutes']??0),'Integer'],4=>[(int)($params['is_active']??1),'Integer'],5=>[$calUrl,'String'],6=>[$mode,'String'],7=>[$defaultLocation,'Integer'],8=>[(int)$id,'Integer']]
        );
      }
      return (int) $id;
    }

    $wpUserId = !empty($params['wp_user_id']) ? (int) $params['wp_user_id'] : NULL;
    if ($wpUserId !== NULL) {
      \CRM_Core_DAO::executeQuery(
        'INSERT INTO civicrm_booking_therapist
           (contact_id, wp_user_id, color, max_advance_days, buffer_minutes, is_active, calendar_url, availability_mode, default_location_id)
         VALUES (%1, %2, %3, %4, %5, %6, %7, %8, %9)',
        [
          1 => [(int) $params['contact_id'], 'Integer'],
          2 => [$wpUserId, 'Integer'],
          3 => [$params['color'] ?? '#3b82f6', 'String'],
          4 => [(int) ($params['max_advance_days'] ?? 60), 'Integer'],
          5 => [(int) ($params['buffer_minutes'] ?? 0), 'Integer'],
          6 => [(int) ($params['is_active'] ?? 1), 'Integer'],
          7 => [trim($params['calendar_url'] ?? ''), 'String'],
          8 => [$mode, 'String'],
          9 => [$defaultLocation, 'Integer'],
        ]
      );
    } else {
      \CRM_Core_DAO::executeQuery(
        'INSERT INTO civicrm_booking_therapist
           (contact_id, color, max_advance_days, buffer_minutes, is_active, calendar_url, availability_mode, default_location_id)
         VALUES (%1, %2, %3, %4, %5, %6, %7, %8)',
        [
          1 => [(int) $params['contact_id'], 'Integer'],
          2 => [$params['color'] ?? '#3b82f6', 'String'],
          3 => [(int) ($params['max_advance_days'] ?? 60), 'Integer'],
          4 => [(int) ($params['buffer_minutes'] ?? 0), 'Integer'],
          5 => [(int) ($params['is_active'] ?? 1), 'Integer'],
          6 => [trim($params['calendar_url'] ?? ''), 'String'],
          7 => [$mode, 'String'],
          8 => [$defaultLocation, 'Integer'],
        ]
      );
    }
    return (int) \CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
  }

  /**
   * Supprimer un intervenant·e (soft delete).
   */
  public static function delete(int $id): void {
    \CRM_Core_DAO::executeQuery(
      'UPDATE civicrm_booking_therapist SET is_active = 0 WHERE id = %1',
      [1 => [$id, 'Integer']]
    );
  }

  /**
   * Récupérer les intervenant·es éligibles pour un type de RDV.
   */
  public static function getByAppointmentType(int $typeId): array {
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT t.*, c.display_name
       FROM civicrm_booking_therapist t
       JOIN civicrm_booking_type_therapist tt ON tt.therapist_id = t.id
       JOIN civicrm_contact c ON c.id = t.contact_id
       WHERE tt.appointment_type_id = %1 AND t.is_active = 1
       ORDER BY c.sort_name',
      [1 => [$typeId, 'Integer']]
    );
    $results = [];
    while ($dao->fetch()) {
      $results[] = $dao->toArray();
    }
    return $results;
  }

  /**
   * Définir les types de RDV associés à un intervenant·e.
   */
  public static function setAppointmentTypes(int $therapistId, array $typeIds): void {
    \CRM_Core_DAO::executeQuery(
      'DELETE FROM civicrm_booking_type_therapist WHERE therapist_id = %1',
      [1 => [$therapistId, 'Integer']]
    );
    foreach ($typeIds as $typeId) {
      \CRM_Core_DAO::executeQuery(
        'INSERT IGNORE INTO civicrm_booking_type_therapist (appointment_type_id, therapist_id)
         VALUES (%1, %2)',
        [
          1 => [(int) $typeId, 'Integer'],
          2 => [$therapistId, 'Integer'],
        ]
      );
    }
  }

  /**
   * Email principal d'un intervenant·e.
   */
  public static function getEmail(int $therapistId): ?string {
    return \CRM_Core_DAO::singleValueQuery(
      'SELECT e.email
       FROM civicrm_email e
       JOIN civicrm_booking_therapist t ON t.contact_id = e.contact_id
       WHERE t.id = %1 AND e.is_primary = 1
       LIMIT 1',
      [1 => [$therapistId, 'Integer']]
    ) ?: NULL;
  }
}
