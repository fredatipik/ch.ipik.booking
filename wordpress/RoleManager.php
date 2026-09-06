<?php
namespace CRM\Booking\WordPress;

/**
 * RoleManager — gestion du rôle WordPress ipik_therapist.
 * Créé à l'activation de l'extension, supprimé à la désinstallation.
 */
class RoleManager {

  const ROLE_SLUG = 'ipik_therapist';
  const ROLE_NAME = 'Intervenant·e (IPIK)';

  /**
   * Créer le rôle s'il n'existe pas déjà.
   */
  public static function ensureTherapistRole(): void {
    if (!function_exists('add_role')) return;
    if (get_role(self::ROLE_SLUG)) return;

    add_role(self::ROLE_SLUG, self::ROLE_NAME, self::getCapabilities());
  }

  /**
   * Supprimer le rôle (à l'uninstall).
   */
  public static function removeTherapistRole(): void {
    if (!function_exists('remove_role')) return;
    remove_role(self::ROLE_SLUG);
  }

  /**
   * Capacités WordPress du intervenant·e.
   * Accès CiviCRM limité à ses propres données.
   */
  private static function getCapabilities(): array {
    return [
      // WordPress de base
      'read'                    => TRUE,
      'upload_files'            => FALSE,
      'edit_posts'              => FALSE,
      // CiviCRM — capacités limitées
      'access CiviCRM'          => TRUE,
      'access booking'          => TRUE,   // Voir ses propres RDV
      'administer booking'      => FALSE,  // Pas de gestion des types / intervenant·es
      'view all contacts'       => FALSE,
      'access all custom data'  => FALSE,
    ];
  }

  /**
   * Retourner les utilisateurs WP ayant le rôle intervenant·e.
   */
  public static function getTherapistUsers(): array {
    if (!function_exists('get_users')) return [];
    return get_users(['role' => self::ROLE_SLUG]);
  }

  /**
   * Vérifier si l'utilisateur WP courant est un intervenant·e.
   */
  public static function currentUserIsTherapist(): bool {
    if (!function_exists('wp_get_current_user')) return FALSE;
    $user = wp_get_current_user();
    return in_array(self::ROLE_SLUG, (array) $user->roles, TRUE);
  }
}
