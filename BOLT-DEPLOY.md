# Deploy to Bolt

## Quick Deploy (One-Click)

[![Deploy to Bolt](https://bolt.new/button.svg)](https://bolt.new/github.com/yourusername/SAPvisitorsystem)

*(Replace with your actual GitHub username after pushing)*

## Method 1: GitHub Import (Recommended)

### Step 1: Push to GitHub
```bash
# Create a new repository on GitHub, then:
git remote add origin https://github.com/yourusername/SAPvisitorsystem.git
git branch -M main
git push -u origin main
```

### Step 2: Import to Bolt
1. Go to [bolt.new](https://bolt.new)
2. Click "Import from GitHub"
3. Enter your repository URL: `https://github.com/yourusername/SAPvisitorsystem`
4. Bolt will automatically detect PHP and configure the environment

### Step 3: Configure Environment
Bolt will create a `.env` file automatically, but you can customize:
```
APP_ENV=production
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your-secure-password
SITE_URL=https://your-project.bolt.app
```

## Method 2: Manual File Upload

### Step 1: Create New Project
1. Go to [bolt.new](https://bolt.new)
2. Click "New Project"
3. Select "PHP" as the template

### Step 2: Upload Files
Create these files in Bolt:

**File Structure:**
```
/
├── index.php
├── checkin.php
├── checkout.php
├── confirmation.php
├── host-action.php
├── api/
│   ├── checkin.php
│   ├── checkout.php
│   └── verify-qr.php
├── admin/
│   ├── index.php
│   ├── dashboard.php
│   ├── visitors.php
│   ├── export.php
│   └── logout.php
├── includes/
│   ├── config.php
│   ├── db-sqlite.php (use this instead of db.php)
│   ├── helpers.php
│   └── email.php
├── templates/
│   ├── layout/
│   │   └── kiosk.php
│   └── emails/
│       ├── arrival.php
│       ├── reminder.php
│       ├── escalation.php
│       └── checkout.php
├── assets/
│   ├── css/
│   │   └── kiosk.css
│   └── js/
│       ├── kiosk.js
│       └── qr-scanner.js
└── data/
    └── (SQLite database will be created here)
```

### Step 3: Use SQLite Database
Replace `includes/db.php` with `includes/db-sqlite.php` in your requires:

```php
// Change this:
require_once __DIR__ . '/../includes/db.php';

// To this:
require_once __DIR__ . '/../includes/db-sqlite.php';
```

## Method 3: Zip Upload

### Step 1: Create Zip
```bash
# Exclude unnecessary files
zip -r sap-visitors.zip . -x \
  ".git/*" \
  ".claude/*" \
  "docker-compose.yml" \
  "Dockerfile" \
  "docker-entrypoint.sh" \
  ".env" \
  "logs/*" \
  "uploads/*" \
  "cache/*"
```

### Step 2: Upload to Bolt
1. Go to [bolt.new](https://bolt.new)
2. Click "Upload"
3. Select your `sap-visitors.zip` file

## Bolt Configuration

The `bolt.json` file is included with these settings:
- **PHP Version**: 8.2
- **Server**: Built-in PHP server on port 3000
- **Database**: SQLite (automatically configured)
- **Extensions**: PDO, PDO_SQLite, MySQLi, MBString, GD

## Post-Deployment

### Default Login
- **Admin URL**: `/admin/`
- **Username**: `admin@example.com`
- **Password**: `changeme` (change this immediately!)

### Sample Hosts
The system comes with 5 sample hosts pre-loaded:
- Jean Dupont (IT)
- Marie Martin (HR)
- Pierre Bernard (Finance)
- Sophie Petit (Marketing)
- Lucas Moreau (Sales)

### Important Notes

1. **SQLite vs MySQL**: For Bolt deployment, SQLite is used (no external database needed)
2. **Email**: Email notifications are disabled by default in Bolt (configure SMTP or use webhook)
3. **Cron Jobs**: Reminders won't run automatically - you can trigger them manually or use a scheduled function
4. **File Uploads**: Uploaded files are stored in the `uploads/` directory

## Environment Variables

Set these in Bolt's environment settings:

| Variable | Default | Description |
|----------|---------|-------------|
| `ADMIN_USERNAME` | admin | Admin login username |
| `ADMIN_PASSWORD` | admin123 | Admin login password |
| `SITE_URL` | auto | Your Bolt app URL |
| `DEFAULT_LANGUAGE` | fr | Default language (fr/en) |
| `ENABLE_EMAIL` | false | Enable email notifications |

## Troubleshooting

### Permission Errors
```bash
# In Bolt terminal
chmod -R 777 data/
chmod -R 777 uploads/
chmod -R 777 logs/
```

### Database Locked
SQLite can get locked if multiple requests happen simultaneously. This is normal for demo purposes.

### Missing Extensions
If you see errors about missing extensions, check that `bolt.json` has the required PHP extensions listed.

## Support

- **Bolt Documentation**: https://bolt.new/docs
- **Issues**: Create an issue on GitHub
- **Demo**: [Try the live demo](https://your-demo-url.bolt.app)
