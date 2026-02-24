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
    `first_name` VARCHAR(100) NOT NULL COMMENT 'Visitor first name',
    `last_name` VARCHAR(100) NOT NULL COMMENT 'Visitor last name',
    `company` VARCHAR(150) DEFAULT NULL COMMENT 'Visitor company/organization',
    `reason` TEXT COMMENT 'Purpose of visit',
    `host_email` VARCHAR(255) NOT NULL COMMENT 'Host email address',
    `host_name` VARCHAR(150) DEFAULT NULL COMMENT 'Host full name (cached)',
    `visitor_email` VARCHAR(255) DEFAULT NULL COMMENT 'Visitor email (optional, GDPR)',
    `arrival_time` DATETIME NOT NULL COMMENT 'Actual check-in timestamp',
    `expected_duration` INT UNSIGNED DEFAULT 180 COMMENT 'Expected visit duration in minutes (default 3h)',
    `departure_time` DATETIME DEFAULT NULL COMMENT 'Actual check-out timestamp',
    `status` ENUM('checked_in', 'checked_out', 'unconfirmed', 'manual_close') DEFAULT 'checked_in' COMMENT 'Current visit status',
    `checkin_method` ENUM('kiosk', 'qr_mobile') DEFAULT 'kiosk' COMMENT 'How visitor checked in',
    `checkout_method` ENUM('qr_rescan', 'host_confirmed', 'manual_admin') DEFAULT NULL COMMENT 'How visitor checked out',
    `qr_token` VARCHAR(64) UNIQUE DEFAULT NULL COMMENT 'Unique token for QR code checkout',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    -- Indexes for common queries
    INDEX `idx_arrival_time` (`arrival_time`),
    INDEX `idx_status` (`status`),
    INDEX `idx_host_email` (`host_email`),
    INDEX `idx_departure_time` (`departure_time`),
    INDEX `idx_qr_token` (`qr_token`),
    INDEX `idx_created_at` (`created_at`),

    -- Composite indexes for dashboard queries
    INDEX `idx_status_arrival` (`status`, `arrival_time`),
    INDEX `idx_host_status` (`host_email`, `status`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Visitor check-in/check-out records';

-- --------------------------------------------------------
-- Table: hosts
-- Managed list of authorized hosts (employees who can receive visitors)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `hosts`;

CREATE TABLE `hosts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) UNIQUE NOT NULL COMMENT 'Host email address (unique identifier)',
    `name` VARCHAR(150) NOT NULL COMMENT 'Host full name',
    `department` VARCHAR(100) DEFAULT NULL COMMENT 'Department/team',
    `phone` VARCHAR(50) DEFAULT NULL COMMENT 'Optional phone number',
    `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Whether host can receive visitors',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_email` (`email`),
    INDEX `idx_active` (`is_active`),
    INDEX `idx_department` (`department`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Authorized hosts who can receive visitors';

-- --------------------------------------------------------
-- Table: notifications
-- Log of all email notifications sent
-- --------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;

CREATE TABLE `notifications` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `visitor_id` INT UNSIGNED NOT NULL COMMENT 'Related visitor record',
    `type` ENUM('arrival', 'reminder', 'escalation', 'checkout', 'host_action') NOT NULL COMMENT 'Notification type',
    `recipient_email` VARCHAR(255) NOT NULL COMMENT 'Email recipient',
    `subject` VARCHAR(255) DEFAULT NULL COMMENT 'Email subject line',
    `content` TEXT DEFAULT NULL COMMENT 'Email body (for audit/debug)',
    `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When notification was sent',
    `status` ENUM('sent', 'failed', 'pending', 'retrying') DEFAULT 'pending' COMMENT 'Delivery status',
    `error_message` TEXT DEFAULT NULL COMMENT 'Error details if failed',
    `retry_count` TINYINT UNSIGNED DEFAULT 0 COMMENT 'Number of retry attempts',
    `message_id` VARCHAR(255) DEFAULT NULL COMMENT 'External message ID (Graph API)',

    INDEX `idx_visitor_id` (`visitor_id`),
    INDEX `idx_type` (`type`),
    INDEX `idx_status` (`status`),
    INDEX `idx_sent_at` (`sent_at`),
    INDEX `idx_recipient` (`recipient_email`),

    FOREIGN KEY (`visitor_id`) REFERENCES `visitors`(`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Notification log for audit and tracking';

-- --------------------------------------------------------
-- Table: audit_log
-- Comprehensive audit trail for all system actions
-- --------------------------------------------------------
DROP TABLE IF EXISTS `audit_log`;

CREATE TABLE `audit_log` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `action` VARCHAR(100) NOT NULL COMMENT 'Action type (e.g., checkin, checkout, login)',
    `user_email` VARCHAR(255) DEFAULT NULL COMMENT 'Admin/supervisor who performed action',
    `visitor_id` INT UNSIGNED DEFAULT NULL COMMENT 'Related visitor if applicable',
    `details` JSON DEFAULT NULL COMMENT 'Structured action details',
    `ip_address` VARCHAR(45) DEFAULT NULL COMMENT 'Client IP address (IPv6 compatible)',
    `user_agent` VARCHAR(255) DEFAULT NULL COMMENT 'Client user agent string',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    INDEX `idx_action` (`action`),
    INDEX `idx_user_email` (`user_email`),
    INDEX `idx_visitor_id` (`visitor_id`),
    INDEX `idx_created_at` (`created_at`),
    INDEX `idx_ip_address` (`ip_address`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Audit trail for compliance and security';

-- --------------------------------------------------------
-- Table: settings
-- System configuration key-value store
-- --------------------------------------------------------
DROP TABLE IF EXISTS `settings`;

CREATE TABLE `settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY COMMENT 'Unique setting identifier',
    `setting_value` TEXT COMMENT 'Setting value (JSON for complex values)',
    `description` VARCHAR(255) DEFAULT NULL COMMENT 'Human-readable description',
    `is_json` BOOLEAN DEFAULT FALSE COMMENT 'Whether value should be parsed as JSON',
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_key` (`setting_key`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='System configuration settings';

-- --------------------------------------------------------
-- Table: admin_users
-- Local admin accounts (for non-SSO access)
-- --------------------------------------------------------
DROP TABLE IF EXISTS `admin_users`;

CREATE TABLE `admin_users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `email` VARCHAR(255) UNIQUE NOT NULL COMMENT 'Admin email address',
    `name` VARCHAR(150) NOT NULL COMMENT 'Admin full name',
    `password_hash` VARCHAR(255) DEFAULT NULL COMMENT 'BCrypt password hash (NULL for SSO-only)',
    `role` ENUM('admin', 'supervisor', 'viewer') DEFAULT 'viewer' COMMENT 'Access level',
    `is_active` BOOLEAN DEFAULT TRUE COMMENT 'Whether account is enabled',
    `last_login` DATETIME DEFAULT NULL COMMENT 'Last successful login timestamp',
    `last_login_ip` VARCHAR(45) DEFAULT NULL COMMENT 'IP of last login',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`),
    INDEX `idx_active` (`is_active`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='Administrative user accounts';

-- --------------------------------------------------------
-- Table: qr_sessions
-- Tracks QR code generation and usage for security
-- --------------------------------------------------------
DROP TABLE IF EXISTS `qr_sessions`;

CREATE TABLE `qr_sessions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `visitor_id` INT UNSIGNED NOT NULL COMMENT 'Related visitor',
    `qr_token` VARCHAR(64) NOT NULL COMMENT 'QR token value',
    `generated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'When QR was generated',
    `expires_at` DATETIME NOT NULL COMMENT 'QR code expiration',
    `used_at` DATETIME DEFAULT NULL COMMENT 'When QR was scanned for checkout',
    `used_from_ip` VARCHAR(45) DEFAULT NULL COMMENT 'IP that used the QR',
    `is_revoked` BOOLEAN DEFAULT FALSE COMMENT 'Whether QR was manually revoked',

    INDEX `idx_visitor_id` (`visitor_id`),
    INDEX `idx_qr_token` (`qr_token`),
    INDEX `idx_expires_at` (`expires_at`),

    FOREIGN KEY (`visitor_id`) REFERENCES `visitors`(`id`) ON DELETE CASCADE

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='QR code session tracking';

-- --------------------------------------------------------
-- Insert Default Settings
-- --------------------------------------------------------

INSERT INTO `settings` (`setting_key`, `setting_value`, `description`, `is_json`) VALUES
-- Timing and Intervals
('reminder_intervals', '[120, 240, 360, 480]', 'Reminder intervals in minutes (2h, 4h, 6h, 8h)', TRUE),
('default_duration', '180', 'Default visit duration in minutes (3 hours)', FALSE),
('end_of_day_time', '18:00', 'End of business day (24h format)', FALSE),
('qr_code_validity_hours', '24', 'How long QR codes remain valid', FALSE),

-- Data Retention and Privacy
('data_retention_days', '365', 'Days to retain visitor data before anonymization', FALSE),
('auto_anonymize', 'true', 'Automatically anonymize expired records', FALSE),
('gdpr_mode', 'strict', 'GDPR compliance level (strict|relaxed)', FALSE),

-- Site Configuration
('site_name', 'SAP Office', 'Site/location name displayed in UI', FALSE),
('site_address', '', 'Physical address of the location', FALSE),
('timezone', 'Europe/Paris', 'Default timezone for timestamps', FALSE),
('locale', 'fr_FR', 'Default locale for formatting', FALSE),
('evacuation_plan_url', '', 'URL to evacuation plan document', FALSE),

-- Email Configuration
('email_from_name', 'SAP Visitor System', 'Sender name for emails', FALSE),
('email_from_address', 'visitors@sap.example.com', 'Sender email address', FALSE),
('email_reply_to', '', 'Reply-to address for emails', FALSE),
('supervisor_emails', '[]', 'JSON array of supervisor emails for escalations', TRUE),
('escalation_enabled', 'true', 'Enable end-of-day escalation emails', FALSE),

-- Kiosk Settings
('kiosk_auto_redirect_seconds', '30', 'Seconds before kiosk returns to home screen', FALSE),
('kiosk_language_default', 'fr', 'Default language (fr|en)', FALSE),
('kiosk_show_company_field', 'true', 'Show company field in check-in form', FALSE),
('kiosk_show_reason_field', 'true', 'Show visit reason field', FALSE),
('kiosk_allow_manual_host', 'true', 'Allow entering host email not in directory', FALSE),

-- Security Settings
('max_login_attempts', '5', 'Failed login attempts before lockout', FALSE),
('login_lockout_minutes', '30', 'Minutes to lock account after max attempts', FALSE),
('session_lifetime_hours', '8', 'Admin session lifetime', FALSE),
('require_https', 'true', 'Require HTTPS for all requests', FALSE),
('allowed_ip_ranges', '[]', 'JSON array of allowed IP ranges (empty = all)', TRUE),

-- Feature Flags
('enable_email_notifications', 'true', 'Master switch for email notifications', FALSE),
('enable_host_confirmations', 'true', 'Allow hosts to confirm visitor departure via email', FALSE),
('enable_visitor_email', 'true', 'Allow visitors to provide email (optional)', FALSE),
('enable_qr_checkout', 'true', 'Enable QR code checkout flow', FALSE),
('enable_manual_checkout', 'true', 'Enable manual name-based checkout', FALSE),
('maintenance_mode', 'false', 'Put system in maintenance mode', FALSE);

-- --------------------------------------------------------
-- Insert Sample Hosts (for testing - remove in production)
-- --------------------------------------------------------

-- Uncomment below to add sample hosts for testing
-- INSERT INTO `hosts` (`email`, `name`, `department`, `is_active`) VALUES
-- ('john.doe@sap.example.com', 'John Doe', 'IT Department', TRUE),
-- ('jane.smith@sap.example.com', 'Jane Smith', 'HR Department', TRUE),
-- ('manager@sap.example.com', 'Site Manager', 'Operations', TRUE);

-- --------------------------------------------------------
-- Views for Common Queries
-- --------------------------------------------------------

-- View: Currently checked-in visitors
CREATE OR REPLACE VIEW `view_current_visitors` AS
SELECT
    v.*,
    TIMESTAMPDIFF(MINUTE, v.arrival_time, NOW()) as `duration_minutes`,
    CASE
        WHEN v.expected_duration > 0 THEN
            ROUND((TIMESTAMPDIFF(MINUTE, v.arrival_time, NOW()) / v.expected_duration) * 100, 1)
        ELSE 0
    END as `duration_percentage`
FROM `visitors` v
WHERE v.status = 'checked_in'
ORDER BY v.arrival_time ASC;

-- View: Today's visits summary
CREATE OR REPLACE VIEW `view_today_visits` AS
SELECT
    v.*,
    h.department as `host_department`
FROM `visitors` v
LEFT JOIN `hosts` h ON v.host_email = h.email
WHERE DATE(v.arrival_time) = CURDATE()
ORDER BY v.arrival_time DESC;

-- View: Visitor statistics by day
CREATE OR REPLACE VIEW `view_daily_stats` AS
SELECT
    DATE(arrival_time) as `date`,
    COUNT(*) as `total_visits`,
    COUNT(DISTINCT host_email) as `unique_hosts`,
    COUNT(DISTINCT company) as `unique_companies`,
    AVG(TIMESTAMPDIFF(MINUTE, arrival_time, COALESCE(departure_time, NOW()))) as `avg_duration_minutes`,
    SUM(CASE WHEN status = 'checked_in' THEN 1 ELSE 0 END) as `still_checked_in`
FROM `visitors`
GROUP BY DATE(arrival_time)
ORDER BY `date` DESC;

SET FOREIGN_KEY_CHECKS = 1;

-- --------------------------------------------------------
-- Grant Permissions (adjust as needed for your setup)
-- --------------------------------------------------------

-- Example: Create application user (run as admin)
-- CREATE USER IF NOT EXISTS 'visitor_app'@'localhost' IDENTIFIED BY 'strong_random_password';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON `visitor_management`.* TO 'visitor_app'@'localhost';
-- FLUSH PRIVILEGES;