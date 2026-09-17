<?php
namespace CRM\Booking\Page;

use CRM\Booking\BAO\AppointmentType;

/**
 * Page — liste des types de rendez-vous (vue admin).
 */
class AppointmentTypeList extends \CRM_Core_Page {

  public function run(): void {
    \CRM_Core_Permission::check('administer booking') || \CRM_Core_Error::statusBounce(ts('Access denied.'));
    \CRM\Booking\Utils::setBreadCrumb();
    $this->assign('types', AppointmentType::getAll(FALSE));
    $this->assign('selectorLabels', [
      'round_robin'  => ts('Round-robin'),
      'least_loaded' => ts('Least busy'),
      'random'       => ts('Random'),
    ]);
    parent::run();
  }
}
