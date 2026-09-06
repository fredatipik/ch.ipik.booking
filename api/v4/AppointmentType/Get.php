<?php
namespace api\v4\AppointmentType;

use CRM\Booking\BAO\AppointmentType;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * APIv4 AppointmentType.Get — liste les types de rendez-vous actifs.
 */
class Get extends AbstractAction {

  /** @var bool */
  protected bool $active_only = TRUE;

  public function _run(Result $result): void {
    foreach (AppointmentType::getAll($this->active_only) as $type) {
      $result[] = $type;
    }
  }
}
