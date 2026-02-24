-- SAP Visitor Management System - Database Schema
-- MySQL 8.0+
-- Created: 2026-02-24

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table: visitors
-- Stores all visitor check-in/check-out records
-- --------------------------------------------------------
DROP TABLE IF EXISTS `visitors`;
CREATE TABLE `visitors` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `first_name` VARCHAR(100) NOT NULL,
    `last_name` VARCHAR(100) NOT NULL,
    `company` VARCHAR(150) DEFAULT NULL,
    `reason` TEXT DEFAULT NULL,
    `host_email` VARCHAR(255) NOT NULL,
    `host_name` VARCHAR(150) DEFAULT NULL,
    `visitor_email` VARCHAR(255) DEFAULT NULL,
    `arrival_time` DATETIME NOT NULL,
    `expected_duration` INT UNSIGNED DEFAULT 180 COMMENT 'Duration in minutes (default 3 hours)',
    `departure_time` DATETIME DEFAULT NULL,
    `status` ENUM('checked_in', 'checked_out', 'unconfirmed', 'manual_close') DEFAULT 'checked_in',
    `checkin_method` ENUM('kiosk', 'qr_mobile') DEFAULT 'kiosk',
    `checkout_method` ENUM('qr_rescan', 'host_confirmed', 'manual_admin') DEFAULT NULL,
    `qr_token` VARCHAR(64) UNIQUE DEFAULT NULL COMMENT 'Unique token for QR checkout',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes for common queries
    INDEX `idx_arrival_time` (`arrival_time`),
    INDEX `idx_status` (`status`),
    INDEX `idx_host_email` (`host_email`),
    INDEX `idx_qr_token` (`qr_token`),
    INDEX `idx_departure_time` (`departure_time`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_status_arrival` (`status`, `arrival_time`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Visitor check-in/check-out records';

-- --------------------------------------------------------
-- Table: hosts
-- Managed list of employees who can receive visitors
-- --------------------------------------------------------
DROP TABLE IF EXISTS `hosts`;
CREATE TABLE `hosts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `department` VARCHAR(100) DEFAULT NULL,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_email` (`email`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_department` (`department`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Employee hosts who can receive visitors';

-- --------------------------------------------------------
-- Table: notifications
-- Log of all notification emails sent
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `visitor_id` INT UNSIGNED NOT NULL,
    `type` ENUM('arrival', 'reminder', 'escalation', 'checkout') NOT NULL,
    `recipient_email` VARCHAR(255) NOT NULL,
    `subject` VARCHAR(255) DEFAULT NULL,
    `message_body` TEXT DEFAULT NULL,
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `status` ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    `error_message` TEXT DEFAULT NULL,
    `retry_count` TINYINT UNSIGNED DEFAULT 0,

    INDEX `idx_visitor_id` (`visitor_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_sent_at` (`sent_at`),
    INDEX `idx_status` (`status`),
    INDEX `idx_visitor_type` (`visitor_id`, `type`),

    FOREIGN KEY (`visitor_id`) REFERENCES `visitors`(`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notification email log';

-- --------------------------------------------------------
-- Table: audit_log
-- Security audit trail for all system actions
-- --------------------------------------------------------
DROP TABLE IF EXISTS `audit_log`;
CREATE TABLE `audit_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `action` VARCHAR(100) NOT NULL COMMENT 'Action performed (e.g., checkin, checkout, login)',
    `user_email` VARCHAR(255) DEFAULT NULL COMMENT 'Admin/supervisor who performed action',
    `visitor_id` INT UNSIGNED DEFAULT NULL,
    `details` TEXT DEFAULT NULL COMMENT 'JSON or text details of the action',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'IPv4 or IPv6 address',
    `user_agent` VARCHAR(255) DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_action` (`action`),
    INDEX `idx_user_email` (`user_email`),
    INDEX `idx_visitor_id` (`visitor_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_ip_address` (`ip_address`),
    INDEX `idx_action_created` (`action`, `created_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Security audit log';

-- --------------------------------------------------------
-- Table: settings
-- System configuration settings
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT DEFAULT NULL,
    `setting_type` ENUM('string', 'integer', 'boolean', 'json', 'array') DEFAULT 'string',
    `description` VARCHAR(255) DEFAULT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `updated_by` VARCHAR(255) DEFAULT NULL,

    INDEX `idx_key` (`setting_key`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System configuration settings';

-- --------------------------------------------------------
-- Table: admin_users
-- Admin and supervisor user accounts
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admin_users`;
CREATE TABLE `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) UNIQUE NOT NULL,
    `name` VARCHAR(150) NOT NULL,
    `role` ENUM('admin', 'supervisor', 'viewer') DEFAULT 'viewer',
    `password_hash` VARCHAR(255) DEFAULT NULL COMMENT 'Null for SSO-only users',
    `sso_enabled` BOOLEAN DEFAULT TRUE,
    `last_login` DATETIME DEFAULT NULL,
    `login_count` INT UNSIGNED DEFAULT 0,
    `is_active` BOOLEAN DEFAULT TRUE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_active` (`is_active`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Admin and supervisor user accounts';

-- --------------------------------------------------------
-- Table: data_retention_log
-- Track GDPR data anonymization
-- --------------------------------------------------------
DROP TABLE IF EXISTS `data_retention_log`;
CREATE TABLE `data_retention_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `visitor_id` INT UNSIGNED NOT NULL,
    `anonymized_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `records_affected` INT UNSIGNED DEFAULT 0,
    `details` TEXT DEFAULT NULL,

    INDEX `idx_visitor_id` (`visitor_id`),
    INDEX `idx_anonymized_at` (`anonymized_at`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='GDPR data retention anonymization log';

-- --------------------------------------------------------
-- Insert Default Settings
-- --------------------------------------------------------
INSERT INTO `settings` (`setting_key`, `setting_value`, `setting_type`, `description`) VALUES
('reminder_intervals', '[120, 240, 360, 480]', 'json', 'Reminder intervals in minutes (2, 4, 6, 8 hours)'),
('default_duration', '180', 'integer', 'Default visit duration in minutes (3 hours)'),
('end_of_day_time', '18:00', 'string', 'End of business day time (24h format)'),
('data_retention_days', '365', 'integer', 'Days to retain visitor data before anonymization'),
('site_name', 'SAP Office', 'string', 'Site name displayed in emails and UI'),
('evacuation_plan_url', '', 'string', 'URL to evacuation plan document'),
('company_name', 'SAP', 'string', 'Company name for branding'),
('timezone', 'Europe/Paris', 'string', 'System timezone'),
('date_format', 'd/m/Y H:i', 'string', 'Date/time display format'),
('language_default', 'fr', 'string', 'Default language (fr/en)'),
('qr_code_expiry_hours', '24', 'integer', 'Hours until QR code expires'),
('max_visit_duration_hours', '12', 'integer', 'Maximum allowed visit duration'),
('enable_email_notifications', 'true', 'boolean', 'Enable/disable email notifications'),
('escalation_recipients', '[]', 'json', 'List of supervisor emails for escalation'),
('kiosk_auto_redirect_seconds', '60', 'integer', 'Seconds before kiosk returns to home screen'),
('session_timeout_minutes', '30', 'integer', 'Admin session timeout in minutes'),
('maintenance_mode', 'false', 'boolean', 'Enable maintenance mode'),
('version', '1.0.0', 'string', 'Application version');

-- --------------------------------------------------------
-- Insert Default Admin User (password: changeme)
-- CHANGE THIS PASSWORD AFTER INSTALLATION
-- --------------------------------------------------------
INSERT INTO `admin_users` (`email`, `name`, `role`, `password_hash`, `sso_enabled`, `is_active`) VALUES
('admin@example.com', 'System Administrator', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', FALSE, TRUE);
-- Note: Default password is 'changeme' - MUST BE CHANGED AFTER INSTALLATION

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Views for Common Queries
-- --------------------------------------------------------

-- View: Current visitors on site
DROP VIEW IF EXISTS `view_current_visitors`;
CREATE VIEW `view_current_visitors` AS
SELECT
    v.*,
    TIMESTAMPDIFF(MINUTE, v.arrival_time, NOW()) as duration_minutes,
    CONCAT(v.first_name, ' ', v.last_name) as full_name
FROM `visitors` v
WHERE v.status = 'checked_in'
ORDER BY v.arrival_time DESC;

-- View: Today's visits
DROP VIEW IF EXISTS `view_today_visits`;
CREATE VIEW `view_today_visits` AS
SELECT
    v.*,
    CONCAT(v.first_name, ' ', v.last_name) as full_name,
    TIMESTAMPDIFF(MINUTE, v.arrival_time, COALESCE(v.departure_time, NOW())) as actual_duration_minutes
FROM `visitors` v
WHERE DATE(v.arrival_time) = CURDATE()
ORDER BY v.arrival_time DESC;

-- View: Visitor statistics by host
DROP VIEW IF EXISTS `view_host_statistics`;
CREATE VIEW `view_host_statistics` AS
SELECT
    v.host_email,
    v.host_name,
    COUNT(*) as total_visits,
    SUM(CASE WHEN v.status = 'checked_in' THEN 1 ELSE 0 END) as active_visits,
    AVG(TIMESTAMPDIFF(MINUTE, v.arrival_time, COALESCE(v.departure_time, NOW()))) as avg_duration_minutes,
    MAX(v.arrival_time) as last_visit_date
FROM `visitors` v
WHERE v.arrival_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY v.host_email, v.host_name;
