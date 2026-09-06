<?php
namespace CRM\Booking\BAO;

/**
 * BAO AppointmentType — CRUD pour les types de rendez-vous.
 */
class AppointmentType {

  /**
   * Récupérer tous les types actifs.
   */
  public static function getAll(bool $activeOnly = TRUE): array {
    $sql = 'SELECT * FROM civicrm_booking_appointment_type';
    if ($activeOnly) {
      $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY weight, label';

    $dao = \CRM_Core_DAO::executeQuery($sql);
    $results = [];
    while ($dao->fetch()) {
      $results[] = $dao->toArray();
    }
    return $results;
  }

  /**
   * Récupérer un type par ID.
   */
  public static function getById(int $id): ?array {
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT * FROM civicrm_booking_appointment_type WHERE id = %1',
      [1 => [$id, 'Integer']]
    );
    if (!$dao->fetch()) {
      return NULL;
    }
    $type = $dao->toArray();
    // Charger les intervenant·es liés
    $type['therapist_ids'] = self::getTherapistIds($id);
    return $type;
  }

  /**
   * IDs des intervenant·es liés à ce type.
   */
  public static function getTherapistIds(int $typeId): array {
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT therapist_id FROM civicrm_booking_type_therapist WHERE appointment_type_id = %1',
      [1 => [$typeId, 'Integer']]
    );
    $ids = [];
    while ($dao->fetch()) {
      $ids[] = (int) $dao->therapist_id;
    }
    return $ids;
  }

  /**
   * Créer ou mettre à jour un type de RDV.
   */
  public static function save(array $params): int {
    $id = $params['id'] ?? NULL;

    $fields = [
      1  => [$params['label'], 'String'],
      2  => [(string) ($params['description'] ?? ''), 'String'],
      3  => [(int) ($params['duration_minutes'] ?? 60), 'Integer'],
      4  => [$params['color'] ?? '#10b981', 'String'],
      5  => [(int) ($params['requires_existing_contact'] ?? 0), 'Integer'],
      6  => [(int) ($params['requires_account_creation'] ?? 0), 'Integer'],
      7  => [$params['therapist_selector'] ?? 'round_robin', 'String'],
      8  => [(int) ($params['is_active'] ?? 1), 'Integer'],
      9  => [(int) ($params['weight'] ?? 0), 'Integer'],
      // Zéro plutôt que NULL : intervalForType() lit toute valeur non
      // positive comme « reprendre le réglage général ».
      10 => [!empty($params['slot_interval_minutes']) ? (int) $params['slot_interval_minutes'] : 0, 'Integer'],
    ];

    if ($id) {
      $fields[11] = [(int) $id, 'Integer'];
      \CRM_Core_DAO::executeQuery(
        'UPDATE civicrm_booking_appointment_type
         SET label = %1, description = %2, duration_minutes = %3, color = %4,
             requires_existing_contact = %5, requires_account_creation = %6,
             therapist_selector = %7, is_active = %8, weight = %9,
             slot_interval_minutes = %10
         WHERE id = %11',
        $fields
      );
    }
    else {
      \CRM_Core_DAO::executeQuery(
        'INSERT INTO civicrm_booking_appointment_type
           (label, description, duration_minutes, color,
            requires_existing_contact, requires_account_creation,
            therapist_selector, is_active, weight, slot_interval_minutes)
         VALUES (%1, %2, %3, %4, %5, %6, %7, %8, %9, %10)',
        $fields
      );
      $id = (int) \CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
    }

    // Mettre à jour les liaisons intervenant·es
    if (isset($params['therapist_ids'])) {
      \CRM_Core_DAO::executeQuery(
        'DELETE FROM civicrm_booking_type_therapist WHERE appointment_type_id = %1',
        [1 => [$id, 'Integer']]
      );
      foreach ((array) $params['therapist_ids'] as $therapistId) {
        \CRM_Core_DAO::executeQuery(
          'INSERT IGNORE INTO civicrm_booking_type_therapist (appointment_type_id, therapist_id)
           VALUES (%1, %2)',
          [
            1 => [$id, 'Integer'],
            2 => [(int) $therapistId, 'Integer'],
          ]
        );
      }
    }

    return $id;
  }

  /**
   * Supprimer un type (soft delete).
   */
  public static function delete(int $id): void {
    \CRM_Core_DAO::executeQuery(
      'UPDATE civicrm_booking_appointment_type SET is_active = 0 WHERE id = %1',
      [1 => [$id, 'Integer']]
    );
  }

  /**
   * IDs des types de RDV associés à un intervenant·e donné.
   * (Inverse de getTherapistIds — utilisé dans le formulaire Therapist.)
   */
  public static function getTherapistTypeIds(int $therapistId): array {
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT appointment_type_id FROM civicrm_booking_type_therapist WHERE therapist_id = %1',
      [1 => [$therapistId, 'Integer']]
    );
    $ids = [];
    while ($dao->fetch()) {
      $ids[] = (int) $dao->appointment_type_id;
    }
    return $ids;
  }

  /**
   * Liste pour les selects (id => label).
   */
  public static function getOptions(): array {
    $options = [];
    foreach (self::getAll() as $type) {
      $options[$type['id']] = $type['label'];
    }
    return $options;
  }
}
