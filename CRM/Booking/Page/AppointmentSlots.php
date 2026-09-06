<?php
namespace CRM\Booking\Page;

use CRM\Booking\BAO\AppointmentType;
use CRM\Booking\Service\SlotService;
use CRM\Booking\Utils;

/**
 * Créneaux libres d'un·e intervenant·e, au format JSON.
 * Alimente la liste déroulante du formulaire de création en backoffice.
 */
class AppointmentSlots extends \CRM_Core_Page {

  private const HORIZON_DAYS = 60;

  public function run(): void {
    if (!\CRM_Core_Permission::check('access booking')) {
      $this->respond([]);
    }

    $typeId      = (int) \CRM_Utils_Request::retrieve('type_id', 'Positive', $this, FALSE, 0);
    $therapistId = (int) \CRM_Utils_Request::retrieve('therapist_id', 'Positive', $this, FALSE, 0);

    if (!$typeId || !$therapistId) {
      $this->respond([]);
    }

    try {
      $service = new SlotService();
      $from    = date('Y-m-d');
      $to      = date('Y-m-d', strtotime('+' . self::HORIZON_DAYS . ' days'));

      // getAvailableSlots agrège tous les intervenants éligibles ; on filtre
      // ensuite sur celui qui nous intéresse.
      $all  = $service->getAvailableSlots($typeId, $from, $to);
      $mine = [];

      foreach ($all as $slot) {
        if ($service->isSlotAvailable($typeId, $therapistId, $slot)) {
          $mine[] = [
            'value' => $slot,
            'label' => $this->formatSlot($slot),
          ];
        }
        if (count($mine) >= 200) break;
      }

      $this->respond($mine);
    }
    catch (\Throwable $e) {
      Utils::logError('Liste des créneaux', ['error' => $e->getMessage()]);
      $this->respond([]);
    }
  }

  /**
   * « lundi 28 septembre — 09:15 »
   */
  private function formatSlot(string $datetime): string {
    $dt    = new \DateTime($datetime);
    $jours = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
    $mois  = ['', 'janvier', 'février', 'mars', 'avril', 'mai', 'juin',
              'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];

    return sprintf(
      '%s %d %s — %s',
      $jours[(int) $dt->format('w')],
      (int) $dt->format('j'),
      $mois[(int) $dt->format('n')],
      $dt->format('H:i')
    );
  }

  private function respond(array $slots): void {
    \CRM_Utils_System::setHttpHeader('Content-Type', 'application/json; charset=utf-8');
    echo json_encode(['slots' => $slots]);
    \CRM_Utils_System::civiExit();
  }
}
