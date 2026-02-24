# SAP Visitor Management System

A modern digital visitor check-in/check-out system to replace paper logbooks, featuring kiosk interface, QR code access, host notifications, and supervisor dashboard.

## Features

- **Kiosk Interface**: Large touch-friendly tiles for check-in/check-out (Arrivée/Sortie)
- **QR Code Check-in**: Visitors scan QR codes to check in/out
- **Host Notifications**: Email notifications to hosts on visitor arrival
- **Reminders**: Automatic reminders at 2/4/6/8 hours
- **End-of-Day Escalation**: Alerts to supervisors for unconfirmed visitors
- **Admin Dashboard**: Real-time view of who's on-site, exports, settings
- **GDPR Compliant**: Minimal PII, data retention policies, audit logging

## Quick Start (Docker - Recommended)

The easiest way to run the application is with Docker:

```bash
# 1. Clone/navigate to the project
cd SAPvisitorsystem

# 2. Start the containers
docker-compose up -d

# 3. Access the application
# Kiosk:     http://localhost:8000
# Admin:     http://localhost:8000/admin/ (admin / admin123)
# phpMyAdmin: http://localhost:8080
```

To stop:
```bash
docker-compose down
```

To view logs:
```bash
docker-compose logs -f app
```

## Manual Setup (Without Docker)

### 1. Database Setup

```bash
mysql -u root -p < sql/schema.sql
```

### 2. Configuration

```bash
cp .env.example .env
# Edit .env with your database and email settings
```

### 3. Environment Variables

```bash
# Database
DB_HOST=localhost
DB_NAME=visitor_management
DB_USER=visitors_user
DB_PASS=your_secure_password

# Admin
ADMIN_USERNAME=admin
ADMIN_PASSWORD=changeme

# Microsoft Graph API (for email)
MS_GRAPH_TENANT_ID=your-tenant-id
MS_GRAPH_CLIENT_ID=your-client-id
MS_GRAPH_CLIENT_SECRET=your-client-secret
MS_GRAPH_FROM_EMAIL=visitors@yourdomain.com
```

### 4. Cron Jobs

```bash
# Reminders every 15 minutes
*/15 * * * * /usr/bin/php /var/www/visitors/cron/reminders.php

# End-of-day escalation at 18:00
0 18 * * * /usr/bin/php /var/www/visitors/cron/reminders.php --escalation

# Weekly cleanup
0 2 * * 0 /usr/bin/php /var/www/visitors/cron/cleanup.php
```

### 5. Web Server

Point your web server to the project root. Ensure:
- PHP 8.0+
- MySQL 8.0+
- HTTPS enabled
- mod_rewrite enabled (Apache)

## Usage

### Visitor Flow

1. **Check-in**: Visitor taps "Arrivée" → Fills form → Gets QR code
2. **Checkout**: Visitor taps "Sortie" → Scans QR code → Confirmed

### Host Actions

- Receives email on visitor arrival
- Can confirm "Still here" or "Departed"
- Gets reminders at 2/4/6/8 hours

### Admin Access

- Go to `/admin/` → Login
- Dashboard shows real-time stats
- View visitors, export CSV, manage settings

## File Structure

```
├── index.php              # Kiosk main page
├── checkin.php            # Check-in form
├── checkout.php           # Checkout with QR
├── confirmation.php       # Success pages
├── host-action.php        # Host email actions
├── api/                   # API endpoints
├── admin/                 # Admin panel
├── cron/                  # Scheduled jobs
├── includes/              # Core classes
├── templates/             # Layouts & emails
├── assets/                # CSS, JS, images
└── sql/                   # Database schema
```

## Security

- CSRF protection on all forms
- Input sanitization
- Role-based access (Admin/Supervisor)
- HTTPS enforced
- Audit logging
- GDPR-compliant data retention

## Testing

### Manual Test Flow

1. Open `http://localhost/index.php` (kiosk)
2. Tap "Arrivée" → Fill visitor form
3. Check QR code is generated
4. Open checkout in new tab
5. Scan/enter QR code → Confirm checkout
6. Check `/admin/` dashboard shows data

### Test Data

```sql
-- Add test hosts
INSERT INTO hosts (email, name, department) VALUES
('host1@company.com', 'Jean Dupont', 'IT'),
('host2@company.com', 'Marie Martin', 'RH');
```

## License

Internal use only - SAP Visitor Management System
