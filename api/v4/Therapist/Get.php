<?php
namespace api\v4\Therapist;

use CRM\Booking\BAO\Therapist;
use Civi\Api4\Generic\AbstractAction;
use Civi\Api4\Generic\Result;

/**
 * APIv4 Therapist.Get — liste les thérapeutes actifs.
 */
class Get extends AbstractAction {

  /** @var bool */
  protected bool $active_only = TRUE;

  public function _run(Result $result): void {
    \CRM_Core_Permission::check('access booking') || \CRM_Core_Error::fatal(ts('Accès refusé.'));
    foreach (Therapist::getAll($this->active_only) as $therapist) {
      $result[] = $therapist;
    }
  }
}
