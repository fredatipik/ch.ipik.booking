<?php
namespace CRM\Booking\Service;

use CRM\Booking\Utils;

/**
 * MessageService — textes du formulaire public.
 *
 * Ces quelques phrases s'adressent directement aux patients : chaque cabinet
 * a sa façon de les formuler. Elles sont donc modifiables depuis les
 * paramètres de l'extension, sans toucher au code.
 *
 * Un message laissé vide reprend le texte livré par défaut.
 */
class MessageService {

  public const EMAIL_GATE       = 'msg_email_gate';
  public const CONTACT_NOT_FOUND = 'msg_contact_not_found';
  public const NO_SLOTS         = 'msg_no_slots';
  public const CONFIRMATION     = 'msg_confirmation';

  /**
   * Définition des messages : libellé pour les paramètres, texte par défaut,
   * et brève indication du moment où le patient le lit.
   */
  public static function definitions(): array {
    return [
      self::EMAIL_GATE => [
        'label'   => ts('Email address verification'),
        'help'    => ts('Displayed for an appointment type reserved for existing patients, before selecting a slot.'),
        'default' => ts('This appointment type is reserved for existing patients. Please enter your email address to continue.'),
      ],
      self::CONTACT_NOT_FOUND => [
        'label'   => ts('Address not recognised'),
        'help'    => ts('Affiché lorsque l\'adresse saisie ne correspond à aucun patient enregistré.'),
        'default' => ts('Nous ne retrouvons pas cette adresse. Si vous êtes déjà suivi·e chez nous, contactez-nous directement pour convenir d\'un rendez-vous.'),
      ],
      self::NO_SLOTS => [
        'label'   => ts('No slot available'),
        'help'    => ts('Displayed when the selected period has no availability.'),
        'default' => ts('No slot available for this period.'),
      ],
      self::CONFIRMATION => [
        'label'   => ts('Booking confirmation'),
        'help'    => ts('Displayed after the appointment is saved.'),
        'default' => ts('A confirmation email has been sent. See you soon!'),
      ],
    ];
  }

  /**
   * Texte configuré, ou texte par défaut si aucun n'a été saisi.
   */
  public static function get(string $key): string {
    $custom = trim((string) Utils::getSetting($key, ''));
    if ($custom !== '') {
      return $custom;
    }

    $definitions = self::definitions();
    return $definitions[$key]['default'] ?? '';
  }

  /**
   * Tous les messages, prêts à être transmis au formulaire public.
   *
   * @return array<string, string>
   */
  public static function all(): array {
    $messages = [];
    foreach (array_keys(self::definitions()) as $key) {
      $messages[$key] = self::get($key);
    }
    return $messages;
  }
}
