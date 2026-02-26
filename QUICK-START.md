# SAP Visitor Management - Quick Start Guide

Complete visitor management system with QR codes, email notifications, and admin dashboard.

---

## ✨ What's Included

- ✅ **QR Code Check-in/Out** - Visitors scan codes to enter and exit
- ✅ **Email Notifications** - Hosts get notified automatically
- ✅ **Real-time Dashboard** - See who's on-site right now
- ✅ **Automated Reminders** - System follows up with hosts
- ✅ **CSV Export** - Download visitor logs
- ✅ **Manual Checkout** - Admin override when needed
- ✅ **Printable QR Posters** - For meeting rooms
- ✅ **Multi-language** - French & English support
- ✅ **GDPR Compliant** - Minimal data collection, retention policies

---

## 🚀 Quick Setup (5 Steps)

### 1. Database is Ready ✅
Your Supabase database is already set up with all tables and security.

### 2. Install Dependencies
```bash
npm install
```

### 3. Build the Application
```bash
npm run build
```

### 4. Deploy Edge Functions
Edge functions are already deployed:
- ✅ `send-notification` - Sends emails
- ✅ `reminder-cron` - Automated reminders

### 5. Configure Email (Required)
Follow [MICROSOFT-GRAPH-SETUP.md](./MICROSOFT-GRAPH-SETUP.md) to enable email notifications.

**TL;DR:**
1. Register app in Azure AD
2. Grant `Mail.Send` permission
3. Add secrets to Supabase:
   - `MICROSOFT_CLIENT_ID`
   - `MICROSOFT_TENANT_ID`
   - `MICROSOFT_CLIENT_SECRET`
   - `FROM_EMAIL`

---

## 🎯 Usage

### For Visitors

**Check-in:**
1. Tap "Arrivée" on kiosk or scan QR code
2. Enter: Name, Company, Reason, Host
3. Save the QR code shown

**Check-out:**
1. Scan your QR code
2. Or enter name manually
3. Done!

### For Hosts

**Email Notifications:**
- Get email when visitor arrives
- Click "Still on site" or "Departed"
- Get reminders if visitor stays long

### For Admins

**Dashboard Access:**
- Go to: `https://yourdomain.com/#/admin`
- Login with admin credentials
- See real-time visitor status

**Features:**
- **Dashboard** - Overview stats
- **Now View** - Who's on-site (auto-refreshes)
- **All Visitors** - Complete log
- **QR Posters** - Print for meeting rooms
- **Settings** - Configure system
- **Export** - Download CSV reports

---

## ⚙️ Configuration

### Add Admin User

Run in Supabase SQL Editor:

```sql
INSERT INTO admin_users (email, name, role, password_hash, is_active)
VALUES (
  'admin@sap.com',
  'Admin User',
  'admin',
  'changeme',  -- Change this to a real password hash
  true
);
```

**Note**: This uses plain text password for demo. In production, use proper password hashing.

### Configure Settings

Login to admin dashboard → Settings:

- **Reminder Intervals**: `120, 240, 360, 480` (2h, 4h, 6h, 8h)
- **End of Day Time**: `18:00`
- **Supervisor Emails**: `supervisor@sap.com, manager@sap.com`
- **Enable Notifications**: `true`

### Add Hosts

Via SQL:

```sql
INSERT INTO hosts (email, name, department, is_active)
VALUES
  ('john.doe@sap.com', 'John Doe', 'IT', true),
  ('jane.smith@sap.com', 'Jane Smith', 'HR', true);
```

Or use admin interface (coming in Phase 2).

---

## 🔄 Automated Reminders

Set up cron job to call `reminder-cron` every 30 minutes.

**Quick Setup - Using cron-job.org (Free):**

1. Go to https://cron-job.org
2. Create account
3. Add cron job:
   - URL: `https://your-project.supabase.co/functions/v1/reminder-cron`
   - Schedule: Every 30 minutes
   - Method: POST
   - Header: `Authorization: Bearer YOUR_SERVICE_KEY`
4. Enable and save

See [CRON-SETUP.md](./CRON-SETUP.md) for other options.

---

## 📊 How It Works

### Check-in Flow

```
Visitor Arrives
    ↓
Fills Form (Kiosk/Mobile)
    ↓
System Generates QR Code
    ↓
Email Sent to Host ✉️
    ↓
Visitor Saves QR Code 📱
```

### Check-out Flow

```
Visitor Leaving
    ↓
Scans QR Code 📷
    ↓
System Records Departure
    ↓
Email Sent to Host (optional) ✉️
```

### Reminder System

```
Visitor Checked In
    ↓
Time Passes (2h, 4h, 6h, 8h)
    ↓
Cron Job Runs
    ↓
Reminder Email to Host ✉️
    ↓
Host Confirms Status
```

### End-of-Day Escalation

```
End of Day (18:00)
    ↓
Cron Job Checks Visitors
    ↓
Finds Unconfirmed Visitors
    ↓
Email to Supervisors ✉️
    ↓
Supervisors Take Action
```

---

## 🧪 Testing Checklist

### Test Check-in
- [ ] Open kiosk at `/#/`
- [ ] Click "Arrivée"
- [ ] Fill form and submit
- [ ] QR code displays
- [ ] Download QR works
- [ ] Data in database: `SELECT * FROM visitors;`

### Test Check-out
- [ ] Go to `/#/checkout`
- [ ] Click "Start Scanner"
- [ ] Allow camera access
- [ ] Scan QR code from check-in
- [ ] Confirmation shown
- [ ] Status updated in database

### Test Admin
- [ ] Login at `/#/admin`
- [ ] Dashboard shows stats
- [ ] "Now View" shows visitor
- [ ] Manual checkout works
- [ ] CSV export downloads
- [ ] QR poster generates
- [ ] Settings save successfully

### Test Emails (Once Configured)
- [ ] Check-in triggers arrival email
- [ ] Host receives email with buttons
- [ ] Click "Departed" marks as checked out
- [ ] Click "Still on site" logs confirmation
- [ ] Manual trigger reminder:
  ```bash
  curl -X POST https://your-project.supabase.co/functions/v1/reminder-cron \
    -H "Authorization: Bearer YOUR_SERVICE_KEY"
  ```

---

## 📁 Project Structure

```
/
├── src/
│   ├── pages/           # View components
│   │   ├── home.js      # Kiosk home (tiles)
│   │   ├── checkin.js   # Check-in form
│   │   ├── checkout.js  # Check-out scanner
│   │   ├── confirmation.js  # Success page with QR
│   │   ├── confirm-action.js  # Host email actions
│   │   └── admin.js     # Admin dashboard
│   ├── styles/
│   │   └── main.css     # All styles
│   ├── i18n.js          # Translations (FR/EN)
│   ├── router.js        # SPA routing
│   ├── supabase.js      # Database client
│   └── main.js          # App entry point
│
├── supabase/
│   ├── functions/
│   │   ├── send-notification/  # Email sender
│   │   └── reminder-cron/      # Automated reminders
│   └── migrations/
│       └── 20260224214436_create_visitor_management_schema.sql
│
├── dist/                # Built files (after npm run build)
├── index.html           # Main HTML file
├── package.json         # Dependencies
├── vite.config.js       # Build configuration
│
└── Documentation:
    ├── IMPLEMENTATION-SUMMARY.md    # Feature overview
    ├── MICROSOFT-GRAPH-SETUP.md     # Email configuration
    ├── CRON-SETUP.md                # Automated reminders setup
    └── QUICK-START.md               # This file
```

---

## 🗄️ Database Tables

- **visitors** - Check-in/out records
- **hosts** - Employee list
- **notifications** - Email log
- **action_tokens** - Email link tokens
- **audit_log** - Admin actions
- **settings** - System config
- **admin_users** - Admin accounts
- **data_retention_log** - GDPR compliance

All tables have Row Level Security (RLS) enabled.

---

## 🔒 Security Features

- **Public Access** (Kiosk):
  - Can check-in visitors
  - Can check-out by QR or name
  - Cannot see other visitors
  - Cannot access admin

- **Email Actions** (Hosts):
  - Secure tokens (24h expiry)
  - One-time use
  - No login required
  - Audit logged

- **Admin Dashboard**:
  - Login required
  - Role-based access
  - All actions logged
  - Session management

- **Data Protection**:
  - Minimal PII collected
  - Configurable retention
  - Automatic anonymization
  - GDPR compliant

---

## 🎨 Customization

### Branding

Update in `settings` table:

```sql
UPDATE settings SET setting_value = 'Your Company'
WHERE setting_key = 'company_name';

UPDATE settings SET setting_value = 'Your Site Name'
WHERE setting_key = 'site_name';
```

### Colors

Edit CSS variables in `src/styles/main.css`:

```css
:root {
  --sap-blue: #008FD3;      /* Primary color */
  --sap-green: #107E3E;     /* Success color */
  --sap-orange: #E9730C;    /* Warning color */
  /* ... */
}
```

### Languages

Add/edit translations in `src/i18n.js`:

```javascript
const translations = {
  fr: { /* French translations */ },
  en: { /* English translations */ }
};
```

### Email Templates

Edit in `supabase/functions/send-notification/index.ts`:

```typescript
function buildEmailContent(payload, baseUrl) {
  // Modify HTML email templates here
}
```

---

## 📞 Support & Troubleshooting

### Common Issues

**QR Scanner Not Working**
- Check camera permissions in browser
- Use HTTPS (required for camera access)
- Fallback: Use manual entry

**Emails Not Sending**
- Verify Microsoft Graph API setup
- Check Edge Function logs
- Test with curl command
- Review [MICROSOFT-GRAPH-SETUP.md](./MICROSOFT-GRAPH-SETUP.md)

**Admin Can't Login**
- Check admin_users table has entry
- Verify email and password
- Check is_active = true

**Visitor Not Found on Checkout**
- Check visitor is status='checked_in'
- Verify QR token matches
- Check visitors table in database

### Getting Help

1. **Check Documentation**:
   - [IMPLEMENTATION-SUMMARY.md](./IMPLEMENTATION-SUMMARY.md)
   - [MICROSOFT-GRAPH-SETUP.md](./MICROSOFT-GRAPH-SETUP.md)
   - [CRON-SETUP.md](./CRON-SETUP.md)

2. **Check Logs**:
   - Supabase Dashboard → Edge Functions → Logs
   - Browser Console (F12)
   - Database: `SELECT * FROM audit_log ORDER BY created_at DESC;`

3. **Verify Configuration**:
   ```sql
   -- Check settings
   SELECT * FROM settings;

   -- Check recent visitors
   SELECT * FROM visitors ORDER BY arrival_time DESC LIMIT 5;

   -- Check notifications
   SELECT * FROM notifications ORDER BY sent_at DESC LIMIT 5;
   ```

---

## 🚀 Going Live

### Pre-Launch Checklist

- [ ] Database schema applied
- [ ] Edge Functions deployed
- [ ] Microsoft Graph API configured
- [ ] Cron job scheduled
- [ ] Admin user created
- [ ] Hosts added to database
- [ ] Settings configured
- [ ] Supervisor emails added
- [ ] Test check-in works
- [ ] Test check-out works
- [ ] Test emails arrive
- [ ] Test QR scanner works
- [ ] Admin dashboard accessible
- [ ] CSV export works
- [ ] Print QR posters for rooms
- [ ] Train reception staff
- [ ] Monitor first day

### Day 1 Monitoring

Check every hour:
- Visitors can check in
- QR codes generate
- Emails arrive
- Check-out works
- Admin dashboard updates
- No errors in logs

---

## 📈 Phase 2 Features (Future)

- SMS notifications (Twilio)
- WhatsApp Business API
- Badge printing
- Pre-registration portal
- Photo capture
- ID scanning
- Training quiz
- Mobile app for hosts
- Multi-site support
- Analytics dashboard
- Visitor history

---

## 📄 License

Internal SAP use only. Proprietary and confidential.

---

## 🎉 You're Ready!

Your visitor management system is fully functional. Just configure Microsoft Graph API for emails and you're good to go!

**Need help?** Review the documentation files in this directory.

**Questions?** Check the troubleshooting section above.

**Ready to launch?** Follow the Pre-Launch Checklist!

---

**Welcome to modern visitor management! 🚀**
