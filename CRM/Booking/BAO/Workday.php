<?php
namespace CRM\Booking\BAO;

/**
 * BAO Workday — journées travaillées déclarées au cas par cas.
 *
 * Modèle alternatif aux disponibilités hebdomadaires, destiné aux équipes
 * dont les horaires varient d'une semaine à l'autre. Chaque journée porte
 * ses propres horaires et le local où l'intervenant travaille ce jour-là.
 */
class Workday {

  /**
   * Journées d'un intervenant sur une période.
   *
   * @return array<string, array> Indexé par date (Y-m-d).
   */
  public static function getRange(int $therapistId, string $dateFrom, string $dateTo): array {
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT w.*, l.name AS location_name, l.color AS location_color
       FROM civicrm_booking_workday w
       LEFT JOIN civicrm_booking_location l ON l.id = w.location_id
       WHERE w.therapist_id = %1
         AND w.work_date BETWEEN %2 AND %3
       ORDER BY w.work_date',
      [
        1 => [$therapistId, 'Integer'],
        2 => [$dateFrom, 'String'],
        3 => [$dateTo, 'String'],
      ]
    );

    $rows = [];
    while ($dao->fetch()) {
      $rows[$dao->work_date] = $dao->toArray();
    }
    return $rows;
  }

  /**
   * Journée précise, ou NULL si l'intervenant ne travaille pas ce jour-là.
   */
  public static function getForDate(int $therapistId, string $date): ?array {
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT * FROM civicrm_booking_workday
       WHERE therapist_id = %1 AND work_date = %2',
      [
        1 => [$therapistId, 'Integer'],
        2 => [$date, 'String'],
      ]
    );
    return $dao->fetch() ? $dao->toArray() : NULL;
  }

  /**
   * Déclarer ou mettre à jour une journée travaillée.
   */
  public static function save(int $therapistId, string $date, array $params): void {
    $locationId = !empty($params['location_id']) ? (int) $params['location_id'] : NULL;

    $common = [
      1 => [$therapistId, 'Integer'],
      2 => [$date, 'String'],
      3 => [self::normalizeTime($params['start_time']), 'String'],
      4 => [self::normalizeTime($params['end_time']), 'String'],
    ];

    // CRM_Core_DAO refuse NULL sur un paramètre typé Integer : le cas
    // « hors local » passe donc par une requête distincte.
    if ($locationId === NULL) {
      \CRM_Core_DAO::executeQuery(
        'INSERT INTO civicrm_booking_workday
           (therapist_id, work_date, start_time, end_time, location_id)
         VALUES (%1, %2, %3, %4, NULL)
         ON DUPLICATE KEY UPDATE
           start_time  = %3,
           end_time    = %4,
           location_id = NULL',
        $common
      );
      return;
    }

    \CRM_Core_DAO::executeQuery(
      'INSERT INTO civicrm_booking_workday
         (therapist_id, work_date, start_time, end_time, location_id)
       VALUES (%1, %2, %3, %4, %5)
       ON DUPLICATE KEY UPDATE
         start_time  = %3,
         end_time    = %4,
         location_id = %5',
      $common + [5 => [$locationId, 'Integer']]
    );
  }

  /**
   * Retirer une journée travaillée.
   */
  public static function remove(int $therapistId, string $date): void {
    \CRM_Core_DAO::executeQuery(
      'DELETE FROM civicrm_booking_workday
       WHERE therapist_id = %1 AND work_date = %2',
      [
        1 => [$therapistId, 'Integer'],
        2 => [$date, 'String'],
      ]
    );
  }

  /**
   * Nombre de journées déclarées à partir d'aujourd'hui.
   */
  public static function countUpcoming(int $therapistId): int {
    return (int) \CRM_Core_DAO::singleValueQuery(
      'SELECT COUNT(*) FROM civicrm_booking_workday
       WHERE therapist_id = %1 AND work_date >= CURDATE()',
      [1 => [$therapistId, 'Integer']]
    );
  }

  /**
   * '9:00' ou '09:00' → '09:00:00'
   */
  private static function normalizeTime(string $time): string {
    $time = trim($time);
    if (preg_match('/^(\d{1,2}):(\d{2})/', $time, $m)) {
      return sprintf('%02d:%02d:00', (int) $m[1], (int) $m[2]);
    }
    return '09:00:00';
  }
}
