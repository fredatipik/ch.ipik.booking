-- @skipIfColumnExists civicrm_booking_appointment_type slot_interval_minutes
ALTER TABLE `civicrm_booking_appointment_type`
  ADD COLUMN `slot_interval_minutes` smallint unsigned DEFAULT NULL
  COMMENT 'Intervalle entre creneaux proposes , NULL = valeur globale'
