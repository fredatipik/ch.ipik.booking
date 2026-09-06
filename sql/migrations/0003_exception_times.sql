-- @skipIfColumnExists civicrm_booking_exception start_time
ALTER TABLE `civicrm_booking_exception`
  ADD COLUMN `start_time` time DEFAULT NULL COMMENT 'Heure debut (type special uniquement)',
  ADD COLUMN `end_time` time DEFAULT NULL COMMENT 'Heure fin (type special uniquement)'
