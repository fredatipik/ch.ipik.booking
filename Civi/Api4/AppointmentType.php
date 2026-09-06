<?php
namespace Civi\Api4;

/**
 * Entité APIv4 AppointmentType.
 */
class AppointmentType extends Generic\AbstractEntity {

  public static function get(bool $checkPermissions = TRUE): \api\v4\AppointmentType\Get {
    return (new \api\v4\AppointmentType\Get(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function getInfo(): array {
    return [
      'title'        => ts('Type de rendez-vous'),
      'title_plural' => ts('Types de rendez-vous'),
      'description'  => ts('Types de rendez-vous ch.ipik.booking'),
      'primary_key'  => ['id'],
      'type'         => ['Base'],
      'table_name'   => 'civicrm_booking_appointment_type',
    ];
  }
}
