<?php
namespace CRM\Booking\Page;

/**
 * Page InvoiceAction — alias vers AppointmentAction pour la route invoice/generate.
 * Séparé pour la clarté des routes XML.
 */
class InvoiceAction extends AppointmentAction {
  // Hérite entièrement de AppointmentAction.
  // Le dispatch est fait sur l'URL dans AppointmentAction::run().
}
