-- @skipIfColumnExists civicrm_booking_appointment caldav_event_id
ALTER TABLE `civicrm_booking_appointment`
  CHANGE COLUMN `gcal_event_id` `caldav_event_id` varchar(255) DEFAULT NULL
  COMMENT 'UID de l evenement dans le calendrier CalDAV externe'
