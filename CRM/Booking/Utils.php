<?php
namespace CRM\Booking;

/**
 * Utilitaires généraux pour ch.ipik.booking.
 */
class Utils {

  /**
   * Lire un paramètre depuis civicrm_booking_settings.
   */
  public static function getSetting(string $key, mixed $default = NULL): mixed {
    $value = \CRM_Core_DAO::singleValueQuery(
      'SELECT `value` FROM `civicrm_booking_settings` WHERE `key` = %1',
      [1 => [$key, 'String']]
    );
    return $value !== NULL ? $value : $default;
  }

  /**
   * Écrire un paramètre dans civicrm_booking_settings.
   */
  public static function setSetting(string $key, mixed $value): void {
    \CRM_Core_DAO::executeQuery(
      'INSERT INTO `civicrm_booking_settings` (`key`, `value`)
       VALUES (%1, %2)
       ON DUPLICATE KEY UPDATE `value` = %2',
      [
        1 => [$key, 'String'],
        2 => [(string) $value, 'String'],
      ]
    );
  }

  /**
   * URL de l'onglet agenda pour un intervenant·e.
   */
  public static function agendaTabUrl(int $contactId): string {
    return \CRM_Utils_System::url(
      'civicrm/booking/therapist-agenda',
      ['cid' => $contactId]
    );
  }

  /**
   * URL de base du plugin (pour assets JS/CSS).
   */
  public static function resourceUrl(): string {
    return \CRM_Core_Resources::singleton()->getUrl('ch.ipik.booking');
  }

  /**
   * Formater une datetime pour l'affichage (selon locale CiviCRM).
   */
  public static function formatDatetime(string $datetime): string {
    return \CRM_Utils_Date::customFormat($datetime, '%d.%m.%Y %H:%M');
  }

  /**
   * Vérifier si l'extension swissQRinvoice est active.
   */
  public static function isSwissQRInvoiceActive(): bool {
    static $active = NULL;
    if ($active !== NULL) return $active;

    try {
      $result = \civicrm_api3('Extension', 'get', [
        'key'    => 'ch.ipik.swissQRinvoice',
        'status' => 'installed',
      ]);
      $active = (int) ($result['count'] ?? 0) > 0;
    }
    catch (\Throwable $e) {
      $active = FALSE;
    }

    return $active;
  }

  /**
   * Retourner les stratégies d'attribution disponibles (pour les selects).
   */
  public static function getSelectorOptions(): array {
    return [
      'round_robin'  => ts('Round-robin (rotation équitable)'),
      'least_loaded' => ts('Moins chargé (moins de RDV sur la période)'),
      'random'       => ts('Aléatoire'),
    ];
  }

  /**
   * Poser le fil d'Ariane Booking sur une page/formulaire.
   * $extra = [['title' => '…', 'url' => '…'], …] pour les niveaux intermédiaires.
   */
  public static function setBreadCrumb(array $extra = []): void {
    $crumbs = [[
      'title' => ts('Booking'),
      'url'   => \CRM_Utils_System::url('civicrm/booking/appointments', 'reset=1'),
    ]];
    foreach ($extra as $c) {
      $crumbs[] = $c;
    }
    \CRM_Utils_System::appendBreadCrumb($crumbs);
  }

  /**
   * Logger une erreur dans le log CiviCRM.
   */
  public static function logError(string $message, array $context = []): void {
    \Civi::log()->error('[ch.ipik.booking] ' . $message, $context);
  }
}
