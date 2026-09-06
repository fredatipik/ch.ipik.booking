-- @skipIfColumnExists civicrm_booking_therapist default_location_id
ALTER TABLE `civicrm_booking_therapist`
  ADD COLUMN `default_location_id` int(10) unsigned DEFAULT NULL
  COMMENT 'Local habituel, utilise quand la journee nen designe aucun'
