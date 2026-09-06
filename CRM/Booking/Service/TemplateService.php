<?php
namespace CRM\Booking\Service;

use CRM\Booking\Utils;

/**
 * TemplateService — création et récupération des modèles d'email.
 *
 * Les modèles sont de vrais MessageTemplate CiviCRM, créés à l'installation
 * et modifiables dans Administration → Communications → Modèles de messages.
 * Leur identifiant est mémorisé dans civicrm_booking_settings, ce qui permet
 * de les retrouver même après un renommage par l'utilisateur.
 */
class TemplateService {

  public const CONFIRMATION_CLIENT    = 'confirmation_client';
  public const CONFIRMATION_THERAPIST = 'confirmation_therapist';
  public const REMINDER_CLIENT        = 'reminder_client';
  public const CANCELLATION           = 'cancellation';

  /**
   * Définition des modèles livrés avec l'extension.
   */
  public static function definitions(): array {
    return [
      self::CONFIRMATION_CLIENT => [
        'title'   => 'Booking — Confirmation de rendez-vous (patient)',
        'subject' => 'Confirmation de votre rendez-vous du {booking.date}',
        'html'    => self::htmlConfirmationClient(),
      ],
      self::CONFIRMATION_THERAPIST => [
        'title'   => 'Booking — Nouveau rendez-vous (intervenant·e)',
        'subject' => 'Nouveau rendez-vous — {booking.date} à {booking.time}',
        'html'    => self::htmlConfirmationTherapist(),
      ],
      self::REMINDER_CLIENT => [
        'title'   => 'Booking — Rappel de rendez-vous (patient)',
        'subject' => 'Rappel : votre rendez-vous du {booking.date}',
        'html'    => self::htmlReminderClient(),
      ],
      self::CANCELLATION => [
        'title'   => 'Booking — Annulation de rendez-vous',
        'subject' => 'Annulation du rendez-vous du {booking.date}',
        'html'    => self::htmlCancellation(),
      ],
    ];
  }

  /**
   * Créer les modèles manquants. Idempotent : ne touche pas à ceux qui existent.
   */
  public static function installTemplates(): void {
    foreach (array_keys(self::definitions()) as $key) {
      self::installTemplate($key);
    }
  }

  /**
   * Créer un modèle s'il n'existe pas encore. Idempotent.
   */
  public static function installTemplate(string $key): void {
    $definitions = self::definitions();
    if (!isset($definitions[$key])) return;

    $settingKey = 'template_id_' . $key;
    $existingId = (int) Utils::getSetting($settingKey, 0);

    if ($existingId && self::templateExists($existingId)) {
      return;
    }

    $def = $definitions[$key];
    try {
      $result = \civicrm_api3('MessageTemplate', 'create', [
        'msg_title'   => $def['title'],
        'msg_subject' => $def['subject'],
        'msg_html'    => $def['html'],
        'msg_text'    => self::htmlToText($def['html']),
        'is_active'   => 1,
      ]);
      Utils::setSetting($settingKey, (int) $result['id']);
    }
    catch (\Throwable $e) {
      Utils::logError('Création du modèle de message échouée', [
        'template' => $key,
        'error'    => $e->getMessage(),
      ]);
    }
  }

  /**
   * Récupérer un modèle : ['subject' => …, 'html' => …, 'text' => …]
   * Retourne NULL si le modèle n'existe pas (le service d'envoi a un repli).
   */
  public static function getTemplate(string $key): ?array {
    $id = (int) Utils::getSetting('template_id_' . $key, 0);

    // Créer le modèle s'il n'a jamais été installé ou s'il a été supprimé
    if (!$id || !self::templateExists($id)) {
      self::installTemplate($key);
      $id = (int) Utils::getSetting('template_id_' . $key, 0);
    }
    if (!$id) return NULL;

    try {
      $tpl = \civicrm_api3('MessageTemplate', 'getsingle', [
        'id'     => $id,
        'return' => ['msg_subject', 'msg_html', 'msg_text'],
      ]);
      return [
        'subject' => $tpl['msg_subject'] ?? '',
        'html'    => $tpl['msg_html']    ?? '',
        'text'    => $tpl['msg_text']    ?? '',
      ];
    }
    catch (\Throwable $e) {
      return NULL;
    }
  }

  /**
   * Supprimer les modèles à la désinstallation.
   */
  public static function removeTemplates(): void {
    foreach (array_keys(self::definitions()) as $key) {
      $id = (int) Utils::getSetting('template_id_' . $key, 0);
      if (!$id) continue;
      try {
        \civicrm_api3('MessageTemplate', 'delete', ['id' => $id]);
      }
      catch (\Throwable $e) {}
    }
  }

  /**
   * Liste des modèles avec leur URL d'édition, pour la page Paramètres.
   */
  public static function getTemplateLinks(): array {
    $links = [];
    foreach (self::definitions() as $key => $def) {
      $id = (int) Utils::getSetting('template_id_' . $key, 0);
      if (!$id || !self::templateExists($id)) {
        self::installTemplate($key);
        $id = (int) Utils::getSetting('template_id_' . $key, 0);
      }
      $links[] = [
        'key'    => $key,
        'title'  => $def['title'],
        'id'     => $id,
        'exists' => $id > 0 && self::templateExists($id),
        'url'    => $id
          ? \CRM_Utils_System::url('civicrm/admin/messageTemplates/add', "action=update&id={$id}&reset=1")
          : NULL,
      ];
    }
    return $links;
  }

  // -------------------------------------------------------------------------

  private static function templateExists(int $id): bool {
    try {
      return (int) \civicrm_api3('MessageTemplate', 'getcount', ['id' => $id]) > 0;
    }
    catch (\Throwable $e) {
      return FALSE;
    }
  }

  private static function htmlToText(string $html): string {
    $text = str_replace(['</p>', '</tr>', '<br>', '<br/>', '<br />'], "\n", $html);
    $text = strip_tags($text);
    $text = preg_replace("/\n{3,}/", "\n\n", $text);
    return trim($text);
  }

  // -------------------------------------------------------------------------
  // Contenus par défaut
  // -------------------------------------------------------------------------

  private static function htmlConfirmationClient(): string {
    return <<<'HTML'
<p>Bonjour {contact.first_name},</p>

<p>Votre rendez-vous est confirmé :</p>

<table cellpadding="6" style="border-collapse:collapse">
  <tr><td><strong>Type</strong></td><td>{booking.type}</td></tr>
  <tr><td><strong>Date</strong></td><td>{booking.date}</td></tr>
  <tr><td><strong>Heure</strong></td><td>{booking.time}</td></tr>
  <tr><td><strong>Durée</strong></td><td>{booking.duration} minutes</td></tr>
  <tr><td><strong>Lieu</strong></td><td>{booking.location}</td></tr>
</table>

<p>Si vous devez annuler ou déplacer ce rendez-vous, merci de nous prévenir dès que possible.</p>

<p>À bientôt,<br>L'équipe du cabinet</p>
HTML;
  }

  private static function htmlConfirmationTherapist(): string {
    return <<<'HTML'
<p>Bonjour,</p>

<p>Un nouveau rendez-vous a été enregistré dans votre agenda :</p>

<table cellpadding="6" style="border-collapse:collapse">
  <tr><td><strong>Type</strong></td><td>{booking.type}</td></tr>
  <tr><td><strong>Date</strong></td><td>{booking.date}</td></tr>
  <tr><td><strong>Heure</strong></td><td>{booking.time}</td></tr>
  <tr><td><strong>Durée</strong></td><td>{booking.duration} minutes</td></tr>
  <tr><td><strong>Patient</strong></td><td>{booking.contact_name}</td></tr>
  <tr><td><strong>Notes</strong></td><td>{booking.notes}</td></tr>
</table>
HTML;
  }

  private static function htmlCancellation(): string {
    return <<<'HTML'
<p>Bonjour,</p>

<p>Le rendez-vous suivant a été <strong>annulé</strong> :</p>

<table cellpadding="6" style="border-collapse:collapse">
  <tr><td><strong>Type</strong></td><td>{booking.type}</td></tr>
  <tr><td><strong>Date</strong></td><td>{booking.date}</td></tr>
  <tr><td><strong>Heure</strong></td><td>{booking.time}</td></tr>
  <tr><td><strong>Patient</strong></td><td>{booking.contact_name}</td></tr>
  <tr><td><strong>Intervenant·e</strong></td><td>{booking.therapist}</td></tr>
</table>

<p>{booking.cancel_reason}</p>

<p>Pour reprendre rendez-vous, n'hésitez pas à nous contacter.</p>
HTML;
  }

  private static function htmlReminderClient(): string {
    return <<<'HTML'
<p>Bonjour {contact.first_name},</p>

<p>Petit rappel : vous avez rendez-vous <strong>{booking.date}</strong> à <strong>{booking.time}</strong> pour {booking.type}.</p>

<p>À bientôt,<br>L'équipe du cabinet</p>
HTML;
  }
}
