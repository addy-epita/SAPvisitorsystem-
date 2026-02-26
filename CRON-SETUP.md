# Cron Job Setup for Automated Reminders

This guide explains how to set up the automated reminder system that sends timed reminders and end-of-day escalations.

---

## Overview

The `reminder-cron` Edge Function should be called every 30 minutes to:
- Send reminder emails to hosts at configured intervals (2h, 4h, 6h, 8h)
- Send end-of-day escalation emails to supervisors for unconfirmed visitors
- Prevent duplicate notifications

---

## Option 1: Supabase Cron (Recommended for Supabase Pro)

If you're on Supabase Pro plan, you can use native cron scheduling.

### Setup via Supabase Dashboard

1. **Enable pg_cron Extension**
   ```sql
   -- Run in SQL Editor
   CREATE EXTENSION IF NOT EXISTS pg_cron;
   ```

2. **Create Cron Job**
   ```sql
   -- Schedule reminder-cron to run every 30 minutes
   SELECT cron.schedule(
     'visitor-reminders',
     '*/30 * * * *',
     $$
     SELECT net.http_post(
       url:='https://YOUR_PROJECT_ID.supabase.co/functions/v1/reminder-cron',
       headers:=jsonb_build_object(
         'Authorization', 'Bearer YOUR_SERVICE_ROLE_KEY',
         'Content-Type', 'application/json'
       ),
       body:='{}'::jsonb
     );
     $$
   );
   ```

3. **Replace Placeholders**
   - `YOUR_PROJECT_ID`: Your Supabase project reference
   - `YOUR_SERVICE_ROLE_KEY`: Your service role key (from project settings)

4. **Verify Job Created**
   ```sql
   SELECT * FROM cron.job;
   ```

### Manage Cron Jobs

**View all cron jobs:**
```sql
SELECT * FROM cron.job;
```

**View job execution history:**
```sql
SELECT * FROM cron.job_run_details
ORDER BY start_time DESC
LIMIT 10;
```

**Unschedule a job:**
```sql
SELECT cron.unschedule('visitor-reminders');
```

**Update schedule:**
```sql
-- First unschedule
SELECT cron.unschedule('visitor-reminders');

-- Then reschedule with new timing
SELECT cron.schedule(
  'visitor-reminders',
  '*/15 * * * *',  -- Every 15 minutes
  $$ ... $$
);
```

---

## Option 2: External Cron Service (Works on Any Plan)

Use an external service to call your Edge Function on schedule.

### A. Using cron-job.org (Free)

1. **Go to**: https://cron-job.org
2. **Create free account**
3. **Create new cron job**:
   - **Title**: `SAP Visitor Reminders`
   - **Address**: `https://YOUR_PROJECT_ID.supabase.co/functions/v1/reminder-cron`
   - **Schedule**: Every 30 minutes
   - **Request method**: POST
   - **Headers**: Add header
     - Name: `Authorization`
     - Value: `Bearer YOUR_SERVICE_ROLE_KEY`
4. **Save and enable**

### B. Using EasyCron (Free tier available)

1. **Go to**: https://www.easycron.com
2. **Sign up for free account**
3. **Create new cron job**:
   - **URL**: `https://YOUR_PROJECT_ID.supabase.co/functions/v1/reminder-cron`
   - **Cron Expression**: `*/30 * * * *`
   - **HTTP Method**: POST
   - **Custom Headers**:
     ```
     Authorization: Bearer YOUR_SERVICE_ROLE_KEY
     Content-Type: application/json
     ```
4. **Save and enable**

### C. Using Your Own Server

If you have a Linux server with cron:

1. **Create script**: `/opt/visitor-cron/reminder.sh`
   ```bash
   #!/bin/bash

   SUPABASE_URL="https://YOUR_PROJECT_ID.supabase.co"
   SERVICE_KEY="YOUR_SERVICE_ROLE_KEY"

   curl -X POST "${SUPABASE_URL}/functions/v1/reminder-cron" \
     -H "Authorization: Bearer ${SERVICE_KEY}" \
     -H "Content-Type: application/json" \
     -d '{}' \
     --silent \
     --output /var/log/visitor-cron/reminder-$(date +\%Y\%m\%d-\%H\%M).log
   ```

2. **Make executable**:
   ```bash
   chmod +x /opt/visitor-cron/reminder.sh
   ```

3. **Add to crontab**:
   ```bash
   crontab -e
   ```

   Add line:
   ```
   */30 * * * * /opt/visitor-cron/reminder.sh
   ```

4. **Create log directory**:
   ```bash
   mkdir -p /var/log/visitor-cron
   ```

### D. Using GitHub Actions (Free for public repos)

Create `.github/workflows/visitor-cron.yml`:

```yaml
name: Visitor Reminders Cron

on:
  schedule:
    # Every 30 minutes
    - cron: '*/30 * * * *'
  workflow_dispatch:  # Allow manual trigger

jobs:
  trigger-reminders:
    runs-on: ubuntu-latest
    steps:
      - name: Call reminder Edge Function
        run: |
          curl -X POST ${{ secrets.SUPABASE_URL }}/functions/v1/reminder-cron \
            -H "Authorization: Bearer ${{ secrets.SUPABASE_SERVICE_KEY }}" \
            -H "Content-Type: application/json" \
            -d '{}'
```

Add secrets in GitHub repository settings:
- `SUPABASE_URL`: Your Supabase project URL
- `SUPABASE_SERVICE_KEY`: Your service role key

---

## Option 3: Cloud-Based Scheduled Tasks

### A. AWS EventBridge

1. **Create EventBridge Rule**:
   - Schedule: `cron(0,30 * * * ? *)`  # Every 30 minutes
   - Target: API Gateway or Lambda → Supabase Edge Function

2. **Configure Lambda** (if using):
   ```javascript
   const https = require('https');

   exports.handler = async (event) => {
     const options = {
       hostname: 'YOUR_PROJECT_ID.supabase.co',
       path: '/functions/v1/reminder-cron',
       method: 'POST',
       headers: {
         'Authorization': 'Bearer YOUR_SERVICE_ROLE_KEY',
         'Content-Type': 'application/json'
       }
     };

     return new Promise((resolve, reject) => {
       const req = https.request(options, (res) => {
         resolve({ statusCode: res.statusCode });
       });
       req.on('error', reject);
       req.write('{}');
       req.end();
     });
   };
   ```

### B. Azure Logic Apps

1. **Create Logic App**
2. **Add Recurrence trigger**: Every 30 minutes
3. **Add HTTP action**:
   - Method: POST
   - URI: `https://YOUR_PROJECT_ID.supabase.co/functions/v1/reminder-cron`
   - Headers:
     - `Authorization`: `Bearer YOUR_SERVICE_ROLE_KEY`
     - `Content-Type`: `application/json`
   - Body: `{}`

### C. Google Cloud Scheduler

1. **Create Cloud Scheduler Job**:
   - Frequency: `*/30 * * * *`
   - Target type: HTTP
   - URL: `https://YOUR_PROJECT_ID.supabase.co/functions/v1/reminder-cron`
   - HTTP method: POST
   - Headers:
     ```
     Authorization: Bearer YOUR_SERVICE_ROLE_KEY
     Content-Type: application/json
     ```
   - Body: `{}`

---

## Testing Your Cron Setup

### Manual Test

Call the function directly to verify it works:

```bash
curl -X POST https://YOUR_PROJECT_ID.supabase.co/functions/v1/reminder-cron \
  -H "Authorization: Bearer YOUR_SERVICE_ROLE_KEY" \
  -H "Content-Type: application/json" \
  -d '{}'
```

**Expected response**:
```json
{
  "success": true,
  "message": "Reminder cron executed successfully",
  "reminders_sent": 0,
  "escalations_sent": 0
}
```

### Verify Execution

Check the notifications table in your database:

```sql
SELECT
  type,
  recipient_email,
  sent_at,
  status
FROM notifications
WHERE sent_at >= NOW() - INTERVAL '1 hour'
ORDER BY sent_at DESC;
```

### Monitor Edge Function Logs

In Supabase Dashboard:
1. Go to **Edge Functions**
2. Select **reminder-cron**
3. View **Logs** tab
4. Check for successful executions and errors

---

## Cron Expression Examples

```
*/30 * * * *   - Every 30 minutes
*/15 * * * *   - Every 15 minutes
0 * * * *      - Every hour at :00
0 */2 * * *    - Every 2 hours
0 9-17 * * *   - Every hour from 9 AM to 5 PM
0 9,12,15,18 * * *  - At 9 AM, 12 PM, 3 PM, 6 PM
```

---

## Configuration in Settings

The cron job behavior is controlled by settings in your database:

### View Current Settings

```sql
SELECT setting_key, setting_value, description
FROM settings
WHERE setting_key IN (
  'reminder_intervals',
  'end_of_day_time',
  'enable_email_notifications'
);
```

### Update Settings via Admin Panel

1. Login to admin dashboard
2. Go to **Settings** view
3. Modify:
   - **Reminder Intervals**: When to send reminders (in minutes)
   - **End of Day Time**: When to send escalation (HH:MM format)
   - **Enable Email Notifications**: Toggle on/off

### Update Settings via SQL

```sql
-- Update reminder intervals (2h, 4h, 6h, 8h)
UPDATE settings
SET setting_value = '[120, 240, 360, 480]'
WHERE setting_key = 'reminder_intervals';

-- Update end-of-day time
UPDATE settings
SET setting_value = '18:00'
WHERE setting_key = 'end_of_day_time';

-- Disable notifications temporarily
UPDATE settings
SET setting_value = 'false'
WHERE setting_key = 'enable_email_notifications';
```

---

## Troubleshooting

### Cron Job Not Running

**Check:**
1. Verify cron service is active (for server-based)
2. Check external service dashboard for errors
3. Verify Edge Function URL is correct
4. Confirm service role key is valid
5. Test manual execution with curl

### Reminders Not Being Sent

**Check:**
1. Settings: `enable_email_notifications` is `true`
2. Visitors exist that need reminders
3. Microsoft Graph API configured correctly
4. Check notifications table for errors
5. Review Edge Function logs

### Duplicate Reminders

**Should not happen** - function checks for recent notifications:
- Won't send reminder if one was sent in last 60 minutes
- Check logic in `reminder-cron/index.ts`

### Escalations Not Sent

**Check:**
1. Current time matches `end_of_day_time` setting (±30 min window)
2. Visitors exist that are past expected duration
3. Supervisor emails configured in settings
4. Edge Function has completed Microsoft Graph setup

---

## Cost Considerations

### Supabase Free Plan
- Edge Functions: 500,000 invocations/month
- At 30-min intervals: ~1,440 calls/month
- **Cost**: FREE ✅

### Supabase Pro Plan
- Edge Functions: 2,000,000 invocations/month
- Native pg_cron available
- **Cost**: Included in plan ✅

### External Services
- **cron-job.org**: Free tier sufficient
- **EasyCron**: Free tier includes 10 jobs
- **GitHub Actions**: 2,000 minutes/month free
- **AWS/Azure/GCP**: Minimal cost (< $1/month)

---

## Production Checklist

- [ ] Cron job scheduled (every 30 minutes)
- [ ] Manual test successful
- [ ] Settings configured (intervals, EOD time, supervisor emails)
- [ ] Email notifications enabled
- [ ] Microsoft Graph API working
- [ ] Edge Function logs monitored
- [ ] Test reminders received
- [ ] Test escalations received
- [ ] Monitoring/alerting set up for failures
- [ ] Documentation shared with team
- [ ] Backup cron method configured (recommended)

---

## Monitoring & Alerts

### Set Up Alerts

**Supabase Dashboard**:
- Go to Logs
- Create alert for Edge Function failures
- Email notification on repeated failures

**Custom Monitoring**:
```sql
-- Check for recent cron failures
SELECT COUNT(*) as failed_notifications
FROM notifications
WHERE sent_at >= NOW() - INTERVAL '1 day'
  AND status = 'failed';
```

### Health Check Query

```sql
-- Verify cron is running (should have recent entries)
SELECT
  MAX(sent_at) as last_notification,
  NOW() - MAX(sent_at) as time_since_last,
  COUNT(*) as total_today
FROM notifications
WHERE sent_at >= CURRENT_DATE;
```

If `time_since_last` > 2 hours, investigate cron job.

---

## Summary

Once set up:

✅ Runs automatically every 30 minutes
✅ Sends reminders at configured intervals (2h, 4h, 6h, 8h)
✅ Sends end-of-day escalations to supervisors
✅ Prevents duplicate notifications
✅ Fully automated - no manual intervention needed
✅ Configurable via admin settings panel
✅ Complete audit trail in notifications table

Choose the cron method that best fits your infrastructure!
