#!/bin/bash
set -e

# SAP Visitor Management System - Docker Entrypoint

echo "========================================"
echo "SAP Visitor Management System"
echo "========================================"
echo ""

# Create necessary directories
mkdir -p /var/www/html/logs
mkdir -p /var/www/html/uploads
mkdir -p /var/www/html/cache

# Set permissions
chown -R www-data:www-data /var/www/html
chmod -R 755 /var/www/html
chmod -R 777 /var/www/html/logs 2>/dev/null || true
chmod -R 777 /var/www/html/uploads 2>/dev/null || true

# Create .env file from environment variables if it doesn't exist
if [ ! -f /var/www/html/.env ]; then
    echo "Creating .env file..."
    cat > /var/www/html/.env <<EOF
# Application Environment
APP_ENV=${APP_ENV:-development}
APP_DEBUG=${APP_DEBUG:-true}

# Database Configuration
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-3306}
DB_NAME=${DB_NAME:-visitor_system}
DB_USER=${DB_USER:-visitor_user}
DB_PASS=${DB_PASS:-visitor_pass}

# Site Configuration
SITE_URL=${SITE_URL:-http://localhost:8000}
BASE_URL=${BASE_URL:-http://localhost:8000}
SITE_NAME=${SITE_NAME:-SAP Office}
COMPANY_NAME=${COMPANY_NAME:-SAP}
TIMEZONE=${TIMEZONE:-Europe/Paris}
DEFAULT_LANGUAGE=${DEFAULT_LANGUAGE:-fr}

# Microsoft Graph API (disabled in Docker by default)
MS_GRAPH_ENABLED=${MS_GRAPH_ENABLED:-false}
MS_GRAPH_TENANT_ID=${MS_GRAPH_TENANT_ID:-}
MS_GRAPH_CLIENT_ID=${MS_GRAPH_CLIENT_ID:-}
MS_GRAPH_CLIENT_SECRET=${MS_GRAPH_CLIENT_SECRET:-}
MS_GRAPH_FROM_EMAIL=${MS_GRAPH_FROM_EMAIL:-}
MS_GRAPH_FROM_NAME=${MS_GRAPH_FROM_NAME:-Visitor Management System}

# Admin Credentials
ADMIN_USERNAME=${ADMIN_USERNAME:-admin}
ADMIN_PASSWORD=${ADMIN_PASSWORD:-admin123}

# Feature Flags
FEATURE_KIOSK_MODE=true
FEATURE_QR_CHECKOUT=true
FEATURE_EMAIL_NOTIFICATIONS=false
FEATURE_REMINDERS=true
FEATURE_ESCALATION=true
FEATURE_LOCAL_LOGIN=true
FEATURE_AUDIT_LOG=true
EOF
    echo ".env file created!"
fi

# Wait for database to be ready
echo ""
echo "Waiting for database to be ready..."
max_retries=30
retry_count=0

while [ $retry_count -lt $max_retries ]; do
    if mysqladmin ping -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" --silent 2>/dev/null; then
        echo "Database is ready!"
        break
    fi

    retry_count=$((retry_count + 1))
    echo "Waiting for database... ($retry_count/$max_retries)"
    sleep 2
done

if [ $retry_count -eq $max_retries ]; then
    echo "WARNING: Could not connect to database. The app may not work correctly."
fi

echo ""
echo "========================================"
echo "Application Ready!"
echo "========================================"
echo ""
echo "Access the application at:"
echo "  - Kiosk:     http://localhost:8000"
echo "  - Admin:     http://localhost:8000/admin/"
echo "  - phpMyAdmin: http://localhost:8080"
echo ""
echo "Admin Login:"
echo "  - Username: ${ADMIN_USERNAME:-admin}"
echo "  - Password: ${ADMIN_PASSWORD:-admin123}"
echo ""
echo "========================================"

# Start Apache
exec "$@"
