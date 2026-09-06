<?php
namespace api\v4\Appointment;

use CRM\Booking\Service\BookingService;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * APIv4 Appointment.Create — crée un rendez-vous complet (contact + activity).
 *
 * Exemple :
 *   cv api4 Appointment.create '{
 *     "appointment_type_id":1,
 *     "start_datetime":"2025-06-10 09:00:00",
 *     "contact":{"first_name":"Jean","last_name":"Dupont","email":"jean@example.com"}
 *   }'
 */
class Create extends AbstractAction {

  /**
   * @var int
   * @required
   */
  protected int $appointment_type_id;

  /**
   * @var string  YYYY-MM-DD HH:MM:SS
   * @required
   */
  protected string $start_datetime;

  /**
   * @var array  {first_name, last_name, email, phone?}
   * @required
   */
  protected array $contact;

  /**
   * @var int|null  WP user ID si disponible
   */
  protected ?int $wp_user_id = NULL;

  /**
   * @var string|null
   */
  protected ?string $notes = NULL;

  public function _run(Result $result): void {
    \CRM_Core_Permission::check('access booking') || \CRM_Core_Error::fatal(ts('Accès refusé.'));

    $service = new BookingService();
    $outcome = $service->book([
      'appointment_type_id' => $this->appointment_type_id,
      'start_datetime'      => $this->start_datetime,
      'contact'             => $this->contact,
      'wp_user_id'          => $this->wp_user_id,
      'notes'               => $this->notes,
    ]);

    if (!$outcome['success']) {
      throw new \CRM_Core_Exception($outcome['error']);
    }

    $result[] = ['id' => $outcome['appointment_id']];
  }
}
