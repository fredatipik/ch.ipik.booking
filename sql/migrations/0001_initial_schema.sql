-- ch.ipik.booking — install.sql
-- Tables créées à l'activation de l'extension

-- Thérapeutes
CREATE TABLE IF NOT EXISTS `civicrm_booking_therapist` (
  `id`               int(10) unsigned NOT NULL AUTO_INCREMENT,
  `contact_id`       int(10) unsigned NOT NULL COMMENT 'FK civicrm_contact.id',
  `wp_user_id`       int(10) unsigned DEFAULT NULL COMMENT 'FK WordPress users.ID',
  `color`            varchar(7)  NOT NULL DEFAULT '#3b82f6' COMMENT 'Couleur agenda hex',
  `max_advance_days` smallint unsigned NOT NULL DEFAULT 60 COMMENT 'Reservation max N jours a l avance',
  `buffer_minutes`   smallint unsigned NOT NULL DEFAULT 0  COMMENT 'Tampon entre RDV (minutes)',
  `is_active`        tinyint(1)  NOT NULL DEFAULT 1,
  `calendar_url`     varchar(512) DEFAULT NULL COMMENT 'URL CalDAV agenda Infomaniak',
  `availability_mode` varchar(16) DEFAULT NULL COMMENT 'weekly | workdays , NULL = valeur globale',
  `default_location_id` int(10) unsigned DEFAULT NULL COMMENT 'Local habituel',
  `created_at`       datetime    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_therapist_contact` (`contact_id`),
  KEY `fk_therapist_contact` (`contact_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Types de rendez-vous
CREATE TABLE IF NOT EXISTS `civicrm_booking_appointment_type` (
  `id`                        int(10) unsigned NOT NULL AUTO_INCREMENT,
  `label`                     varchar(255) NOT NULL,
  `description`               text DEFAULT NULL,
  `duration_minutes`          smallint unsigned NOT NULL DEFAULT 60,
  `color`                     varchar(7) NOT NULL DEFAULT '#10b981',
  `requires_existing_contact` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Refuser si email inconnu dans CiviCRM',
  `requires_account_creation` tinyint(1) NOT NULL DEFAULT 0 COMMENT 'Creation compte WP obligatoire',
  `therapist_selector`        varchar(32) NOT NULL DEFAULT 'round_robin' COMMENT 'round_robin|least_loaded|random - surcharge locale',
  `slot_interval_minutes`     smallint unsigned DEFAULT NULL COMMENT 'Intervalle entre creneaux , NULL = valeur globale',
  `is_active`                 tinyint(1) NOT NULL DEFAULT 1,
  `weight`                    smallint unsigned NOT NULL DEFAULT 0 COMMENT 'Ordre d affichage',
  `created_at`                datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Liaison type ↔ thérapeutes éligibles
CREATE TABLE IF NOT EXISTS `civicrm_booking_type_therapist` (
  `appointment_type_id` int(10) unsigned NOT NULL,
  `therapist_id`        int(10) unsigned NOT NULL,
  PRIMARY KEY (`appointment_type_id`, `therapist_id`),
  KEY `fk_tt_therapist` (`therapist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Disponibilités récurrentes par thérapeute
CREATE TABLE IF NOT EXISTS `civicrm_booking_availability` (
  `id`           int(10) unsigned NOT NULL AUTO_INCREMENT,
  `therapist_id` int(10) unsigned NOT NULL,
  `day_of_week`  tinyint unsigned NOT NULL COMMENT '0=Dimanche a 6=Samedi',
  `start_time`   time NOT NULL,
  `end_time`     time NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_avail_therapist` (`therapist_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Exceptions : jours off, congés, créneaux spéciaux
CREATE TABLE IF NOT EXISTS `civicrm_booking_exception` (
  `id`           int(10) unsigned NOT NULL AUTO_INCREMENT,
  `therapist_id` int(10) unsigned NOT NULL,
  `date_start`   date NOT NULL,
  `date_end`     date NOT NULL,
  `type`         enum('off','special') NOT NULL DEFAULT 'off',
  `start_time`   time DEFAULT NULL COMMENT 'Heure debut (type special uniquement)',
  `end_time`     time DEFAULT NULL COMMENT 'Heure fin (type special uniquement)',
  `note`         varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_exc_therapist` (`therapist_id`),
  KEY `idx_exc_dates` (`date_start`, `date_end`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Rendez-vous
CREATE TABLE IF NOT EXISTS `civicrm_booking_appointment` (
  `id`                   int(10) unsigned NOT NULL AUTO_INCREMENT,
  `appointment_type_id`  int(10) unsigned NOT NULL,
  `therapist_id`         int(10) unsigned NOT NULL,
  `contact_id`           int(10) unsigned NOT NULL COMMENT 'FK civicrm_contact.id',
  `start_datetime`       datetime NOT NULL,
  `end_datetime`         datetime NOT NULL,
  `status`               enum('pending','confirmed','cancelled','completed') NOT NULL DEFAULT 'confirmed',
  `civicrm_activity_id`  int(10) unsigned DEFAULT NULL COMMENT 'FK civicrm_activity.id',
  `swissqr_invoice_id`   int(10) unsigned DEFAULT NULL COMMENT 'FK civicrm_booking_invoice (Phase 2)',
  `caldav_event_id`      varchar(255) DEFAULT NULL COMMENT 'UID evenement calendrier CalDAV externe',
  `location_id`          int(10) unsigned DEFAULT NULL COMMENT 'Local ou le RDV a lieu',
  `location_event_id`    varchar(255) DEFAULT NULL COMMENT 'UID evenement dans agenda du local',
  `notes`                text DEFAULT NULL,
  `created_at`           datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_appt_type`      (`appointment_type_id`),
  KEY `fk_appt_therapist` (`therapist_id`),
  KEY `fk_appt_contact`   (`contact_id`),
  KEY `idx_appt_datetime`  (`start_datetime`, `end_datetime`),
  KEY `idx_appt_status`    (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Paramètres globaux (clé/valeur)
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

CREATE TABLE IF NOT EXISTS `civicrm_booking_settings` (
  `key`   varchar(128) NOT NULL,
  `value` text DEFAULT NULL,
  PRIMARY KEY (`key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valeurs par défaut
INSERT IGNORE INTO `civicrm_booking_settings` (`key`, `value`) VALUES
  ('therapist_selector', 'round_robin'),
  ('slot_interval_minutes', '15'),
  ('reminder_hours_before', '24'),
  ('confirmation_email_subject', 'Confirmation de votre rendez-vous'),
  ('reminder_email_subject', 'Rappel : votre rendez-vous demain'),
  ('caldav_user', ''),
  ('caldav_password', ''),
  ('contribution_financial_type_id', ''),
  ('contribution_status_id', '1'),
  ('availability_mode', 'weekly'),
  ('default_start_time', '09:00'),
  ('default_end_time', '17:00'),
  ('create_activities', '1'),
  ('from_email', '');
