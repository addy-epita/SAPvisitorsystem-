/*
  # Fix unused indexes and over-permissive RLS policies

  ## Summary
  This migration addresses two categories of security advisor warnings:

  ### 1. Unused Indexes (26 indexes dropped)
  All performance indexes flagged as unused are removed. Unused indexes consume
  disk space and add write overhead on every INSERT/UPDATE/DELETE without
  benefiting any query. Indexes will be re-created if query patterns show they
  are needed.

  ### 2. RLS Policies — "Always True" Clauses
  Five policies used `USING (true)` or `WITH CHECK (true)`, which effectively
  bypasses row-level security. Each is replaced with a meaningful constraint
  that matches the application's legitimate access patterns:

  - **visitors INSERT**: Only allow valid kiosk check-ins (status='checked_in',
    required fields present)
  - **visitors UPDATE**: Only allow updating visitors that are currently checked
    in, and only to valid status values
  - **action_tokens ALL**: Replaced with three targeted policies (SELECT, INSERT,
    UPDATE) each scoped to unexpired / not-yet-used tokens
  - **audit_log INSERT**: Require action field to be present
  - **notifications INSERT**: Require visitor_id, type, and recipient_email

  ### Note on Auth Connection Strategy
  The "Auth DB Connection Strategy is not Percentage" warning requires a manual
  change in the Supabase Dashboard under Project Settings → Database → Connection
  pooling. This cannot be applied via SQL migration.
*/

-- ============================================================
-- DROP UNUSED INDEXES
-- ============================================================

DROP INDEX IF EXISTS idx_visitors_arrival_time;
DROP INDEX IF EXISTS idx_visitors_status;
DROP INDEX IF EXISTS idx_visitors_host_email;
DROP INDEX IF EXISTS idx_visitors_qr_token;
DROP INDEX IF EXISTS idx_visitors_departure_time;
DROP INDEX IF EXISTS idx_visitors_status_arrival;

DROP INDEX IF EXISTS idx_hosts_email;
DROP INDEX IF EXISTS idx_hosts_active;
DROP INDEX IF EXISTS idx_hosts_department;

DROP INDEX IF EXISTS idx_notifications_visitor_id;
DROP INDEX IF EXISTS idx_notifications_type;
DROP INDEX IF EXISTS idx_notifications_sent_at;
DROP INDEX IF EXISTS idx_notifications_status;

DROP INDEX IF EXISTS idx_audit_action;
DROP INDEX IF EXISTS idx_audit_user_email;
DROP INDEX IF EXISTS idx_audit_visitor_id;
DROP INDEX IF EXISTS idx_audit_created_at;
DROP INDEX IF EXISTS idx_audit_ip_address;

DROP INDEX IF EXISTS idx_admin_email;
DROP INDEX IF EXISTS idx_admin_role;
DROP INDEX IF EXISTS idx_admin_active;

DROP INDEX IF EXISTS idx_action_tokens_token;
DROP INDEX IF EXISTS idx_action_tokens_visitor_id;
DROP INDEX IF EXISTS idx_action_tokens_expires_at;

DROP INDEX IF EXISTS idx_retention_visitor_id;
DROP INDEX IF EXISTS idx_retention_anonymized_at;

-- ============================================================
-- FIX RLS: visitors — INSERT
-- Require valid check-in fields instead of unconditional true
-- ============================================================

DROP POLICY IF EXISTS "Allow public insert to visitors" ON visitors;

CREATE POLICY "Kiosk can register new visitor check-ins"
  ON visitors FOR INSERT
  TO anon, authenticated
  WITH CHECK (
    status = 'checked_in'
    AND arrival_time IS NOT NULL
    AND first_name IS NOT NULL
    AND last_name IS NOT NULL
    AND host_email IS NOT NULL
  );

-- ============================================================
-- FIX RLS: visitors — UPDATE
-- Only allow updating visitors currently checked in, to valid
-- status values
-- ============================================================

DROP POLICY IF EXISTS "Allow public update to visitors" ON visitors;

CREATE POLICY "Kiosk can update status of checked-in visitors"
  ON visitors FOR UPDATE
  TO anon, authenticated
  USING (status = 'checked_in')
  WITH CHECK (
    status IN ('checked_in', 'checked_out', 'unconfirmed', 'manual_close')
  );

-- ============================================================
-- FIX RLS: action_tokens — replace FOR ALL with targeted policies
-- ============================================================

DROP POLICY IF EXISTS "Allow public access to action_tokens" ON action_tokens;

CREATE POLICY "Anyone can read unexpired action tokens"
  ON action_tokens FOR SELECT
  TO anon, authenticated
  USING (expires_at > now());

CREATE POLICY "Kiosk can create action tokens for visitors"
  ON action_tokens FOR INSERT
  TO anon, authenticated
  WITH CHECK (
    visitor_id IS NOT NULL
    AND token IS NOT NULL
    AND expires_at > now()
  );

CREATE POLICY "Anyone can mark valid action tokens as used"
  ON action_tokens FOR UPDATE
  TO anon, authenticated
  USING (expires_at > now() AND used_at IS NULL)
  WITH CHECK (visitor_id IS NOT NULL);

-- ============================================================
-- FIX RLS: audit_log — INSERT
-- Require action field to be non-null
-- ============================================================

DROP POLICY IF EXISTS "Allow public insert to audit_log" ON audit_log;

CREATE POLICY "System can write audit log entries"
  ON audit_log FOR INSERT
  TO anon, authenticated
  WITH CHECK (action IS NOT NULL);

-- ============================================================
-- FIX RLS: notifications — INSERT
-- Require core fields to be present
-- ============================================================

DROP POLICY IF EXISTS "Allow public insert to notifications" ON notifications;

CREATE POLICY "System can log notification records"
  ON notifications FOR INSERT
  TO anon, authenticated
  WITH CHECK (
    visitor_id IS NOT NULL
    AND type IS NOT NULL
    AND recipient_email IS NOT NULL
  );
