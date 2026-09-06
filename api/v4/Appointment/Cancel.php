<?php
namespace api\v4\Appointment;

use CRM\Booking\BAO\Appointment;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * APIv4 Appointment.Cancel
 */
class Cancel extends AbstractAction {

  /** @var int @required */
  protected int $id;

  /** @var string */
  protected string $reason = '';

  public function _run(Result $result): void {
    \CRM_Core_Permission::check('access booking') || \CRM_Core_Error::fatal(ts('Accès refusé.'));
    Appointment::cancel($this->id, $this->reason);
    $result[] = ['id' => $this->id, 'status' => 'cancelled'];
  }
}
