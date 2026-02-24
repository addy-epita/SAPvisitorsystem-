/*
  # SAP Visitor Management System - Database Schema

  1. New Tables
    - `visitors` - Stores all visitor check-in/check-out records
      - `id` (bigserial, primary key)
      - `first_name`, `last_name`, `company`, `reason`
      - `host_email`, `host_name`, `visitor_email`
      - `arrival_time`, `expected_duration`, `departure_time`
      - `status`, `checkin_method`, `checkout_method`
      - `qr_token` (unique token for QR checkout)
      - Timestamps and indexes
    
    - `hosts` - Managed list of employees who can receive visitors
      - `id`, `email` (unique), `name`, `department`
      - `is_active` flag
    
    - `notifications` - Log of all notification emails sent
      - Links to visitors table
      - Tracks email status and retries
    
    - `audit_log` - Security audit trail
      - All system actions logged
    
    - `settings` - System configuration
      - Key-value pairs with types
    
    - `admin_users` - Admin and supervisor accounts
      - Email, name, role, password_hash
    
    - `action_tokens` - Secure tokens for email action links
      - Links to visitors table
    
    - `data_retention_log` - GDPR data anonymization tracking

  2. Security
    - Enable RLS on all tables
    - Add policies for authenticated access
*/

-- Create visitors table
CREATE TABLE IF NOT EXISTS visitors (
    id bigserial PRIMARY KEY,
    first_name varchar(100) NOT NULL,
    last_name varchar(100) NOT NULL,
    company varchar(150),
    reason text,
    host_email varchar(255) NOT NULL,
    host_name varchar(150),
    visitor_email varchar(255),
    arrival_time timestamptz NOT NULL,
    expected_duration integer DEFAULT 180,
    departure_time timestamptz,
    status varchar(20) DEFAULT 'checked_in' CHECK (status IN ('checked_in', 'checked_out', 'unconfirmed', 'manual_close')),
    checkin_method varchar(20) DEFAULT 'kiosk' CHECK (checkin_method IN ('kiosk', 'qr_mobile')),
    checkout_method varchar(20) CHECK (checkout_method IN ('qr_rescan', 'host_confirmed', 'manual_admin')),
    qr_token varchar(64) UNIQUE,
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_visitors_arrival_time ON visitors(arrival_time);
CREATE INDEX IF NOT EXISTS idx_visitors_status ON visitors(status);
CREATE INDEX IF NOT EXISTS idx_visitors_host_email ON visitors(host_email);
CREATE INDEX IF NOT EXISTS idx_visitors_qr_token ON visitors(qr_token);
CREATE INDEX IF NOT EXISTS idx_visitors_departure_time ON visitors(departure_time);
CREATE INDEX IF NOT EXISTS idx_visitors_status_arrival ON visitors(status, arrival_time);

-- Create hosts table
CREATE TABLE IF NOT EXISTS hosts (
    id bigserial PRIMARY KEY,
    email varchar(255) UNIQUE NOT NULL,
    name varchar(150) NOT NULL,
    department varchar(100),
    is_active boolean DEFAULT true,
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_hosts_email ON hosts(email);
CREATE INDEX IF NOT EXISTS idx_hosts_active ON hosts(is_active);
CREATE INDEX IF NOT EXISTS idx_hosts_department ON hosts(department);

-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id bigserial PRIMARY KEY,
    visitor_id bigint NOT NULL REFERENCES visitors(id) ON DELETE CASCADE,
    type varchar(20) NOT NULL CHECK (type IN ('arrival', 'reminder', 'escalation', 'checkout')),
    recipient_email varchar(255) NOT NULL,
    subject varchar(255),
    message_body text,
    sent_at timestamptz DEFAULT now(),
    status varchar(20) DEFAULT 'pending' CHECK (status IN ('sent', 'failed', 'pending')),
    error_message text,
    retry_count smallint DEFAULT 0
);

CREATE INDEX IF NOT EXISTS idx_notifications_visitor_id ON notifications(visitor_id);
CREATE INDEX IF NOT EXISTS idx_notifications_type ON notifications(type);
CREATE INDEX IF NOT EXISTS idx_notifications_sent_at ON notifications(sent_at);
CREATE INDEX IF NOT EXISTS idx_notifications_status ON notifications(status);

-- Create audit_log table
CREATE TABLE IF NOT EXISTS audit_log (
    id bigserial PRIMARY KEY,
    action varchar(100) NOT NULL,
    user_email varchar(255),
    visitor_id bigint,
    details text,
    ip_address varchar(45),
    user_agent varchar(255),
    created_at timestamptz DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_audit_action ON audit_log(action);
CREATE INDEX IF NOT EXISTS idx_audit_user_email ON audit_log(user_email);
CREATE INDEX IF NOT EXISTS idx_audit_visitor_id ON audit_log(visitor_id);
CREATE INDEX IF NOT EXISTS idx_audit_created_at ON audit_log(created_at);
CREATE INDEX IF NOT EXISTS idx_audit_ip_address ON audit_log(ip_address);

-- Create settings table
CREATE TABLE IF NOT EXISTS settings (
    setting_key varchar(100) PRIMARY KEY,
    setting_value text,
    setting_type varchar(20) DEFAULT 'string' CHECK (setting_type IN ('string', 'integer', 'boolean', 'json', 'array')),
    description varchar(255),
    updated_at timestamptz DEFAULT now(),
    updated_by varchar(255)
);

-- Create admin_users table
CREATE TABLE IF NOT EXISTS admin_users (
    id bigserial PRIMARY KEY,
    email varchar(255) UNIQUE NOT NULL,
    name varchar(150) NOT NULL,
    role varchar(20) DEFAULT 'viewer' CHECK (role IN ('admin', 'supervisor', 'viewer')),
    password_hash varchar(255),
    sso_enabled boolean DEFAULT true,
    last_login timestamptz,
    login_count integer DEFAULT 0,
    is_active boolean DEFAULT true,
    created_at timestamptz DEFAULT now(),
    updated_at timestamptz DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_admin_email ON admin_users(email);
CREATE INDEX IF NOT EXISTS idx_admin_role ON admin_users(role);
CREATE INDEX IF NOT EXISTS idx_admin_active ON admin_users(is_active);

-- Create action_tokens table
CREATE TABLE IF NOT EXISTS action_tokens (
    id bigserial PRIMARY KEY,
    visitor_id bigint NOT NULL REFERENCES visitors(id) ON DELETE CASCADE,
    token varchar(64) UNIQUE NOT NULL,
    action_type varchar(20) DEFAULT 'confirm_present' CHECK (action_type IN ('confirm_present', 'confirm_departed', 'extend_visit')),
    used_at timestamptz,
    expires_at timestamptz NOT NULL,
    created_at timestamptz DEFAULT now()
);

CREATE INDEX IF NOT EXISTS idx_action_tokens_token ON action_tokens(token);
CREATE INDEX IF NOT EXISTS idx_action_tokens_visitor_id ON action_tokens(visitor_id);
CREATE INDEX IF NOT EXISTS idx_action_tokens_expires_at ON action_tokens(expires_at);

-- Create data_retention_log table
CREATE TABLE IF NOT EXISTS data_retention_log (
    id bigserial PRIMARY KEY,
    visitor_id bigint NOT NULL,
    anonymized_at timestamptz DEFAULT now(),
    records_affected integer DEFAULT 0,
    details text
);

CREATE INDEX IF NOT EXISTS idx_retention_visitor_id ON data_retention_log(visitor_id);
CREATE INDEX IF NOT EXISTS idx_retention_anonymized_at ON data_retention_log(anonymized_at);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('reminder_intervals', '[120, 240, 360, 480]', 'json', 'Reminder intervals in minutes'),
('default_duration', '180', 'integer', 'Default visit duration in minutes'),
('end_of_day_time', '18:00', 'string', 'End of business day time'),
('data_retention_days', '365', 'integer', 'Days to retain visitor data'),
('site_name', 'SAP Office', 'string', 'Site name displayed in emails and UI'),
('company_name', 'SAP', 'string', 'Company name for branding'),
('timezone', 'Europe/Paris', 'string', 'System timezone'),
('language_default', 'fr', 'string', 'Default language (fr/en)'),
('qr_code_expiry_hours', '24', 'integer', 'Hours until QR code expires'),
('enable_email_notifications', 'true', 'boolean', 'Enable/disable email notifications')
ON CONFLICT (setting_key) DO NOTHING;

-- Insert sample hosts
INSERT INTO hosts (email, name, department, is_active) VALUES
('jean.dupont@sap.com', 'Jean Dupont', 'IT', true),
('marie.martin@sap.com', 'Marie Martin', 'RH', true),
('pierre.bernard@sap.com', 'Pierre Bernard', 'Finance', true),
('sophie.petit@sap.com', 'Sophie Petit', 'Marketing', true),
('lucas.moreau@sap.com', 'Lucas Moreau', 'Sales', true)
ON CONFLICT (email) DO NOTHING;

-- Enable RLS
ALTER TABLE visitors ENABLE ROW LEVEL SECURITY;
ALTER TABLE hosts ENABLE ROW LEVEL SECURITY;
ALTER TABLE notifications ENABLE ROW LEVEL SECURITY;
ALTER TABLE audit_log ENABLE ROW LEVEL SECURITY;
ALTER TABLE settings ENABLE ROW LEVEL SECURITY;
ALTER TABLE admin_users ENABLE ROW LEVEL SECURITY;
ALTER TABLE action_tokens ENABLE ROW LEVEL SECURITY;
ALTER TABLE data_retention_log ENABLE ROW LEVEL SECURITY;

-- Create policies for public access (kiosk functionality)
CREATE POLICY "Allow public read access to visitors" ON visitors FOR SELECT USING (true);
CREATE POLICY "Allow public insert to visitors" ON visitors FOR INSERT WITH CHECK (true);
CREATE POLICY "Allow public update to visitors" ON visitors FOR UPDATE USING (true);

CREATE POLICY "Allow public read access to hosts" ON hosts FOR SELECT USING (is_active = true);

CREATE POLICY "Allow public read access to settings" ON settings FOR SELECT USING (true);

CREATE POLICY "Allow public insert to audit_log" ON audit_log FOR INSERT WITH CHECK (true);
CREATE POLICY "Allow public read access to audit_log" ON audit_log FOR SELECT USING (true);

CREATE POLICY "Allow public insert to notifications" ON notifications FOR INSERT WITH CHECK (true);
CREATE POLICY "Allow public read access to notifications" ON notifications FOR SELECT USING (true);

CREATE POLICY "Allow public access to action_tokens" ON action_tokens FOR ALL USING (true);

CREATE POLICY "Allow public read access to admin_users" ON admin_users FOR SELECT USING (true);

CREATE POLICY "Allow public read access to data_retention_log" ON data_retention_log FOR SELECT USING (true);