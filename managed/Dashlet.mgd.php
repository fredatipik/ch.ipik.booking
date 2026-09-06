<?php
/**
 * Dashlet « Mes rendez-vous », proposé sur le tableau de bord CiviCRM.
 *
 * Actif par défaut, mais chacun peut le retirer depuis « Configurer votre
 * tableau de bord » et le remettre ensuite.
 */
return [
  [
    'name'    => 'Dashlet_BookingUpcoming',
    'entity'  => 'Dashboard',
    'cleanup' => 'always',
    'update'  => 'unmodified',
    'params'  => [
      'version'     => 3,
      'name'        => 'booking_upcoming',
      'label'       => 'Mes rendez-vous',
      'url'         => 'civicrm/booking/dashlet?reset=1&snippet=5',
      'fullscreen_url' => 'civicrm/booking/dashlet?reset=1&snippet=5&context=dashletFullscreen',
      'permission'  => 'access booking',
      'is_active'   => 1,
      'is_reserved' => 0,
    ],
  ],
];
