<?php
namespace CRM\Booking\Form;

use CRM\Booking\BAO\Availability as BAOAvailability;
use CRM\Booking\BAO\Therapist as BAOTherapist;

/**
 * Formulaire — disponibilités récurrentes d'un intervenant·e.
 *
 * Toute la semaine est éditable d'un coup : deux plages horaires possibles
 * par jour (matin / après-midi). Laisser un champ vide retire la plage.
 */
class Availability extends \CRM_Core_Form {

  protected int $_therapistId = 0;
  protected int $_contactId   = 0;

  /** 1 = lundi … 7 = dimanche (0 en base pour dimanche) */
  private const DAYS = [
    1 => 'Lundi',
    2 => 'Mardi',
    3 => 'Mercredi',
    4 => 'Jeudi',
    5 => 'Vendredi',
    6 => 'Samedi',
    0 => 'Dimanche',
  ];

  /** Nombre de plages horaires éditables par jour */
  private const SLOTS_PER_DAY = 2;

  public function preProcess(): void {
    parent::preProcess();
    \CRM_Core_Permission::check('access booking') || \CRM_Core_Error::statusBounce(ts('Accès refusé.'));

    $this->_therapistId = (int) \CRM_Utils_Request::retrieve('therapist_id', 'Positive', $this, TRUE);
    $this->_contactId   = (int) \CRM_Utils_Request::retrieve('cid', 'Positive', $this, FALSE, 0);

    $therapist = BAOTherapist::getById($this->_therapistId);
    if (!$therapist) {
      \CRM_Core_Error::statusBounce(ts('Intervenant·e introuvable.'));
    }

    $this->setTitle(ts('Disponibilités de %1', [1 => $therapist['display_name']]));
    \CRM\Booking\Utils::setBreadCrumb([[
      'title' => ts('Intervenant·es'),
      'url'   => \CRM_Utils_System::url('civicrm/booking/therapists', 'reset=1'),
    ]]);
  }

  public function buildQuickForm(): void {
    $rows = [];
    foreach (self::DAYS as $dow => $label) {
      for ($i = 1; $i <= self::SLOTS_PER_DAY; $i++) {
        $this->add('text', "start_{$dow}_{$i}", NULL, ['class' => 'ipik-time']);
        $this->add('text', "end_{$dow}_{$i}",   NULL, ['class' => 'ipik-time']);
      }
      $rows[] = ['dow' => $dow, 'label' => $label];
    }
    $this->assign('dayRows', $rows);
    $this->assign('slotsPerDay', self::SLOTS_PER_DAY);

    $this->addButtons([['type' => 'submit', 'name' => ts('Enregistrer'), 'isDefault' => TRUE]]);

    $cancelURL = $this->_contactId
      ? \CRM_Utils_System::url('civicrm/booking/therapist-agenda', ['cid' => $this->_contactId])
      : \CRM_Utils_System::url('civicrm/booking/therapists');
    $this->assign('cancelURL', $cancelURL);
  }

  public function setDefaultValues(): array {
    $defaults = [];
    $counters = [];

    foreach (BAOAvailability::getForTherapist($this->_therapistId) as $slot) {
      $dow = (int) $slot['day_of_week'];
      $n   = ($counters[$dow] ?? 0) + 1;
      $counters[$dow] = $n;
      if ($n > self::SLOTS_PER_DAY) continue; // au-delà : ignoré à l'affichage

      $defaults["start_{$dow}_{$n}"] = substr($slot['start_time'], 0, 5);
      $defaults["end_{$dow}_{$n}"]   = substr($slot['end_time'],   0, 5);
    }
    return $defaults;
  }

  public function validate(): bool {
    parent::validate();
    $values = $this->exportValues();

    foreach (self::DAYS as $dow => $label) {
      for ($i = 1; $i <= self::SLOTS_PER_DAY; $i++) {
        $start = trim($values["start_{$dow}_{$i}"] ?? '');
        $end   = trim($values["end_{$dow}_{$i}"]   ?? '');

        if ($start === '' && $end === '') continue;

        if ($start === '' || $end === '') {
          $this->_errors["end_{$dow}_{$i}"] = ts('%1 : indiquez l\'heure de début et de fin.', [1 => $label]);
          continue;
        }
        if (!preg_match('/^\d{1,2}:\d{2}$/', $start) || !preg_match('/^\d{1,2}:\d{2}$/', $end)) {
          $this->_errors["start_{$dow}_{$i}"] = ts('%1 : format attendu HH:MM.', [1 => $label]);
          continue;
        }
        if ($start >= $end) {
          $this->_errors["end_{$dow}_{$i}"] = ts('%1 : l\'heure de fin doit suivre l\'heure de début.', [1 => $label]);
        }
      }
    }
    return empty($this->_errors);
  }

  public function postProcess(): void {
    $values = $this->exportValues();
    $slots  = [];

    foreach (array_keys(self::DAYS) as $dow) {
      for ($i = 1; $i <= self::SLOTS_PER_DAY; $i++) {
        $start = trim($values["start_{$dow}_{$i}"] ?? '');
        $end   = trim($values["end_{$dow}_{$i}"]   ?? '');
        if ($start === '' || $end === '') continue;

        $slots[] = [
          'day_of_week' => $dow,
          'start_time'  => $this->normalizeTime($start),
          'end_time'    => $this->normalizeTime($end),
        ];
      }
    }

    BAOAvailability::saveForTherapist($this->_therapistId, $slots);

    \CRM_Core_Session::setStatus(
      count($slots)
        ? ts('%1 plage(s) horaire(s) enregistrée(s).', [1 => count($slots)])
        : ts('Toutes les disponibilités ont été retirées.'),
      ts('Succès'),
      'success'
    );

    $redirect = $this->_contactId
      ? \CRM_Utils_System::url('civicrm/booking/therapist-agenda', ['cid' => $this->_contactId])
      : \CRM_Utils_System::url('civicrm/booking/therapists');
    \CRM_Utils_System::redirect($redirect);
  }

  /**
   * '9:00' → '09:00:00'
   */
  private function normalizeTime(string $time): string {
    [$h, $m] = array_pad(explode(':', $time), 2, '00');
    return sprintf('%02d:%02d:00', (int) $h, (int) $m);
  }
}
