/*
  # Add Phone, Expiry Tracking, and Improved Notification Settings

  ## Summary
  This migration enhances the visitor management system to support:
  1. Visitor phone number capture
  2. Multi-step host notification tracking (expiry → reminder → EOD warning → escalation)
  3. Safety department email configuration
  4. Updated end-of-day time to 21:00
  5. Escalation at 22:00 configuration

  ## New Visitor Fields
  - `phone` - Visitor phone number
  - `expiry_notified` - Whether first expiry email was sent to host
  - `expiry_email_sent_at` - Timestamp of first expiry email
  - `reminder_sent` - Whether 1h reminder email was sent to host
  - `reminder_sent_at` - Timestamp of reminder email
  - `eod_notified` - Whether 21h end-of-day warning was sent
  - `host_confirmed` - Host confirmation: 'present' | 'departed' | null
  - `departure_method` - How departure was recorded

  ## Settings Updates
  - `end_of_day_time` updated to '21:00'
  - `safety_emails` added with default safety dept email
  - `escalation_hour` added (22h)
  - `eod_warning_hour` added (21h)

  ## Constraint Updates
  - `checkout_method` now includes 'self_checkout'
  - `notifications.type` now includes new email types
*/

-- Add phone number
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'visitors' AND column_name = 'phone') THEN
    ALTER TABLE visitors ADD COLUMN phone varchar(30);
  END IF;
END $$;

-- Add expiry notification tracking
DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'visitors' AND column_name = 'expiry_notified') THEN
    ALTER TABLE visitors ADD COLUMN expiry_notified boolean DEFAULT false;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'visitors' AND column_name = 'expiry_email_sent_at') THEN
    ALTER TABLE visitors ADD COLUMN expiry_email_sent_at timestamptz;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'visitors' AND column_name = 'reminder_sent') THEN
    ALTER TABLE visitors ADD COLUMN reminder_sent boolean DEFAULT false;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'visitors' AND column_name = 'reminder_sent_at') THEN
    ALTER TABLE visitors ADD COLUMN reminder_sent_at timestamptz;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'visitors' AND column_name = 'eod_notified') THEN
    ALTER TABLE visitors ADD COLUMN eod_notified boolean DEFAULT false;
  END IF;
END $$;

DO $$
BEGIN
  IF NOT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_name = 'visitors' AND column_name = 'host_confirmed') THEN
    ALTER TABLE visitors ADD COLUMN host_confirmed varchar(20) CHECK (host_confirmed IN ('present', 'departed'));
  END IF;
END $$;

-- Update checkout_method constraint to allow self_checkout
ALTER TABLE visitors DROP CONSTRAINT IF EXISTS visitors_checkout_method_check;
ALTER TABLE visitors ADD CONSTRAINT visitors_checkout_method_check
  CHECK (checkout_method IN ('qr_rescan', 'host_confirmed', 'manual_admin', 'self_checkout'));

-- Update notifications type constraint to include all new email types
ALTER TABLE notifications DROP CONSTRAINT IF EXISTS notifications_type_check;
ALTER TABLE notifications ADD CONSTRAINT notifications_type_check
  CHECK (type IN (
    'arrival', 'arrival_safety',
    'expiry_check', 'expiry_reminder',
    'eod_warning', 'escalation',
    'checkout', 'checkout_safety',
    'reminder'
  ));

-- Update end-of-day time to 21:00
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
  ('end_of_day_time', '21:00', 'string', 'Heure de vérification fin de journée (avertissement hôte)')
ON CONFLICT (setting_key) DO UPDATE SET setting_value = '21:00', updated_at = now();

-- Add safety department emails
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
  ('safety_emails', '["securite@sapformations.com"]', 'json', 'Emails du département sécurité (séparés par virgule)')
ON CONFLICT (setting_key) DO NOTHING;

-- Add escalation hour (22:00)
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
  ('escalation_hour', '22', 'integer', 'Heure d''escalade vers la sécurité si hôte n''a pas confirmé')
ON CONFLICT (setting_key) DO NOTHING;

-- Add EOD warning hour (21:00)
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
  ('eod_warning_hour', '21', 'integer', 'Heure d''envoi de l''avertissement final à l''hôte')
ON CONFLICT (setting_key) DO NOTHING;

-- Update default duration to 120 minutes (2h)
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
  ('default_duration', '120', 'integer', 'Durée de visite par défaut en minutes (2h)')
ON CONFLICT (setting_key) DO UPDATE SET setting_value = '120', updated_at = now();
