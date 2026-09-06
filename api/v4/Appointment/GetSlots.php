<?php
namespace api\v4\Appointment;

use CRM\Booking\Service\SlotService;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * APIv4 Appointment.GetSlots — retourne les créneaux disponibles pour un type de RDV.
 *
 * Utilisable par n8n, un frontend headless, ou tout client externe.
 *
 * Exemple :
 *   cv api4 Appointment.getSlots '{"appointment_type_id":1,"date_from":"2025-06-01","date_to":"2025-06-30"}'
 */
class GetSlots extends AbstractAction {

  /** @var int @required */
  protected int $appointment_type_id;

  /** @var string  YYYY-MM-DD @required */
  protected string $date_from;

  /** @var string  YYYY-MM-DD @required */
  protected string $date_to;

  public function _run(Result $result): void {
    $service = new SlotService();
    $slots   = $service->getAvailableSlots(
      $this->appointment_type_id,
      $this->date_from,
      $this->date_to
    );
    foreach ($slots as $slot) {
      $result[] = ['datetime' => $slot, 'date' => substr($slot, 0, 10), 'time' => substr($slot, 11, 5)];
    }
  }
}
