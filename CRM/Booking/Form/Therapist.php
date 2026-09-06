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
    \CRM_Core_Permission::check('administer booking') || \CRM_Core_Error::statusBounce(ts('Accès refusé.'));
    $this->_id = (int) \CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    if ($this->_id) {
      $this->_therapist = BAOTherapist::getById($this->_id);
      if (!$this->_therapist) \CRM_Core_Error::statusBounce(ts('Intervenant·e introuvable.'));
    }
    $this->setTitle($this->_id ? ts('Modifier l\'intervenant·e') : ts('Nouvel·le intervenant·e'));
    \CRM\Booking\Utils::setBreadCrumb([[
      'title' => ts('Intervenant·es'),
      'url'   => \CRM_Utils_System::url('civicrm/booking/therapists', 'reset=1'),
    ]]);
  }

  public function buildQuickForm(): void {
    $this->addEntityRef('contact_id', ts('Contact CiviCRM'), [
      'api'         => ['params' => ['contact_type' => 'Individual']],
      'placeholder' => ts('Rechercher un contact…'),
    ], TRUE);

    $this->add('text', 'wp_user_id', ts('WordPress User ID'), ['size' => 8]);
    $this->add('text', 'color', ts('Couleur agenda (hex)'), ['maxlength' => 7], TRUE);
    $this->add('text', 'max_advance_days', ts('Réservation max (jours à l\'avance)'), ['size' => 5], TRUE);
    $this->addRule('max_advance_days', ts('Entier positif requis.'), 'positiveInteger');
    $this->add('text', 'buffer_minutes', ts('Tampon entre RDV (minutes)'), ['size' => 5], TRUE);
    $this->addRule('buffer_minutes', ts('Entier requis.'), 'integer');
    $this->add('checkbox', 'is_active', ts('Actif'));

    // Mode de disponibilité, propre à cet intervenant
    $globalMode = (string) Utils::getSetting('availability_mode', 'weekly');
    $globalLabel = $globalMode === 'workdays'
      ? ts('Jours de travail déclarés')
      : ts('Horaires hebdomadaires');
    $this->add('select', 'availability_mode', ts('Mode de disponibilité'), [
      ''         => ts('Réglage général (%1)', [1 => $globalLabel]),
      'weekly'   => ts('Horaires hebdomadaires'),
      'workdays' => ts('Jours de travail déclarés'),
    ]);

    // Local habituel
    $locations = \CRM\Booking\BAO\Location::getOptions();
    $this->add('select', 'default_location_id', ts('Local habituel'),
      ['' => ts('— Aucun —')] + $locations);

    // Agenda CalDAV
    $this->add('text', 'calendar_url', ts('URL agenda CalDAV'), [
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

    $this->addButtons([['type' => 'submit', 'name' => ts('Enregistrer'), 'isDefault' => TRUE]]);
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

    \CRM_Core_Session::setStatus(ts('Intervenant·e enregistré·e.'), ts('Succès'), 'success');
    \CRM_Utils_System::redirect(\CRM_Utils_System::url('civicrm/booking/therapists'));
  }
}
