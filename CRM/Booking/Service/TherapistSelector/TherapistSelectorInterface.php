<?php
namespace CRM\Booking\Service\TherapistSelector;

/**
 * Interface pour les stratégies d'attribution de intervenant·e.
 */
interface TherapistSelectorInterface {
  /**
   * Choisir un intervenant·e parmi une liste de candidats disponibles.
   * $candidates = résultats de Therapist::getByAppointmentType()
   */
  public function select(array $candidates, string $startDatetime): array;
}
