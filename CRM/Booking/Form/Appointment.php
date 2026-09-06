<?php
namespace CRM\Booking\Form;

use CRM\Booking\BAO\Appointment as BAOAppointment;
use CRM\Booking\BAO\AppointmentType;
use CRM\Booking\BAO\Therapist;
use CRM\Booking\Service\BookingService;
use CRM\Booking\Service\SlotService;
use CRM\Booking\Utils;

/**
 * Formulaire — création d'un rendez-vous depuis le backoffice.
 *
 * Deux façons de fixer l'horaire : choisir un créneau libre parmi ceux que
 * propose le calcul de disponibilité, ou saisir date et heure directement.
 * La saisie libre permet de caler un rendez-vous en dehors des plages
 * déclarées — utile au téléphone, pour une urgence.
 */
class Appointment extends \CRM_Core_Form {

  /** Nombre de jours explorés pour proposer des créneaux */
  private const HORIZON_DAYS = 60;

  protected int $_therapistId = 0;
  protected int $_contactId   = 0;

  public function preProcess(): void {
    parent::preProcess();
    \CRM_Core_Permission::check('access booking')
      || \CRM_Core_Error::statusBounce(ts('Accès refusé.'));

    // Pré-sélection possible depuis l'agenda d'un·e intervenant·e
    $this->_therapistId = (int) \CRM_Utils_Request::retrieve('therapist_id', 'Positive', $this, FALSE, 0);
    $this->_contactId   = (int) \CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);

    $this->setTitle(ts('Nouveau rendez-vous'));
    Utils::setBreadCrumb();
  }

  public function buildQuickForm(): void {
    // ---- Patient ----
    $this->addEntityRef('contact_id', ts('Patient·e'), [
      'api'         => ['params' => ['contact_type' => 'Individual']],
      'create'      => TRUE,
      'placeholder' => ts('Rechercher ou créer un contact…'),
    ], TRUE);

    // ---- Type de rendez-vous ----
    $types = AppointmentType::getOptions();
    $this->add('select', 'appointment_type_id', ts('Type de rendez-vous'),
      ['' => ts('— Choisir —')] + $types, TRUE);

    // ---- Intervenant·e ----
    $therapists = [];
    foreach (Therapist::getAll() as $t) {
      $therapists[$t['id']] = $t['display_name'];
    }
    $this->add('select', 'therapist_id', ts('Intervenant·e'),
      ['' => ts('— Choisir —')] + $therapists, TRUE);

    // ---- Horaire ----
    $this->add('select', 'slot_mode', ts('Horaire'), [
      'free'   => ts('Choisir parmi les créneaux disponibles'),
      'manual' => ts('Saisir une date et une heure'),
    ], TRUE);

    // Créneaux libres, alimentés en JavaScript
    $this->add('select', 'slot', ts('Créneau'), ['' => ts('— Choisir un type et un·e intervenant·e —')]);

    // Saisie libre
    $this->add('text', 'manual_date', ts('Date'));
    $this->add('text', 'manual_time', ts('Heure'));

    // Local : déduit par défaut, modifiable explicitement
    $locations = \CRM\Booking\BAO\Location::getOptions();
    $this->add('select', 'location_id', ts('Local'),
      ['' => ts('— Déduire automatiquement —'), '0' => ts('Hors local')] + $locations);

    $this->add('textarea', 'notes', ts('Notes'), ['rows' => 3, 'maxlength' => 500]);

    $this->add('checkbox', 'send_notifications', ts('Envoyer les e-mails de confirmation'));

    $this->addButtons([['type' => 'submit', 'name' => ts('Créer le rendez-vous'), 'isDefault' => TRUE]]);

    $this->assign('slotsURL', \CRM_Utils_System::url('civicrm/booking/appointment/slots'));
    $this->assign('cancelURL', $this->returnUrl());
  }

  public function setDefaultValues(): array {
    $defaults = [
      'slot_mode'          => 'free',
      'send_notifications' => 1,
    ];

    // Intervenant·e passé·e en paramètre, sinon la personne connectée
    if ($this->_therapistId) {
      $defaults['therapist_id'] = $this->_therapistId;
    }
    else {
      $loggedIn = (int) \CRM_Core_Session::getLoggedInContactID();
      $self     = $loggedIn ? Therapist::getByContactId($loggedIn) : NULL;
      if ($self) {
        $defaults['therapist_id'] = $self['id'];
      }
    }

    return $defaults;
  }

  public function validate(): bool {
    parent::validate();
    $values = $this->exportValues();

    if (($values['slot_mode'] ?? 'free') === 'free') {
      if (empty($values['slot'])) {
        $this->_errors['slot'] = ts('Choisissez un créneau, ou passez en saisie libre.');
      }
      return empty($this->_errors);
    }

    // Saisie libre
    $date = trim($values['manual_date'] ?? '');
    $time = trim($values['manual_time'] ?? '');

    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
      $this->_errors['manual_date'] = ts('Date attendue au format AAAA-MM-JJ.');
    }
    if (!preg_match('/^\d{1,2}:\d{2}$/', $time)) {
      $this->_errors['manual_time'] = ts('Heure attendue au format HH:MM.');
    }

    return empty($this->_errors);
  }

  public function postProcess(): void {
    $values    = $this->exportValues();
    $contactId = (int) $values['contact_id'];
    $typeId    = (int) $values['appointment_type_id'];
    $therapId  = (int) $values['therapist_id'];

    $start = ($values['slot_mode'] ?? 'free') === 'free'
      ? $values['slot']
      : sprintf('%s %s:00', $values['manual_date'], $this->padTime($values['manual_time']));

    $type = AppointmentType::getById($typeId);
    if (!$type) {
      \CRM_Core_Session::setStatus(ts('Type de rendez-vous introuvable.'), ts('Erreur'), 'error');
      \CRM_Utils_System::redirect($this->returnUrl());
    }

    $end = (new \DateTime($start))
      ->modify('+' . (int) $type['duration_minutes'] . ' minutes')
      ->format('Y-m-d H:i:s');

    // Local : choix explicite s'il y en a un, sinon déduction habituelle
    $chosen = $values['location_id'] ?? '';
    if ($chosen === '0') {
      $locationId = NULL;             // « Hors local » demandé explicitement
    }
    elseif ($chosen !== '') {
      $locationId = (int) $chosen;
    }
    else {
      $slotService = new SlotService();
      $locationId  = $slotService->locationForDate($therapId, substr($start, 0, 10));
    }

    $appointmentId = BAOAppointment::create([
      'appointment_type_id' => $typeId,
      'therapist_id'        => $therapId,
      'contact_id'          => $contactId,
      'start_datetime'      => $start,
      'end_datetime'        => $end,
      'status'              => BAOAppointment::STATUS_CONFIRMED,
      'notes'               => $values['notes'] ?? NULL,
      'location_id'         => $locationId,
    ]);

    $appointment = BAOAppointment::getById($appointmentId);

    // Agendas externes et notifications, via le service commun
    $booking = new BookingService();
    $booking->syncExisting($appointmentId, $appointment, $locationId);

    if (!empty($values['send_notifications'])) {
      $booking->notifyExisting($appointment, $contactId);
    }

    \CRM_Core_Session::setStatus(
      ts('Rendez-vous du %1 créé.', [1 => Utils::formatDatetime($start)]),
      ts('Succès'),
      'success'
    );
    \CRM_Utils_System::redirect($this->returnUrl());
  }

  // -------------------------------------------------------------------------

  /**
   * '9:00' → '09:00'
   */
  private function padTime(string $time): string {
    [$h, $m] = array_pad(explode(':', trim($time)), 2, '00');
    return sprintf('%02d:%02d', (int) $h, (int) $m);
  }

  private function returnUrl(): string {
    return $this->_contactId
      ? \CRM_Utils_System::url('civicrm/booking/therapist-agenda', ['cid' => $this->_contactId, 'reset' => 1])
      : \CRM_Utils_System::url('civicrm/booking/appointments', 'reset=1');
  }
}
