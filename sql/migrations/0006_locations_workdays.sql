CREATE TABLE IF NOT EXISTS `civicrm_booking_location` (
  `id`           int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name`         varchar(255) NOT NULL,
  `address`      varchar(512) DEFAULT NULL,
  `color`        varchar(7) NOT NULL DEFAULT '#8b5cf6',
  `calendar_url` varchar(512) DEFAULT NULL COMMENT 'URL CalDAV agenda du local',
  `is_active`    tinyint(1) NOT NULL DEFAULT 1,
  `weight`       smallint unsigned NOT NULL DEFAULT 0,
  `created_at`   datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `civicrm_booking_workday` (
  `id`           int(10) unsigned NOT NULL AUTO_INCREMENT,
  `therapist_id` int(10) unsigned NOT NULL,
  `work_date`    date NOT NULL,
  `start_time`   time NOT NULL,
  `end_time`     time NOT NULL,
  `location_id`  int(10) unsigned DEFAULT NULL COMMENT 'NULL = hors local, agendas de local ignores',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_workday` (`therapist_id`, `work_date`),
  KEY `idx_workday_date` (`work_date`),
  KEY `fk_workday_location` (`location_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- @skipIfColumnExists civicrm_booking_therapist availability_mode
ALTER TABLE `civicrm_booking_therapist`
  ADD COLUMN `availability_mode` varchar(16) DEFAULT NULL COMMENT 'weekly | workdays , NULL = valeur globale';

-- @skipIfColumnExists civicrm_booking_appointment location_id
ALTER TABLE `civicrm_booking_appointment`
  ADD COLUMN `location_id` int(10) unsigned DEFAULT NULL COMMENT 'Local ou le RDV a lieu',
  ADD COLUMN `location_event_id` varchar(255) DEFAULT NULL COMMENT 'UID evenement dans agenda du local';

INSERT IGNORE INTO `civicrm_booking_settings` (`key`, `value`) VALUES
  ('availability_mode', 'weekly'),
  ('default_start_time', '09:00'),
  ('default_end_time', '17:00');
