<?php
namespace Civi\Api4;

/**
 * Entité APIv4 Appointment.
 * Expose les actions Get, Create, Cancel, Complete, GetSlots.
 */
class Appointment extends Generic\AbstractEntity {

  /**
   * @return \api\v4\Appointment\Get
   */
  public static function get(bool $checkPermissions = TRUE): \api\v4\Appointment\Get {
    return (new \api\v4\Appointment\Get(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \api\v4\Appointment\Create
   */
  public static function create(bool $checkPermissions = TRUE): \api\v4\Appointment\Create {
    return (new \api\v4\Appointment\Create(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \api\v4\Appointment\Cancel
   */
  public static function cancel(bool $checkPermissions = TRUE): \api\v4\Appointment\Cancel {
    return (new \api\v4\Appointment\Cancel(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \api\v4\Appointment\Complete
   */
  public static function complete(bool $checkPermissions = TRUE): \api\v4\Appointment\Complete {
    return (new \api\v4\Appointment\Complete(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  /**
   * @return \api\v4\Appointment\GetSlots
   */
  public static function getSlots(bool $checkPermissions = TRUE): \api\v4\Appointment\GetSlots {
    return (new \api\v4\Appointment\GetSlots(static::class, __FUNCTION__))
      ->setCheckPermissions($checkPermissions);
  }

  public static function getInfo(): array {
    return [
      'title'        => ts('Rendez-vous'),
      'title_plural' => ts('Rendez-vous'),
      'description'  => ts('Rendez-vous ch.ipik.booking'),
      'primary_key'  => ['id'],
      'type'         => ['Base'],
      'table_name'   => 'civicrm_booking_appointment',
      'class_args'   => [],
    ];
  }
}
