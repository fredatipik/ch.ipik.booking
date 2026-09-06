<?php
namespace CRM\Booking\Service\CalendarProvider;

/**
 * Fournisseur inerte, utilisé lorsque aucun calendrier externe n'est configuré.
 * Toutes les opérations sont sans effet et sans erreur.
 */
class NullCalendarProvider implements CalendarProviderInterface {

  public function isAvailable(): bool {
    return FALSE;
  }

  public function getBusySlots(int $therapistId, string $dateFrom, string $dateTo): array {
    return [];
  }

  public function getBusySlotsForUrl(string $calendarUrl, string $dateFrom, string $dateTo): array {
    return [];
  }

  public function createEvent(array $appointment): ?string {
    return NULL;
  }

  public function createEventInCalendar(
    string $calendarUrl,
    string $startDatetime,
    string $endDatetime,
    string $summary,
    string $description = ''
  ): ?string {
    return NULL;
  }

  public function deleteEvent(int $therapistId, string $externalEventId): void {
  }

  public function deleteEventInCalendar(string $calendarUrl, string $externalEventId): void {
  }

  public function updateEvent(int $therapistId, string $externalEventId, array $appointment): void {
  }
}
