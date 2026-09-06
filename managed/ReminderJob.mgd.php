<?php
/**
 * Managed entity — scheduled job "Booking : envoi des rappels".
 * Créé automatiquement à l'activation de l'extension.
 * Désactivé par défaut : à activer manuellement dans CiviCRM → Scheduled Jobs.
 */
return [
  [
    'name'   => 'Job_BookingReminder',
    'entity' => 'Job',
    'cleanup'=> 'unused',
    'update' => 'unmodified',
    'params' => [
      'version'       => 3,
      'name'          => 'Booking : rappels email',
      'description'   => 'Envoie les rappels de rendez-vous aux patients (ch.ipik.booking).',
      'run_frequency' => 'Hourly',
      'api_entity'    => 'BookingReminder',
      'api_action'    => 'run',
      'parameters'    => '',
      'is_active'     => 0,
    ],
  ],
];
