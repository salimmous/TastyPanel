#!/bin/bash
# MySQL Platform Setup Script

echo "🚀 Setting up MySQL for TastyPanel Platform..."

# Generate secure password
DB_PASS=$(openssl rand -base64 24 | tr -d '/+=' | cut -c1-20)

echo "📝 Creating MySQL database and user..."

# Run MySQL setup
mysql -u root <<SQL
-- Create database
CREATE DATABASE IF NOT EXISTS tastypanel_platform 
    CHARACTER SET utf8mb4 
    COLLATE utf8mb4_unicode_ci;

-- Create user
CREATE USER IF NOT EXISTS 'tastypanel_admin'@'localhost' 
    IDENTIFIED BY '$DB_PASS';

-- Grant privileges
GRANT ALL PRIVILEGES ON tastypanel_platform.* 
    TO 'tastypanel_admin'@'localhost';

-- Grant tenant database creation ability
GRANT CREATE, DROP ON *.* 
    TO 'tastypanel_admin'@'localhost';

FLUSH PRIVILEGES;
SQL

if [ $? -eq 0 ]; then
    echo "✅ MySQL setup completed!"
    echo ""
    echo "📋 Database Credentials:"
    echo "   Database: tastypanel_platform"
    echo "   Username: tastypanel_admin"
    echo "   Password: $DB_PASS"
    echo ""
    echo "⚠️  Save these credentials! They will be needed for .env"
else
    echo "❌ MySQL setup failed!"
    exit 1
fi
