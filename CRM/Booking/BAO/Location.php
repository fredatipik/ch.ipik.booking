<?php
namespace CRM\Booking\BAO;

/**
 * BAO Location — locaux du cabinet.
 *
 * Un local possède son propre agenda CalDAV. Lorsqu'un rendez-vous a lieu
 * dans un local, le créneau y est bloqué pour tous les intervenants, et un
 * événement y est créé portant uniquement le nom de l'intervenant.
 */
class Location {

  public static function getAll(bool $activeOnly = TRUE): array {
    $sql = 'SELECT * FROM civicrm_booking_location';
    if ($activeOnly) {
      $sql .= ' WHERE is_active = 1';
    }
    $sql .= ' ORDER BY weight, name';

    $dao = \CRM_Core_DAO::executeQuery($sql);
    $rows = [];
    while ($dao->fetch()) {
      $rows[] = $dao->toArray();
    }
    return $rows;
  }

  public static function getById(int $id): ?array {
    if (!$id) return NULL;
    $dao = \CRM_Core_DAO::executeQuery(
      'SELECT * FROM civicrm_booking_location WHERE id = %1',
      [1 => [$id, 'Integer']]
    );
    return $dao->fetch() ? $dao->toArray() : NULL;
  }

  /**
   * Liste pour les listes déroulantes : id => nom.
   */
  public static function getOptions(): array {
    $options = [];
    foreach (self::getAll() as $loc) {
      $options[$loc['id']] = $loc['name'];
    }
    return $options;
  }

  public static function save(array $params): int {
    $id = (int) ($params['id'] ?? 0);

    $values = [
      1 => [trim($params['name']), 'String'],
      2 => [trim($params['address'] ?? ''), 'String'],
      3 => [$params['color'] ?? '#8b5cf6', 'String'],
      4 => [trim($params['calendar_url'] ?? ''), 'String'],
      5 => [!empty($params['is_active']) ? 1 : 0, 'Integer'],
      6 => [(int) ($params['weight'] ?? 0), 'Integer'],
    ];

    if ($id) {
      $values[7] = [$id, 'Integer'];
      \CRM_Core_DAO::executeQuery(
        'UPDATE civicrm_booking_location
         SET name = %1, address = %2, color = %3, calendar_url = %4,
             is_active = %5, weight = %6
         WHERE id = %7',
        $values
      );
      return $id;
    }

    \CRM_Core_DAO::executeQuery(
      'INSERT INTO civicrm_booking_location
         (name, address, color, calendar_url, is_active, weight)
       VALUES (%1, %2, %3, %4, %5, %6)',
      $values
    );
    return (int) \CRM_Core_DAO::singleValueQuery('SELECT LAST_INSERT_ID()');
  }

  /**
   * Désactivation plutôt que suppression : les rendez-vous passés
   * conservent une référence au local.
   */
  public static function delete(int $id): void {
    \CRM_Core_DAO::executeQuery(
      'UPDATE civicrm_booking_location SET is_active = 0 WHERE id = %1',
      [1 => [$id, 'Integer']]
    );
  }

  /**
   * URL CalDAV d'un local, nettoyée de tout paramètre d'URL.
   */
  public static function getCalendarUrl(int $id): ?string {
    if (!$id) return NULL;
    $url = \CRM_Core_DAO::singleValueQuery(
      'SELECT calendar_url FROM civicrm_booking_location WHERE id = %1',
      [1 => [$id, 'Integer']]
    );
    if (empty($url)) return NULL;

    $url = preg_replace('/\?.*$/', '', trim((string) $url));
    return $url !== '' ? $url : NULL;
  }
}
