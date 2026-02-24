# SAP Visitor Management System - Implementation Plan

## Project Overview
Replace paper visitor logbook with a modern digital check-in/check-out system featuring kiosk interface, QR code access, host notifications, and supervisor dashboard.

## Tech Stack
- **Backend**: PHP 8.2 (LAMP stack)
- **Database**: MySQL 8.0
- **Frontend**: HTML5, Tailwind CSS, Vanilla JS
- **Email**: Microsoft Graph API / SMTP
- **Hosting**: OVH (VPS or shared hosting)
- **Security**: HTTPS, role-based access, GDPR compliance

## Database Schema

```sql
-- visitors table
CREATE TABLE visitors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    company VARCHAR(150),
    reason TEXT,
    host_email VARCHAR(255) NOT NULL,
    host_name VARCHAR(150),
    visitor_email VARCHAR(255), -- optional
    arrival_time DATETIME NOT NULL,
    expected_duration INT DEFAULT 180, -- minutes (3h default)
    departure_time DATETIME,
    status ENUM('checked_in', 'checked_out', 'unconfirmed', 'manual_close') DEFAULT 'checked_in',
    checkin_method ENUM('kiosk', 'qr_mobile') DEFAULT 'kiosk',
    checkout_method ENUM('qr_rescan', 'host_confirmed', 'manual_admin') DEFAULT NULL,
    qr_token VARCHAR(64) UNIQUE, -- for checkout via QR
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_arrival_time (arrival_time),
    INDEX idx_status (status),
    INDEX idx_host_email (host_email)
);

-- hosts table (managed list)
CREATE TABLE hosts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    name VARCHAR(150) NOT NULL,
    department VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- notifications log
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    visitor_id INT NOT NULL,
    type ENUM('arrival', 'reminder', 'escalation', 'checkout') NOT NULL,
    recipient_email VARCHAR(255) NOT NULL,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('sent', 'failed', 'pending') DEFAULT 'pending',
    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE
);

-- audit log
CREATE TABLE audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(100) NOT NULL,
    user_email VARCHAR(255),
    visitor_id INT,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at)
);

-- system settings
CREATE TABLE settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value) VALUES
('reminder_intervals', '[120, 240, 360, 480]'), -- 2,4,6,8 hours in minutes
('default_duration', '180'), -- 3 hours
('end_of_day_time', '18:00'),
('data_retention_days', '365'),
('site_name', 'SAP Office'),
('evacuation_plan_url', '');
```

## Directory Structure

```
/var/www/visitors/
├── index.php                    # Kiosk main page (Arrivée/Sortie)
├── checkin.php                  # Check-in form
├── checkout.php                 # Checkout form (QR scan)
├── confirmation.php             # Success/thank you pages
├── api/
│   ├── checkin.php             # API: process check-in
│   ├── checkout.php            # API: process checkout
│   ├── verify-qr.php           # API: verify QR token
│   └── notify.php              # API: trigger notifications
├── admin/
│   ├── index.php               # Admin login
│   ├── dashboard.php           # Dashboard with stats
│   ├── visitors.php            # List visitors (now, today, history)
│   ├── export.php              # CSV export
│   ├── hosts.php               # Manage hosts
│   └── settings.php            # System settings
├── includes/
│   ├── config.php              # Database config
│   ├── db.php                  # Database connection
│   ├── auth.php                # Authentication helpers
│   ├── email.php               # Email service (Graph API)
│   └── helpers.php             # Utility functions
├── assets/
│   ├── css/
│   │   └── style.css           # Tailwind + custom styles
│   ├── js/
│   │   ├── kiosk.js            # Kiosk interactions
│   │   └── admin.js            # Admin panel JS
│   └── images/
│       └── logo.png
├── cron/
│   ├── reminders.php           # Send reminder emails
│   └── cleanup.php             # Data retention cleanup
├── templates/
│   ├── emails/
│   │   ├── arrival.php         # Host arrival notification
│   │   ├── reminder.php        # Reminder to host
│   │   ├── escalation.php      # End of day to supervisors
│   │   └── checkout.php        # Visitor checkout confirmation
│   └── layout/
│       ├── kiosk.php           # Kiosk layout wrapper
│       └── admin.php           # Admin layout wrapper
└── .htaccess                   # URL rewriting, security
```

## Key Features Implementation

### 1. Kiosk Interface (index.php)
- Large touch-friendly tiles: "Arrivée" and "Sortie"
- Full-screen kiosk mode
- Auto-redirect to checkin/checkout forms
- Language toggle (FR/EN)

### 2. Check-in Flow (checkin.php)
- Form fields:
  - Prénom (First name)
  - Nom (Last name)
  - Société (Company)
  - Motif de visite (Reason)
  - Hôte (Host - dropdown from hosts table + email input)
  - Email visiteur (optional)
  - Durée prévue (Expected duration - 2h/4h/6h/8h dropdown, default 3h)
- Auto-capture: arrival timestamp
- Generate unique QR token for checkout
- Send email to host with visitor details + action buttons
- Success screen with QR code for visitor to save

### 3. Checkout Flow (checkout.php)
- Option A: Rescan QR code → auto checkout
- Option B: Manual entry (name + company) → find active visit
- Confirmation screen
- Send email to visitor (if email provided) with evacuation confirmation

### 4. Host Email Actions
- Email contains: visitor details, arrival time, two buttons
- Buttons link to: `/host-action.php?token=xxx&action=still_here` or `left`
- Updates visit status in database
- Optional: Link to extend duration

### 5. Reminder System (cron/reminders.php)
- Runs every 15 minutes via cron
- Checks for visitors with status='checked_in'
- For each reminder interval (2h, 4h, 6h, 8h), send email if not already sent
- Track sent reminders in notifications table

### 6. End-of-Day Escalation (cron/reminders.php)
- Runs at configured EOD time (default 18:00)
- Finds all 'checked_in' visitors with no host confirmation
- Sends escalation email to supervisors with list
- Marks as 'unconfirmed' in system

### 7. Admin Dashboard (admin/)
- Login with Microsoft 365 SSO or local auth
- Dashboard cards: Today's visits, Currently on-site, Avg dwell time
- Visitors list with filters (date range, host, company, status)
- "Now" view: Who is on-site now
- CSV export with date range
- Host management (CRUD)
- Settings panel
- Audit log view

### 8. Security & GDPR
- HTTPS enforced
- Input sanitization (prepared statements)
- CSRF tokens on forms
- Role-based access (Admin full, Supervisor read-only)
- Data retention: Auto-anonymize after 365 days
- Visitor email optional (GDPR-friendly)
- Audit logging for all admin actions

## Email Templates (French)

### Host Arrival Notification
```
Subject: [VISITEUR ARRIVÉ] {first_name} {last_name} - {company}

Bonjour {host_name},

Un visiteur est arrivé pour vous :

Nom: {first_name} {last_name}
Société: {company}
Motif: {reason}
Heure d'arrivée: {arrival_time}
Durée prévue: {expected_duration} heures

Ce visiteur est-il toujours sur site?
[TOUJOURS LÀ]  [PARTI]

Si vous ne confirmez pas de départ avant {end_of_day},
les chefs de car seront notifiés.

---
Système de gestion des visiteurs SAP
```

### Reminder Email
```
Subject: [RAPPEL] Visiteur sur site depuis {duration}

Bonjour {host_name},

Rappel : {first_name} {last_name} ({company}) est sur site depuis {duration}.

Confirmez son statut :
[TOUJOURS LÀ]  [PARTI]
```

### Escalation Email (to Supervisors)
```
Subject: [FIN DE JOURNÉE] Visites non confirmées

Bonjour,

Les visites suivantes n'ont pas été confirmées comme terminées :

{list_of_visitors}

Merci de vérifier si ces personnes sont encore sur site.

---
Système de gestion des visiteurs SAP
```

## API Endpoints

```
POST /api/checkin.php
  Body: {first_name, last_name, company, reason, host_email, visitor_email?, expected_duration}
  Response: {success, visitor_id, qr_token, qr_url}

POST /api/checkout.php
  Body: {qr_token OR visitor_id, method}
  Response: {success, message}

GET /api/verify-qr.php?token=xxx
  Response: {valid, visitor_details}

POST /api/notify.php
  Body: {visitor_id, type, recipient}
  Response: {success, message}
```

## Cron Jobs

```bash
# Reminders every 15 minutes
*/15 * * * * /usr/bin/php /var/www/visitors/cron/reminders.php

# Daily escalation at 18:00
0 18 * * * /usr/bin/php /var/www/visitors/cron/reminders.php --escalation

# Weekly data cleanup (Sundays at 2am)
0 2 * * 0 /usr/bin/php /var/www/visitors/cron/cleanup.php
```

## Frontend Design (Tailwind CSS)

### Kiosk Theme
- Large buttons (min-height: 200px)
- High contrast colors (SAP blue: #008FD3)
- Touch-friendly spacing (min 48px tap targets)
- Font sizes: 24px+ for kiosk, 16px for mobile
- Dark mode support for kiosk screens

### Mobile/QR Theme
- Responsive, single column
- Fast load, minimal JS
- Large input fields
- Clear progress indicators

## Installation Steps

1. Create database and import schema
2. Configure database credentials in includes/config.php
3. Set up Microsoft Graph API credentials for email
4. Configure cron jobs
5. Set up virtual host with HTTPS
6. Create initial admin user
7. Import host list
8. Configure settings
9. Print QR codes for meeting rooms
10. Deploy to OVH

## Testing Checklist

- [ ] Check-in via kiosk
- [ ] Check-in via mobile QR
- [ ] Host receives arrival email
- [ ] Host can confirm departure
- [ ] Visitor can checkout via QR rescan
- [ ] Reminders send at correct intervals
- [ ] End-of-day escalation works
- [ ] Admin dashboard shows correct data
- [ ] CSV export works
- [ ] GDPR: data anonymizes after retention period
- [ ] Audit log captures all actions
- [ ] Role-based access works
- [ ] HTTPS enforced
- [ ] Kiosk is touch-friendly
- [ ] Mobile is responsive

## Future Enhancements (Phase 2)

- SMS notifications (Twilio integration)
- WhatsApp Business API
- Badge printing (Brother QL printer)
- Pre-registration portal
- Integration with SAP's existing systems
- Multi-site support
- Real-time dashboard with WebSockets
- Visitor photo capture
- ID scanning
- Training quiz for visitors
