<?php
namespace CRM\Booking\Service\CalendarProvider;

/**
 * Accès à un calendrier externe.
 *
 * Deux familles de méthodes :
 *   - celles qui prennent un identifiant d'intervenant, pour l'agenda personnel
 *   - celles qui prennent une URL, pour un agenda quelconque (local, ressource)
 */
interface CalendarProviderInterface {

  /**
   * Le fournisseur est-il configuré et utilisable ?
   */
  public function isAvailable(): bool;

  /**
   * Créneaux occupés dans l'agenda d'un intervenant.
   *
   * @return array<array{start: string, end: string}> Datetimes 'Y-m-d H:i:s'.
   */
  public function getBusySlots(int $therapistId, string $dateFrom, string $dateTo): array;

  /**
   * Créneaux occupés dans un agenda quelconque, désigné par son URL.
   *
   * @return array<array{start: string, end: string}>
   */
  public function getBusySlotsForUrl(string $calendarUrl, string $dateFrom, string $dateTo): array;

  /**
   * Créer un événement dans l'agenda d'un intervenant.
   *
   * @return string|null UID de l'événement, NULL en cas d'échec.
   */
  public function createEvent(array $appointment): ?string;

  /**
   * Créer un événement dans un agenda quelconque.
   *
   * @param string $summary Titre de l'événement.
   * @return string|null    UID de l'événement, NULL en cas d'échec.
   */
  public function createEventInCalendar(
    string $calendarUrl,
    string $startDatetime,
    string $endDatetime,
    string $summary,
    string $description = ''
  ): ?string;

  /**
   * Supprimer un événement de l'agenda d'un intervenant.
   */
  public function deleteEvent(int $therapistId, string $externalEventId): void;

  /**
   * Supprimer un événement d'un agenda quelconque.
   */
  public function deleteEventInCalendar(string $calendarUrl, string $externalEventId): void;

  /**
   * Remplacer un événement de l'agenda d'un intervenant.
   */
  public function updateEvent(int $therapistId, string $externalEventId, array $appointment): void;
}
