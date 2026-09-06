<?php
namespace api\v4\Appointment;

use CRM\Booking\BAO\Appointment;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * APIv4 Appointment.Complete — marque un RDV comme terminé.
 */
class Complete extends AbstractAction {

  /** @var int @required */
  protected int $id;

  public function _run(Result $result): void {
    \CRM_Core_Permission::check('access booking') || \CRM_Core_Error::fatal(ts('Accès refusé.'));
    Appointment::complete($this->id);
    $result[] = ['id' => $this->id, 'status' => 'completed'];
  }
}
