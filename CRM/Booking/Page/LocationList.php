<?php
namespace CRM\Booking\Page;

use CRM\Booking\BAO\Location;
use CRM\Booking\Utils;

/**
 * Page LocationList — liste des locaux.
 */
class LocationList extends \CRM_Core_Page {

  public function run(): void {
    \CRM_Core_Permission::check('administer booking')
      || \CRM_Core_Error::statusBounce(ts('Accès refusé.'));

    Utils::setBreadCrumb();
    $this->assign('locations', Location::getAll(FALSE));

    parent::run();
  }
}
