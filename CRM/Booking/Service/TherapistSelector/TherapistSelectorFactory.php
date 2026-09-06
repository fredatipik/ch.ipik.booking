<?php
namespace CRM\Booking\Service\TherapistSelector;

use CRM\Booking\Utils;

/**
 * Factory — retourne le sélecteur configuré (global ou par type de RDV).
 */
class TherapistSelectorFactory {

  public static function create(?string $strategy = NULL): TherapistSelectorInterface {
    $strategy = $strategy ?? Utils::getSetting('therapist_selector', 'round_robin');
    return match ($strategy) {
      'least_loaded' => new LeastLoadedSelector(),
      'random'       => new RandomSelector(),
      default        => new RoundRobinSelector(),
    };
  }
}

// ---------------------------------------------------------------------------

/**
 * Round-robin : rotation équitable basée sur le dernier RDV attribué.
 */
class RoundRobinSelector implements TherapistSelectorInterface {

  public function select(array $candidates, string $startDatetime): array {
    if (count($candidates) === 1) return $candidates[0];

    // Trouver le intervenant·e dont le dernier RDV attribué est le plus ancien
    $lastApptByTherapist = [];
    foreach ($candidates as $t) {
      $lastApptByTherapist[$t['id']] = \CRM_Core_DAO::singleValueQuery(
        "SELECT MAX(start_datetime) FROM civicrm_booking_appointment
         WHERE therapist_id = %1 AND status != 'cancelled'",
        [1 => [(int) $t['id'], 'Integer']]
      ) ?? '1970-01-01';
    }
    asort($lastApptByTherapist); // Le plus ancien en premier
    $selectedId = array_key_first($lastApptByTherapist);
    return self::findById($candidates, $selectedId);
  }

  private static function findById(array $candidates, int $id): array {
    foreach ($candidates as $c) {
      if ((int) $c['id'] === $id) return $c;
    }
    return $candidates[0];
  }
}

// ---------------------------------------------------------------------------

/**
 * Least loaded : le intervenant·e avec le moins de RDV dans les 7 prochains jours.
 */
class LeastLoadedSelector implements TherapistSelectorInterface {

  public function select(array $candidates, string $startDatetime): array {
    if (count($candidates) === 1) return $candidates[0];

    $dateFrom = date('Y-m-d', strtotime($startDatetime));
    $dateTo   = date('Y-m-d', strtotime($startDatetime . ' +7 days'));

    $loadByTherapist = [];
    foreach ($candidates as $t) {
      $loadByTherapist[$t['id']] = (int) \CRM_Core_DAO::singleValueQuery(
        "SELECT COUNT(*) FROM civicrm_booking_appointment
         WHERE therapist_id = %1
           AND start_datetime BETWEEN %2 AND %3
           AND status != 'cancelled'",
        [
          1 => [(int) $t['id'], 'Integer'],
          2 => [$dateFrom . ' 00:00:00', 'String'],
          3 => [$dateTo . ' 23:59:59', 'String'],
        ]
      );
    }
    asort($loadByTherapist);
    $selectedId = array_key_first($loadByTherapist);

    foreach ($candidates as $c) {
      if ((int) $c['id'] === $selectedId) return $c;
    }
    return $candidates[0];
  }
}

// ---------------------------------------------------------------------------

/**
 * Random : attribution aléatoire parmi les disponibles.
 */
class RandomSelector implements TherapistSelectorInterface {

  public function select(array $candidates, string $startDatetime): array {
    if (count($candidates) === 1) return $candidates[0];
    return $candidates[array_rand($candidates)];
  }
}
