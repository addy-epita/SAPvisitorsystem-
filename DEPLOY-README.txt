================================================================================
SAP VISITOR MANAGEMENT SYSTEM - BOLT DEPLOYMENT PACKAGE
================================================================================

QUICK START
-----------
1. Go to https://bolt.new
2. Click "Upload" button
3. Select this zip file
4. Wait for deployment (takes ~1-2 minutes)
5. Click the preview link when ready

DEFAULT LOGIN
-------------
Admin Panel: /admin/
Username: admin@example.com
Password: changeme

IMPORTANT: Change the password after first login!

WHAT'S INCLUDED
---------------
✓ Complete visitor check-in/check-out system
✓ QR code generation and scanning
✓ Admin dashboard with real-time stats
✓ CSV export functionality
✓ SQLite database (auto-creates on first run)
✓ Sample hosts pre-loaded
✓ French/English language support

PRE-LOADED SAMPLE HOSTS
-----------------------
- Jean Dupont (IT) - jean.dupont@sap.com
- Marie Martin (HR) - marie.martin@sap.com
- Pierre Bernard (Finance) - pierre.bernard@sap.com
- Sophie Petit (Marketing) - sophie.petit@sap.com
- Lucas Moreau (Sales) - lucas.moreau@sap.com

FEATURES
--------
• Touch-friendly kiosk interface
• QR code check-in/out
• Email notifications (disabled by default)
• Automatic reminders (2/4/6/8 hours)
• End-of-day escalation
• GDPR compliant data handling
• Audit logging

CUSTOMIZATION
-------------
Edit these files after deployment:

1. .env - Environment variables (create from .env.example)
2. includes/config.php - Site settings
3. sql/schema.sql - Database schema (if needed)

ENVIRONMENT VARIABLES (Bolt)
----------------------------
Set these in Bolt's environment settings:

ADMIN_USERNAME=admin
ADMIN_PASSWORD=your-secure-password
SITE_URL=https://your-project.bolt.app
DEFAULT_LANGUAGE=fr
ENABLE_EMAIL=false

FILE STRUCTURE
--------------
/
├── index.php          # Kiosk main page
├── checkin.php        # Check-in form
├── checkout.php       # Checkout with QR
├── confirmation.php   # Success pages
├── admin/             # Admin panel
│   ├── index.php      # Login
│   ├── dashboard.php  # Dashboard
│   └── ...
├── api/               # API endpoints
├── includes/          # Core files
├── templates/         # Layouts
├── assets/            # CSS/JS
└── data/              # SQLite database

TROUBLESHOOTING
---------------

Problem: Database locked
Solution: This is normal for SQLite with concurrent requests. Refresh the page.

Problem: Permission denied
Solution: In Bolt terminal, run:
  chmod -R 777 data/ uploads/ logs/

Problem: Images not loading
Solution: Check that uploads/ directory has write permissions

Problem: QR scanner not working
Solution: Ensure camera permissions are granted in browser

SUPPORT
-------
For issues or questions:
1. Check BOLT-DEPLOY.md for detailed instructions
2. Review README.md for full documentation
3. Check the GitHub repository (if available)

TECHNICAL DETAILS
-----------------
- PHP Version: 8.2
- Database: SQLite (auto-setup)
- Framework: Vanilla PHP
- Frontend: Tailwind CSS
- QR Library: QRCode.js

SECURITY NOTES
--------------
⚠️ Change default admin password immediately!
⚠️ This is a demo/starter template - review security before production use
⚠️ SQLite is suitable for demo/small deployments - use MySQL for production

LICENSE
-------
Internal use only - SAP Visitor Management System

================================================================================
