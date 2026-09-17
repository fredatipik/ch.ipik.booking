<?php
namespace CRM\Booking\Form;

use CRM\Booking\BAO\Availability as BAOAvailability;

/**
 * Formulaire — congé / jour off, ou créneau exceptionnel.
 *
 * type = 'off'     → aucun créneau proposé sur la période
 * type = 'special' → créneaux supplémentaires sur la période (heures requises)
 */
class ExceptionForm extends \CRM_Core_Form {

  protected int $_therapistId = 0;
  protected int $_contactId   = 0;

  public function preProcess(): void {
    parent::preProcess();
    \CRM_Core_Permission::check('access booking') || \CRM_Core_Error::statusBounce(ts('Access denied.'));
    $this->_therapistId = (int) \CRM_Utils_Request::retrieve('therapist_id', 'Positive', $this, TRUE);
    $this->_contactId   = (int) \CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);
    $this->setTitle(ts('Leave or exceptional slot'));
    \CRM\Booking\Utils::setBreadCrumb([[
      'title' => ts('Practitioners'),
      'url'   => \CRM_Utils_System::url('civicrm/booking/therapists', 'reset=1'),
    ]]);
  }

  public function buildQuickForm(): void {
    // Dates : inputs natifs rendus dans le template (CiviCRM intercepte les id contenant "date")
    $this->add('text', 'date_start', ts('From'), [], TRUE);
    $this->add('text', 'date_end',   ts('To (inclusive)'), [], TRUE);

    $this->add('select', 'type', ts('Type'), [
      'off'     => ts('Leave / absence — no slot offered'),
      'special' => ts('Exceptional slot — additional availability'),
    ], TRUE);

    // Heures : utilisées uniquement si type = special
    $this->add('text', 'start_time', ts('Start time'), ['placeholder' => '09:00', 'maxlength' => 5]);
    $this->add('text', 'end_time',   ts('End time'),   ['placeholder' => '12:00', 'maxlength' => 5]);

    $this->add('text', 'note', ts('Note'), ['maxlength' => 255, 'size' => 40]);

    $this->addButtons([['type' => 'submit', 'name' => ts('Add'), 'isDefault' => TRUE]]);

    $cancelURL = $this->_contactId
      ? \CRM_Utils_System::url('civicrm/booking/therapist-agenda', ['cid' => $this->_contactId])
      : \CRM_Utils_System::url('civicrm/booking/therapists');
    $this->assign('cancelURL', $cancelURL);
  }

  public function validate(): bool {
    parent::validate();
    $values = $this->exportValues();

    $start = $values['date_start'] ?? '';
    $end   = $values['date_end']   ?? '';

    if ($start && $end && $start > $end) {
      $this->_errors['date_end'] = ts('The end date must be on or after the start date.');
    }

    // Créneau exceptionnel : les heures sont obligatoires
    if (($values['type'] ?? '') === 'special') {
      $st = trim($values['start_time'] ?? '');
      $et = trim($values['end_time']   ?? '');
      if (!preg_match('/^\d{2}:\d{2}$/', $st)) {
        $this->_errors['start_time'] = ts('Start time required in HH:MM format.');
      }
      if (!preg_match('/^\d{2}:\d{2}$/', $et)) {
        $this->_errors['end_time'] = ts('End time required in HH:MM format.');
      }
      if (!isset($this->_errors['start_time']) && !isset($this->_errors['end_time']) && $st >= $et) {
        $this->_errors['end_time'] = ts('End time must be after start time.');
      }
    }

    return empty($this->_errors);
  }

  public function postProcess(): void {
    $values    = $this->exportValues();
    $isSpecial = ($values['type'] ?? 'off') === 'special';

    BAOAvailability::addException([
      'therapist_id' => $this->_therapistId,
      'date_start'   => $values['date_start'],
      'date_end'     => $values['date_end'],
      'type'         => $values['type'],
      'start_time'   => $isSpecial ? trim($values['start_time']) . ':00' : NULL,
      'end_time'     => $isSpecial ? trim($values['end_time'])   . ':00' : NULL,
      'note'         => $values['note'] ?? NULL,
    ]);

    \CRM_Core_Session::setStatus(
      $isSpecial ? ts('Exceptional slot added.') : ts('Leave added.'),
      ts('Success'),
      'success'
    );

    $redirect = $this->_contactId
      ? \CRM_Utils_System::url('civicrm/booking/therapist-agenda', ['cid' => $this->_contactId])
      : \CRM_Utils_System::url('civicrm/booking/therapists');
    \CRM_Utils_System::redirect($redirect);
  }
}
