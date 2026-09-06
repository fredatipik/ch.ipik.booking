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
    \CRM_Core_Permission::check('access booking') || \CRM_Core_Error::statusBounce(ts('Accès refusé.'));
    $this->_therapistId = (int) \CRM_Utils_Request::retrieve('therapist_id', 'Positive', $this, TRUE);
    $this->_contactId   = (int) \CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);
    $this->setTitle(ts('Congé ou créneau exceptionnel'));
    \CRM\Booking\Utils::setBreadCrumb([[
      'title' => ts('Intervenant·es'),
      'url'   => \CRM_Utils_System::url('civicrm/booking/therapists', 'reset=1'),
    ]]);
  }

  public function buildQuickForm(): void {
    // Dates : inputs natifs rendus dans le template (CiviCRM intercepte les id contenant "date")
    $this->add('text', 'date_start', ts('Du'), [], TRUE);
    $this->add('text', 'date_end',   ts('Au (inclus)'), [], TRUE);

    $this->add('select', 'type', ts('Type'), [
      'off'     => ts('Congé / absence — aucun créneau proposé'),
      'special' => ts('Créneau exceptionnel — disponibilité supplémentaire'),
    ], TRUE);

    // Heures : utilisées uniquement si type = special
    $this->add('text', 'start_time', ts('Heure de début'), ['placeholder' => '09:00', 'maxlength' => 5]);
    $this->add('text', 'end_time',   ts('Heure de fin'),   ['placeholder' => '12:00', 'maxlength' => 5]);

    $this->add('text', 'note', ts('Note'), ['maxlength' => 255, 'size' => 40]);

    $this->addButtons([['type' => 'submit', 'name' => ts('Ajouter'), 'isDefault' => TRUE]]);

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
      $this->_errors['date_end'] = ts('La date de fin doit être postérieure ou égale à la date de début.');
    }

    // Créneau exceptionnel : les heures sont obligatoires
    if (($values['type'] ?? '') === 'special') {
      $st = trim($values['start_time'] ?? '');
      $et = trim($values['end_time']   ?? '');
      if (!preg_match('/^\d{2}:\d{2}$/', $st)) {
        $this->_errors['start_time'] = ts('Heure de début requise au format HH:MM.');
      }
      if (!preg_match('/^\d{2}:\d{2}$/', $et)) {
        $this->_errors['end_time'] = ts('Heure de fin requise au format HH:MM.');
      }
      if (!isset($this->_errors['start_time']) && !isset($this->_errors['end_time']) && $st >= $et) {
        $this->_errors['end_time'] = ts('L\'heure de fin doit être postérieure à l\'heure de début.');
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
      $isSpecial ? ts('Créneau exceptionnel ajouté.') : ts('Congé ajouté.'),
      ts('Succès'),
      'success'
    );

    $redirect = $this->_contactId
      ? \CRM_Utils_System::url('civicrm/booking/therapist-agenda', ['cid' => $this->_contactId])
      : \CRM_Utils_System::url('civicrm/booking/therapists');
    \CRM_Utils_System::redirect($redirect);
  }
}
