<?php
namespace CRM\Booking\Form;

use CRM\Booking\BAO\Location;
use CRM\Booking\Utils;

/**
 * Formulaire — création et édition d'un local.
 *
 * Un local possède son propre agenda CalDAV : lorsqu'un rendez-vous s'y tient,
 * le créneau y est bloqué pour tous les intervenants.
 */
class LocationForm extends \CRM_Core_Form {

  protected ?array $_location = NULL;
  protected int $_id = 0;

  public function preProcess(): void {
    parent::preProcess();
    \CRM_Core_Permission::check('administer booking')
      || \CRM_Core_Error::statusBounce(ts('Accès refusé.'));

    $this->_id = (int) \CRM_Utils_Request::retrieve('id', 'Positive', $this, FALSE, 0);
    if ($this->_id) {
      $this->_location = Location::getById($this->_id);
      if (!$this->_location) {
        \CRM_Core_Error::statusBounce(ts('Local introuvable.'));
      }
    }

    $this->setTitle($this->_id ? ts('Modifier le local') : ts('Nouveau local'));
    Utils::setBreadCrumb([[
      'title' => ts('Locaux'),
      'url'   => \CRM_Utils_System::url('civicrm/booking/locations', 'reset=1'),
    ]]);
  }

  public function buildQuickForm(): void {
    $this->add('text', 'name', ts('Nom'), ['maxlength' => 255, 'size' => 40], TRUE);
    $this->add('text', 'address', ts('Adresse'), ['maxlength' => 512, 'size' => 50]);
    $this->add('text', 'color', ts('Couleur'), ['maxlength' => 7], TRUE);
    $this->add('text', 'calendar_url', ts('URL agenda CalDAV'), [
      'maxlength'   => 512,
      'size'        => 60,
      'placeholder' => 'https://sync.infomaniak.com/calendars/FK03484/…',
    ]);
    $this->add('text', 'weight', ts('Ordre d\'affichage'), ['size' => 4]);
    $this->add('checkbox', 'is_active', ts('Actif'));

    $this->addButtons([['type' => 'submit', 'name' => ts('Enregistrer'), 'isDefault' => TRUE]]);
    $this->assign('cancelURL', \CRM_Utils_System::url('civicrm/booking/locations', 'reset=1'));
    $this->assign('recordId', $this->_id);
  }

  public function setDefaultValues(): array {
    if ($this->_location) {
      return $this->_location;
    }
    return [
      'color'     => '#8b5cf6',
      'is_active' => 1,
      'weight'    => 0,
    ];
  }

  public function postProcess(): void {
    $values = $this->exportValues();

    Location::save([
      'id'           => $this->_id ?: NULL,
      'name'         => $values['name'],
      'address'      => $values['address'] ?? '',
      'color'        => $values['color'],
      'calendar_url' => $values['calendar_url'] ?? '',
      'weight'       => (int) ($values['weight'] ?? 0),
      'is_active'    => !empty($values['is_active']) ? 1 : 0,
    ]);

    \CRM_Core_Session::setStatus(ts('Local enregistré.'), ts('Succès'), 'success');
    \CRM_Utils_System::redirect(\CRM_Utils_System::url('civicrm/booking/locations', 'reset=1'));
  }
}
