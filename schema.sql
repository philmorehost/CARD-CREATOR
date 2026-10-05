-- CARD-CREATOR Database Schema

CREATE TABLE IF NOT EXISTS `settings` (
  `setting_key` varchar(100) NOT NULL PRIMARY KEY,
  `setting_value` text DEFAULT NULL
);

CREATE TABLE IF NOT EXISTS `users` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password_hash` varchar(255) NOT NULL,
  `role` varchar(20) NOT NULL DEFAULT 'admin',
  `is_suspended` tinyint(1) NOT NULL DEFAULT 0,
  `otp_code` varchar(10) DEFAULT NULL,
  `otp_expires_at` datetime DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `ip_blocks` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `ip_address` varchar(45) NOT NULL,
  `reason` varchar(255) DEFAULT 'Brute force attempts',
  `blocked_until` datetime DEFAULT NULL,
  `is_permanent` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `ip_whitelists` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `ip_address` varchar(45) NOT NULL UNIQUE,
  `label` varchar(100) DEFAULT 'Auto Whitelisted',
  `successful_sessions_count` int(11) NOT NULL DEFAULT 0,
  `is_auto` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `username` varchar(100) DEFAULT NULL,
  `ip_address` varchar(45) NOT NULL,
  `status` varchar(20) NOT NULL,
  `user_agent` text DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `card_templates` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `title` varchar(100) NOT NULL,
  `type` varchar(20) NOT NULL DEFAULT 'id_card',
  `orientation` varchar(20) NOT NULL DEFAULT 'portrait',
  `width_px` int(11) NOT NULL DEFAULT 600,
  `height_px` int(11) NOT NULL DEFAULT 960,
  `front_bg_image` varchar(255) DEFAULT NULL,
  `back_bg_image` varchar(255) DEFAULT NULL,
  `fields_json` text DEFAULT NULL,
  `is_premium` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS `saved_cards` (
  `id` INTEGER PRIMARY KEY AUTOINCREMENT,
  `card_uuid` varchar(64) NOT NULL UNIQUE,
  `template_id` int(11) DEFAULT NULL,
  `title` varchar(100) NOT NULL,
  `form_data_json` text DEFAULT NULL,
  `front_image_url` varchar(255) DEFAULT NULL,
  `back_image_url` varchar(255) DEFAULT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
);
