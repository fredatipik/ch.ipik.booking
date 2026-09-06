<?php
namespace CRM\Booking\BAO;

/**
 * BAO Availability — disponibilités récurrentes et exceptions (jours off).
 */
class Availability {

  const DAYS = [0 => 'Dim', 1 => 'Lun', 2 => 'Mar', 3 => 'Mer', 4 => 'Jeu', 5 => 'Ven', 6 => 'Sam'];

  // -------------------------------------------------------------------------
  // Disponibilités récurrentes
  // -------------------------------------------------------------------------

  /**
   * Récupérer les disponibilités récurrentes d'un intervenant·e.
   */
  public static function getForTherapist(int $therapistId): array {
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT * FROM civicrm_booking_availability
       WHERE therapist_id = %1
       ORDER BY day_of_week, start_time',
      [1 => [$therapistId, 'Integer']]
    );
    $results = [];
    while ($dao->fetch()) {
      $results[] = $dao->toArray();
    }
    return $results;
  }

  /**
   * Remplacer toutes les disponibilités d'un intervenant·e.
   * $slots = [['day_of_week'=>1,'start_time'=>'09:00','end_time'=>'12:00'], ...]
   */
  public static function saveForTherapist(int $therapistId, array $slots): void {
    \CRM_Core_DAO::executeQuery(
      'DELETE FROM civicrm_booking_availability WHERE therapist_id = %1',
      [1 => [$therapistId, 'Integer']]
    );
    foreach ($slots as $slot) {
      \CRM_Core_DAO::executeQuery(
        'INSERT INTO civicrm_booking_availability (therapist_id, day_of_week, start_time, end_time)
         VALUES (%1, %2, %3, %4)',
        [
          1 => [$therapistId, 'Integer'],
          2 => [(int) $slot['day_of_week'], 'Integer'],
          3 => [$slot['start_time'], 'String'],
          4 => [$slot['end_time'], 'String'],
        ]
      );
    }
  }

  // -------------------------------------------------------------------------
  // Exceptions (jours off / congés)
  // -------------------------------------------------------------------------

  /**
   * Récupérer les exceptions futures d'un intervenant·e.
   */
  public static function getExceptions(int $therapistId, bool $futureOnly = TRUE): array {
    $sql = 'SELECT * FROM civicrm_booking_exception WHERE therapist_id = %1';
    if ($futureOnly) {
      $sql .= ' AND date_end >= CURDATE()';
    }
    $sql .= ' ORDER BY date_start';

    $dao = \CRM_Core_DAO::executeQuery($sql, [1 => [$therapistId, 'Integer']]);
    $results = [];
    while ($dao->fetch()) {
      $results[] = $dao->toArray();
    }
    return $results;
  }

  /**
   * Ajouter une exception (jour off ou spécial).
   */
  public static function addException(array $params): int {
    $isSpecial = ($params['type'] ?? 'off') === 'special';

    // CRM_Core_DAO refuse NULL sur un paramètre typé. Les horaires n'ont
    // de sens que pour un créneau exceptionnel : hors de ce cas, la
    // requête les pose littéralement à NULL.
    if ($isSpecial && !empty($params['start_time']) && !empty($params['end_time'])) {
      \CRM_Core_DAO::executeQuery(
        'INSERT INTO civicrm_booking_exception
           (therapist_id, date_start, date_end, type, start_time, end_time, note)
         VALUES (%1, %2, %3, %4, %5, %6, %7)',
        [
          1 => [(int) $params['therapist_id'], 'Integer'],
          2 => [$params['date_start'], 'String'],
          3 => [$params['date_end'], 'String'],
          4 => [$params['type'] ?? 'off', 'String'],
          5 => [$params['start_time'], 'String'],
          6 => [$params['end_time'], 'String'],
          7 => [(string) ($params['note'] ?? ''), 'String'],
        ]
      );
    }
    else {
      \CRM_Core_DAO::executeQuery(
        'INSERT INTO civicrm_booking_exception
           (therapist_id, date_start, date_end, type, start_time, end_time, note)
         VALUES (%1, %2, %3, %4, NULL, NULL, %5)',
        [
          1 => [(int) $params['therapist_id'], 'Integer'],
          2 => [$params['date_start'], 'String'],
          3 => [$params['date_end'], 'String'],
          4 => [$params['type'] ?? 'off', 'String'],
          5 => [(string) ($params['note'] ?? ''), 'String'],
        ]
      );
    }

    return (int) \CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
  }

  /**
   * Supprimer une exception.
   */
  public static function deleteException(int $id): void {
    \CRM_Core_DAO::executeQuery(
      'DELETE FROM civicrm_booking_exception WHERE id = %1',
      [1 => [$id, 'Integer']]
    );
  }

  /**
   * Vérifier si une date tombe dans une exception (jour off) pour un intervenant·e.
   */
  public static function isDateOff(int $therapistId, string $date): bool {
    $count = \CRM_Core_DAO::singleValueQuery(
      'SELECT COUNT(*) FROM civicrm_booking_exception
       WHERE therapist_id = %1 AND type = "off"
         AND date_start <= %2 AND date_end >= %2',
      [
        1 => [$therapistId, 'Integer'],
        2 => [$date, 'String'],
      ]
    );
    return (int) $count > 0;
  }

  /**
   * Récupérer les plages horaires disponibles d'un intervenant·e pour un jour donné.
   * Retourne [] si jour off ou pas de disponibilité configurée.
   * $date = 'YYYY-MM-DD'
   */
  public static function getSlotsForDate(int $therapistId, string $date): array {
    // Jour off : aucune disponibilité, même exceptionnelle
    if (self::isDateOff($therapistId, $date)) {
      return [];
    }

    $slots = [];

    // 1. Disponibilités récurrentes du jour de la semaine
    $dayOfWeek = (int) date('w', strtotime($date)); // 0=Dim
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT start_time, end_time FROM civicrm_booking_availability
       WHERE therapist_id = %1 AND day_of_week = %2',
      [
        1 => [$therapistId, 'Integer'],
        2 => [$dayOfWeek, 'Integer'],
      ]
    );
    while ($dao->fetch()) {
      $slots[] = ['start' => $dao->start_time, 'end' => $dao->end_time];
    }

    // 2. Créneaux exceptionnels (type=special) couvrant cette date
    $dao2 = \CRM_Core_DAO::executeQuery(
      "SELECT start_time, end_time FROM civicrm_booking_exception
       WHERE therapist_id = %1 AND type = 'special'
         AND date_start <= %2 AND date_end >= %2
         AND start_time IS NOT NULL AND end_time IS NOT NULL",
      [
        1 => [$therapistId, 'Integer'],
        2 => [$date, 'String'],
      ]
    );
    while ($dao2->fetch()) {
      $slots[] = ['start' => $dao2->start_time, 'end' => $dao2->end_time];
    }

    return $slots;
  }
}
