<?php
namespace Civi\Api4;

/**
 * Entité APIv4 Therapist.
 */
class Therapist extends Generic\AbstractEntity {

  public static function get(bool $checkPermissions = TRUE): \api\v4\Therapist\Get {
    return (new \api\v4\Therapist\Get(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function getInfo(): array {
    return [
      'title'        => ts('Intervenant·e'),
      'title_plural' => ts('Intervenant·es'),
      'description'  => ts('Intervenant·es ch.ipik.booking'),
      'primary_key'  => ['id'],
      'type'         => ['Base'],
      'table_name'   => 'civicrm_booking_therapist',
    ];
  }
}
