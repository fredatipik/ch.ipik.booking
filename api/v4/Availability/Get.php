<?php
namespace api\v4\Availability;

use CRM\Booking\BAO\Availability;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * APIv4 Availability.Get — disponibilités et exceptions d'un thérapeute.
 */
class Get extends AbstractAction {

  /** @var int @required */
  protected int $therapist_id;

  /** @var bool */
  protected bool $include_exceptions = TRUE;

  public function _run(Result $result): void {
    \CRM_Core_Permission::check('access booking') || \CRM_Core_Error::fatal(ts('Accès refusé.'));

    foreach (Availability::getForTherapist($this->therapist_id) as $slot) {
      $result[] = array_merge($slot, ['record_type' => 'availability']);
    }
    if ($this->include_exceptions) {
      foreach (Availability::getExceptions($this->therapist_id, FALSE) as $exc) {
        $result[] = array_merge($exc, ['record_type' => 'exception']);
      }
    }
  }
}
