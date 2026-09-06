<?php
namespace CRM\Booking\Page;

use CRM\Booking\BAO\Therapist;

/**
 * Page — liste des intervenant·es (vue admin).
 */
class TherapistList extends \CRM_Core_Page {

  public function run(): void {
    \CRM_Core_Permission::check('administer booking') || \CRM_Core_Error::statusBounce(ts('Accès refusé.'));
    \CRM\Booking\Utils::setBreadCrumb();
    $this->assign('therapists', Therapist::getAll(FALSE));
    parent::run();
  }
}
