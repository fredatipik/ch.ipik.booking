<?php
namespace CRM\Booking\Form;

use CRM\Booking\BAO\Therapist as BAOTherapist;
use CRM\Booking\BAO\AppointmentType;
use CRM\Booking\Utils;

/**
 * Formulaire CiviCRM — création et édition d'un intervenant·e.
 */
class Therapist extends \CRM_Core_Form {

  protected ?array $_therapist = NULL;
  protected int $_id = 0;

  public function preProcess(): void {
    parent::preProcess();
    \CRM_Core_Permission::check('administer booking') || \CRM_Core_Error::statusBounce(ts('Access denied.'));
    $this->_id = (int) \CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    if ($this->_id) {
      $this->_therapist = BAOTherapist::getById($this->_id);
      if (!$this->_therapist) \CRM_Core_Error::statusBounce(ts('Practitioner not found.'));
    }
    $this->setTitle($this->_id ? ts('Edit practitioner') : ts('New practitioner'));
    \CRM\Booking\Utils::setBreadCrumb([[
      'title' => ts('Practitioners'),
      'url'   => \CRM_Utils_System::url('civicrm/booking/therapists', 'reset=1'),
    ]]);
  }

  public function buildQuickForm(): void {
    $this->addEntityRef('contact_id', ts('CiviCRM contact'), [
      'api'         => ['params' => ['contact_type' => 'Individual']],
      'placeholder' => ts('Search for a contact…'),
    ], TRUE);

    $this->add('text', 'wp_user_id', ts('WordPress User ID'), ['size' => 8]);
    $this->add('text', 'color', ts('Calendar colour (hex)'), ['maxlength' => 7], TRUE);
    $this->add('text', 'max_advance_days', ts('Max booking (days in advance)'), ['size' => 5], TRUE);
    $this->addRule('max_advance_days', ts('Positive integer required.'), 'positiveInteger');
    $this->add('text', 'buffer_minutes', ts('Buffer between appointments (minutes)'), ['size' => 5], TRUE);
    $this->addRule('buffer_minutes', ts('Integer required.'), 'integer');
    $this->add('checkbox', 'is_active', ts('Active'));

    // Mode de disponibilité, propre à cet intervenant
    $globalMode = (string) Utils::getSetting('availability_mode', 'weekly');
    $globalLabel = $globalMode === 'workdays'
      ? ts('Declared working days')
      : ts('Weekly schedule');
    $this->add('select', 'availability_mode', ts('Availability mode'), [
      ''         => ts('General setting (%1)', [1 => $globalLabel]),
      'weekly'   => ts('Weekly schedule'),
      'workdays' => ts('Declared working days'),
    ]);

    // Local habituel
    $locations = \CRM\Booking\BAO\Location::getOptions();
    $this->add('select', 'default_location_id', ts('Default room'),
      ['' => ts('— None —')] + $locations);

    // Agenda CalDAV
    $this->add('text', 'calendar_url', ts('CalDAV calendar URL'), [
      'maxlength'   => 512,
      'size'        => 60,
      'placeholder' => 'https://sync.infomaniak.com/calendars/FK03484/aedd0391-...',
    ]);

    // Types de RDV : lecture seule
    $allTypes = AppointmentType::getOptions();
    $assignedTypes = [];
    if ($this->_id) {
      $linkedIds = AppointmentType::getTherapistTypeIds($this->_id);
      foreach ($linkedIds as $tid) {
        if (isset($allTypes[$tid])) $assignedTypes[$tid] = $allTypes[$tid];
      }
    }
    $this->assign('assignedTypes', $assignedTypes);

    // Lien vers la configuration de l'agenda (disponibilités, congés, RDV)
    $this->assign('agendaURL', $this->_therapist
      ? \CRM_Utils_System::url('civicrm/booking/therapist-agenda', [
          'cid' => $this->_therapist['contact_id'], 'reset' => 1,
        ])
      : NULL
    );

    $this->addButtons([['type' => 'submit', 'name' => ts('Save'), 'isDefault' => TRUE]]);
    $this->assign('cancelURL', \CRM_Utils_System::url('civicrm/booking/therapists'));
    $this->assign('recordId', $this->_id);
  }

  public function setDefaultValues(): array {
    if ($this->_therapist) {
      return $this->_therapist;
    }
    return [
      'color'            => '#3b82f6',
      'max_advance_days' => 60,
      'buffer_minutes'   => 0,
      'is_active'        => 1,
    ];
  }

  public function postProcess(): void {
    $values = $this->exportValues();

    $params = [
      'id'               => $this->_id ?: NULL,
      'contact_id'       => (int) $values['contact_id'],
      'wp_user_id'       => !empty($values['wp_user_id']) ? (int) $values['wp_user_id'] : NULL,
      'color'            => $values['color'],
      'max_advance_days' => (int) $values['max_advance_days'],
      'buffer_minutes'   => (int) $values['buffer_minutes'],
      'is_active'        => !empty($values['is_active']) ? 1 : 0,
      'calendar_url'     => trim($values['calendar_url'] ?? ''),
      'availability_mode'=> in_array($values['availability_mode'] ?? '', ['weekly', 'workdays'], TRUE)
        ? $values['availability_mode'] : NULL,
      'default_location_id' => !empty($values['default_location_id'])
        ? (int) $values['default_location_id'] : NULL,
    ];

    BAOTherapist::save($params);

    \CRM_Core_Session::setStatus(ts('Practitioner saved.'), ts('Success'), 'success');
    \CRM_Utils_System::redirect(\CRM_Utils_System::url('civicrm/booking/therapists'));
  }
}
