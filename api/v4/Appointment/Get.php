<?php
namespace api\v4\Appointment;

use CRM\Booking\BAO\Appointment;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * APIv4 Appointment.Get — liste les rendez-vous selon des filtres.
 *
 * Exemple :
 *   cv api4 Appointment.get '{"therapist_id":2,"date_from":"2025-01-01","date_to":"2025-01-31"}'
 */
class Get extends AbstractAction {

  /**
   * @var int|null
   */
  protected ?int $therapist_id = NULL;

  /**
   * @var int|null
   */
  protected ?int $contact_id = NULL;

  /**
   * @var string|null  YYYY-MM-DD
   */
  protected ?string $date_from = NULL;

  /**
   * @var string|null  YYYY-MM-DD
   */
  protected ?string $date_to = NULL;

  /**
   * @var string|null  confirmed|pending|cancelled|completed
   */
  protected ?string $status = NULL;

  /**
   * @var int
   */
  protected int $limit = 50;

  /**
   * @var int
   */
  protected int $offset = 0;

  public function _run(Result $result): void {
    \CRM_Core_Permission::check('access booking') || \CRM_Core_Error::fatal(ts('Accès refusé.'));

    $filters = array_filter([
      'therapist_id' => $this->therapist_id,
      'contact_id'   => $this->contact_id,
      'date_from'    => $this->date_from,
      'date_to'      => $this->date_to,
      'status'       => $this->status,
    ], fn($v) => $v !== NULL);

    $rows = Appointment::getAll($filters, $this->limit, $this->offset);
    foreach ($rows as $row) {
      $result[] = $row;
    }
  }
}
