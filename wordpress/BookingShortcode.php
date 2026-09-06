<?php
namespace CRM\Booking\WordPress;

use CRM\Booking\BAO\AppointmentType;
use CRM\Booking\Service\SlotService;
use CRM\Booking\Service\BookingService;
use CRM\Booking\Utils;

/**
 * BookingShortcode — [ipik_booking] et endpoints AJAX WordPress.
 * Usage : [ipik_booking] ou [ipik_booking type="3"]
 */
class BookingShortcode {

  public static function register(): void {
    add_shortcode('ipik_booking', [self::class, 'render']);
    $actions = ['get_slots', 'check_email', 'submit_booking'];
    foreach ($actions as $action) {
      add_action("wp_ajax_ipik_booking_{$action}",        [self::class, "ajax_{$action}"]);
      add_action("wp_ajax_nopriv_ipik_booking_{$action}", [self::class, "ajax_{$action}"]);
    }
  }

  // -------------------------------------------------------------------------
  // Shortcode HTML
  // -------------------------------------------------------------------------

  public static function render(array $atts): string {
    $atts = shortcode_atts(['type' => ''], $atts);

    $nonce = wp_create_nonce('ipik_booking_nonce');

    wp_enqueue_style('ipik-booking', Utils::resourceUrl() . '/css/booking.css', [], '0.4.16');
    wp_enqueue_script('ipik-booking', Utils::resourceUrl() . '/js/booking-form.js', [], '0.4.16', TRUE);

    // Données utilisateur connecté pour pré-remplissage
    $currentUser = NULL;
    if (is_user_logged_in()) {
      $civiContact = MemberGate::getLoggedInCiviContact();
      if ($civiContact) {
        $currentUser = [
          'first_name' => $civiContact['first_name'] ?? '',
          'last_name'  => $civiContact['last_name']  ?? '',
          'email'      => $civiContact['email']       ?? '',
          'contact_id' => $civiContact['id']          ?? 0,
        ];
      }
    }

    wp_localize_script('ipik-booking', 'ipikBooking', [
      'ajaxUrl'     => admin_url('admin-ajax.php'),
      'nonce'       => $nonce,
      'preType'     => intval($atts['type']),
      'currentUser' => $currentUser,
      'l10n'        => [
        'selectType'      => __('Choisissez un type de rendez-vous', 'ipik-booking'),
        'selectDate'      => __('Sélectionnez une date pour voir les créneaux disponibles.', 'ipik-booking'),
        'selectSlot'      => __('Choisissez un créneau', 'ipik-booking'),
        'noSlots'         => __('Aucun créneau disponible sur cette période.', 'ipik-booking'),
        'loading'         => __('Chargement…', 'ipik-booking'),
        'errorGeneric'    => __('Une erreur est survenue. Veuillez réessayer.', 'ipik-booking'),
        'confirmSuccess'  => __('Votre rendez-vous est confirmé ! Un email de confirmation vous a été envoyé.', 'ipik-booking'),
        'emailRequired'   => __('Veuillez saisir votre email pour vérifier votre éligibilité.', 'ipik-booking'),
        'contactNotFound' => __('Ce type de rendez-vous est réservé aux patients existants. Contactez-nous directement.', 'ipik-booking'),
      ],
    ]);

    $types = AppointmentType::getAll();
    ob_start();
    // Injecter le nonce dans le formulaire HTML via un champ hidden
    echo '<input type="hidden" id="ipik-nonce-value" value="' . esc_attr($nonce) . '" />';
    include dirname(__DIR__) . '/templates/wordpress/booking-form.php';
    return ob_get_clean();
  }

  // -------------------------------------------------------------------------
  // AJAX
  // -------------------------------------------------------------------------

  public static function ajax_get_slots(): void {
    ob_start();
    try {
      self::doGetSlots();
    }
    catch (\Throwable $e) {
      Utils::logError('Erreur fatale dans get_slots', [
        'error' => $e->getMessage(),
        'file'  => $e->getFile() . ':' . $e->getLine(),
      ]);
      self::sendJson(FALSE, ['message' => 'Impossible de charger les créneaux.']);
    }
  }

  private static function doGetSlots(): void {
    self::verifyNonce();
    $typeId   = intval($_POST['type_id'] ?? 0);
    $dateFrom = sanitize_text_field($_POST['date_from'] ?? date('Y-m-d'));
    $dateTo   = sanitize_text_field($_POST['date_to']   ?? date('Y-m-d', strtotime('+30 days')));
    if (!$typeId) self::sendJson(FALSE, ['message' => 'Type invalide.']);

    $slotService = new SlotService();
    $slots = $slotService->getAvailableSlots($typeId, $dateFrom, $dateTo);

    $grouped = [];
    foreach ($slots as $slot) {
      $date = substr($slot, 0, 10);
      $time = substr($slot, 11, 5);
      $grouped[$date][] = ['datetime' => $slot, 'time' => $time];
    }
    self::sendJson(TRUE, ['slots' => $grouped]);
  }

  public static function ajax_check_email(): void {
    ob_start();
    try {
      self::doCheckEmail();
    }
    catch (\Throwable $e) {
      Utils::logError('Erreur fatale dans check_email', ['error' => $e->getMessage()]);
      self::sendJson(FALSE, ['message' => 'Vérification impossible.']);
    }
  }

  private static function doCheckEmail(): void {
    self::verifyNonce();
    $email  = sanitize_email($_POST['email']   ?? '');
    $typeId = intval($_POST['type_id'] ?? 0);
    if (!$email || !$typeId) self::sendJson(FALSE, ['message' => 'Paramètres manquants.']);
    $gate = MemberGate::check($typeId, $email);
    self::sendJson(TRUE, ['allowed' => $gate['allowed'], 'reason' => $gate['reason']]);
  }

  public static function ajax_submit_booking(): void {
    ob_start();
    try {
      self::doSubmitBooking();
    }
    catch (\Throwable $e) {
      Utils::logError('Erreur fatale dans submit_booking', [
        'error' => $e->getMessage(),
        'file'  => $e->getFile() . ':' . $e->getLine(),
      ]);
      self::sendJson(FALSE, ['message' => 'Une erreur technique est survenue. Veuillez réessayer.']);
    }
  }

  private static function doSubmitBooking(): void {
    self::verifyNonce();
    $typeId        = intval($_POST['appointment_type_id'] ?? 0);
    $startDatetime = sanitize_text_field($_POST['start_datetime'] ?? '');
    $firstName     = sanitize_text_field($_POST['first_name']     ?? '');
    $lastName      = sanitize_text_field($_POST['last_name']      ?? '');
    $email         = sanitize_email($_POST['email']               ?? '');
    $phone         = sanitize_text_field($_POST['phone']          ?? '');
    $notes         = sanitize_textarea_field($_POST['notes']      ?? '');

    if (!$typeId || !$startDatetime || !$email) {
      self::sendJson(FALSE, ['message' => 'Données manquantes.']);
    }

    $wpUserId = is_user_logged_in() ? get_current_user_id() : NULL;

    $bookingService = new BookingService();
    $result = $bookingService->book([
      'appointment_type_id' => $typeId,
      'start_datetime'      => $startDatetime,
      'contact'             => [
        'first_name' => $firstName,
        'last_name'  => $lastName,
        'email'      => $email,
        'phone'      => $phone,
      ],
      'wp_user_id' => $wpUserId,
      'notes'      => $notes,
    ]);

    if ($result['success']) {
      self::sendJson(TRUE, ['appointment_id' => $result['appointment_id']]);
    } else {
      self::sendJson(FALSE, ['message' => $result['error']]);
    }
  }

  // -------------------------------------------------------------------------

  /**
   * Envoyer une réponse JSON propre en vidant tout output parasite.
   * Indispensable : CiviCRM peut émettre des warnings PHP qui cassent le JSON.
   */
  private static function sendJson(bool $success, array $data, int $status = 200): void {
    // Jeter uniquement le buffer ouvert par le handler AJAX (warnings, notices).
    // Ne pas toucher aux buffers de WordPress lui-même.
    if (ob_get_level() > 0) {
      $noise = ob_get_clean();
      if ($noise !== '' && $noise !== FALSE) {
        Utils::logError('Sortie parasite avant réponse AJAX', [
          'output' => substr($noise, 0, 300),
        ]);
      }
    }
    if ($status !== 200) {
      status_header($status);
    }
    // wp_send_json gère les en-têtes, l'encodage et la terminaison
    $success ? wp_send_json_success($data) : wp_send_json_error($data);
  }

  private static function verifyNonce(): void {
    $nonce = $_POST['nonce'] ?? $_REQUEST['nonce'] ?? '';
    if (!wp_verify_nonce($nonce, 'ipik_booking_nonce')) {
      self::sendJson(FALSE, ['message' => 'Sécurité : token invalide.'], 403);
    }
  }
}
