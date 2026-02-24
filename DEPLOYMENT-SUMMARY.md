# SAP Visitor Management System - Deployment Summary

## 📦 Deployment Package Ready!

### Location
**Zip File**: `~/Downloads/SAP-Visitor-System-Bolt.zip` (81 KB)

### What's Included
- ✅ 59 files
- ✅ Complete application source code
- ✅ SQLite database support
- ✅ Bolt platform configuration
- ✅ Deployment README
- ✅ Sample data pre-loaded

---

## 🚀 How to Deploy to Bolt

### Method 1: Zip Upload (Recommended)

1. **Go to Bolt**
   ```
   https://bolt.new
   ```

2. **Click "Upload"**
   - Click the upload button in the top right
   - Select: `~/Downloads/SAP-Visitor-System-Bolt.zip`
   - Wait for upload (takes ~30 seconds)

3. **Wait for Setup**
   - Bolt will automatically detect PHP
   - SQLite database will auto-create on first run
   - Dependencies will be installed

4. **Access Your App**
   - Kiosk: Click the preview link
   - Admin: Add `/admin/` to the URL

### Method 2: GitHub Import

1. **Push to GitHub**
   ```bash
   git remote add origin https://github.com/YOUR_USERNAME/SAPvisitorsystem.git
   git branch -M main
   git push -u origin main
   ```

2. **Import to Bolt**
   - Go to https://bolt.new
   - Click "Import from GitHub"
   - Enter your repo URL

---

## 🔑 Default Login Credentials

### Admin Panel
- **URL**: `/admin/`
- **Username**: `admin@example.com`
- **Password**: `changeme`

⚠️ **IMPORTANT**: Change this password immediately after first login!

---

## 👥 Pre-loaded Sample Data

### Hosts (SAP Employees)
| Name | Department | Email |
|------|------------|-------|
| Jean Dupont | IT | jean.dupont@sap.com |
| Marie Martin | HR | marie.martin@sap.com |
| Pierre Bernard | Finance | pierre.bernard@sap.com |
| Sophie Petit | Marketing | sophie.petit@sap.com |
| Lucas Moreau | Sales | lucas.moreau@sap.com |

---

## 📁 File Structure

```
SAP-Visitor-System-Bolt.zip
│
├── index.php              # Kiosk main page (Arrivée/Sortie)
├── index.html             # Redirect to index.php
├── checkin.php            # Visitor check-in form
├── checkout.php           # QR code checkout
├── confirmation.php       # Success pages
├── host-action.php        # Host email actions
│
├── api/                   # API endpoints
│   ├── checkin.php       # Process check-in
│   ├── checkout.php      # Process checkout
│   └── verify-qr.php     # Verify QR tokens
│
├── admin/                 # Admin panel
│   ├── index.php         # Login page
│   ├── dashboard.php     # Dashboard with stats
│   ├── visitors.php      # Visitor management
│   ├── export.php        # CSV export
│   └── logout.php        # Logout
│
├── includes/              # Core files
│   ├── config.php        # Configuration
│   ├── db.php           # Database (MySQL/SQLite auto-detect)
│   ├── db-sqlite.php    # SQLite implementation
│   ├── helpers.php      # Utility functions
│   ├── email.php        # Email service
│   └── microsoft-graph.php # MS Graph API
│
├── templates/             # Layouts
│   ├── layout/
│   │   └── kiosk.php    # Kiosk layout
│   └── emails/          # Email templates
│       ├── arrival.php
│       ├── reminder.php
│       ├── escalation.php
│       └── checkout.php
│
├── assets/                # Static files
│   ├── css/kiosk.css    # Styles
│   └── js/              # JavaScript
│       ├── kiosk.js
│       └── qr-scanner.js
│
├── data/                  # SQLite database (auto-created)
├── logs/                  # Log files
├── uploads/               # File uploads
├── cache/                 # Cache directory
│
├── bolt.json             # Bolt configuration
├── DEPLOY-README.txt     # Deployment guide
└── .env.example          # Environment template
```

---

## ⚙️ Configuration

### Environment Variables (Bolt Settings)

Set these in Bolt's environment panel:

```
ADMIN_USERNAME=admin
ADMIN_PASSWORD=your-secure-password
SITE_URL=https://your-project.bolt.new
DEFAULT_LANGUAGE=fr
ENABLE_EMAIL=false
```

### Features Enabled by Default
- ✅ Kiosk Mode
- ✅ QR Code Checkout
- ✅ SQLite Database
- ✅ Admin Dashboard
- ✅ CSV Export
- ✅ Audit Logging
- ❌ Email Notifications (disabled - configure SMTP to enable)
- ❌ Cron Jobs (manual trigger only)

---

## 🧪 Testing the Flow

### Test Check-in
1. Open your Bolt app URL
2. Click "Arrivée" (Check-in)
3. Fill the form:
   - First Name: Test
   - Last Name: Visitor
   - Company: Acme Inc
   - Reason: Meeting
   - Host: Select Jean Dupont
   - Duration: 2 hours
4. Submit
5. Save the QR code shown

### Test Checkout
1. Click "Sortie" (Checkout)
2. Scan or enter the QR code
3. Confirm checkout
4. See success message

### Test Admin
1. Go to `/admin/`
2. Login with admin@example.com / changeme
3. View dashboard with stats
4. Check visitors list
5. Export CSV

---

## 🔧 Customization

### Change Site Name
Edit `includes/config.php`:
```php
define('SITE_NAME', 'Your Company Name');
```

### Change Default Language
Edit `includes/config.php`:
```php
define('DEFAULT_LANGUAGE', 'en'); // 'fr' or 'en'
```

### Add More Hosts
1. Login to admin panel
2. Navigate to Hosts management
3. Add new hosts

### Enable Email (Advanced)
1. Configure SMTP settings in `.env`
2. Set `EMAIL_ENABLED=true`
3. Set `MS_GRAPH_ENABLED=true` (if using Office 365)

---

## 🐛 Troubleshooting

### Database Locked
**Problem**: SQLite shows "database is locked"
**Solution**: Refresh the page. SQLite handles one request at a time.

### Permission Denied
**Problem**: Can't write to directories
**Solution**: In Bolt terminal:
```bash
chmod -R 777 data/ uploads/ logs/
```

### QR Scanner Not Working
**Problem**: Camera doesn't open
**Solution**:
1. Ensure you're using HTTPS (Bolt provides this)
2. Allow camera permissions in browser
3. Use a real device (not all emulators support camera)

### 404 Errors
**Problem**: Pages not found
**Solution**: Check that `.htaccess` file is present (for Apache)

---

## 📊 Technical Specifications

| Component | Specification |
|-----------|--------------|
| **PHP Version** | 8.2 |
| **Database** | SQLite (auto-setup) |
| **Web Server** | PHP Built-in or Apache |
| **Frontend** | Tailwind CSS |
| **QR Codes** | QRCode.js |
| **Platform** | Bolt / Any PHP host |

---

## 📝 Post-Deployment Checklist

- [ ] Change admin password from "changeme"
- [ ] Update site name in config
- [ ] Configure your own hosts list
- [ ] Test check-in flow
- [ ] Test checkout flow
- [ ] Test admin dashboard
- [ ] Customize colors/branding
- [ ] Set up email (optional)
- [ ] Configure reminders (optional)
- [ ] Review audit logs

---

## 🆘 Support

### Documentation
- `README.md` - Full documentation
- `BOLT-DEPLOY.md` - Bolt-specific guide
- `DEPLOY-README.txt` - Quick start (included in zip)

### Common Issues
1. **Database errors**: Check `data/` directory permissions
2. **Upload errors**: Check `uploads/` directory permissions
3. **Email not sending**: Configure SMTP or disable email
4. **Styling issues**: Clear browser cache

---

## 🎉 Ready to Deploy!

Your deployment package is ready at:
```
~/Downloads/SAP-Visitor-System-Bolt.zip
```

**Next Steps**:
1. Go to https://bolt.new
2. Click "Upload"
3. Select the zip file
4. Start using your visitor management system!

Good luck! 🚀
