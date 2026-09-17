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
      'title'        => ts('Appointment type'),
      'title_plural' => ts('Appointment types'),
      'description'  => ts('ch.ipik.practicebooking appointment types'),
      'primary_key'  => ['id'],
      'type'         => ['Base'],
      'table_name'   => 'civicrm_booking_appointment_type',
    ];
  }
}
