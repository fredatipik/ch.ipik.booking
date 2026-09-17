<?php
namespace Civi\Api4;

/**
 * Entité APIv4 Availability.
 */
class Availability extends Generic\AbstractEntity {

  public static function get(bool $checkPermissions = TRUE): \api\v4\Availability\Get {
    return (new \api\v4\Availability\Get(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function getInfo(): array {
    return [
      'title'        => ts('Availability'),
      'title_plural' => ts('Availabilities'),
      'description'  => ts('ch.ipik.practicebooking practitioner availabilities'),
      'primary_key'  => ['id'],
      'type'         => ['Base'],
      'table_name'   => 'civicrm_booking_availability',
    ];
  }
}
