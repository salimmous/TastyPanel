#!/bin/bash

# Script to setup Nginx configuration for TastyPanel

echo "🔧 Setting up Nginx configuration for TastyPanel..."
echo ""

# Check if running as root
if [ "$EUID" -ne 0 ]; then 
    echo "❌ Please run as root (use sudo)"
    exit 1
fi

# Detect PHP version
PHP_VERSION=$(php -v | head -n 1 | cut -d " " -f 2 | cut -d "." -f 1,2)
PHP_SOCKET="/var/run/php/php${PHP_VERSION}-fpm.sock"

echo "📋 Detected PHP version: ${PHP_VERSION}"
echo "📋 PHP-FPM socket: ${PHP_SOCKET}"
echo ""

# Check if PHP socket exists
if [ ! -S "$PHP_SOCKET" ]; then
    echo "⚠️  PHP-FPM socket not found at: $PHP_SOCKET"
    echo "   Available sockets:"
    ls -la /var/run/php/*.sock 2>/dev/null || echo "   No sockets found"
    echo ""
    read -p "Enter PHP-FPM socket path (or press Enter to use default): " CUSTOM_SOCKET
    if [ -n "$CUSTOM_SOCKET" ]; then
        PHP_SOCKET="$CUSTOM_SOCKET"
    fi
fi

# Update nginx.conf with correct PHP socket
sed -i "s|fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;|fastcgi_pass unix:${PHP_SOCKET};|" nginx.conf

# Detect Nginx config location
if [ -d "/etc/nginx/sites-available" ]; then
    CONFIG_PATH="/etc/nginx/sites-available/tastypanel.site"
    ENABLED_PATH="/etc/nginx/sites-enabled/tastypanel.site"
    echo "📁 Using sites-available/sites-enabled structure"
elif [ -d "/etc/nginx/conf.d" ]; then
    CONFIG_PATH="/etc/nginx/conf.d/tastypanel.site.conf"
    ENABLED_PATH=""
    echo "📁 Using conf.d structure"
else
    echo "❌ Could not find Nginx configuration directory"
    exit 1
fi

# Copy configuration
echo "📋 Copying configuration to: $CONFIG_PATH"
cp nginx.conf "$CONFIG_PATH"

# Create symlink if using sites-available
if [ -n "$ENABLED_PATH" ] && [ ! -L "$ENABLED_PATH" ]; then
    echo "🔗 Creating symlink..."
    ln -s "$CONFIG_PATH" "$ENABLED_PATH"
fi

# Test Nginx configuration
echo ""
echo "🧪 Testing Nginx configuration..."
if nginx -t; then
    echo "✅ Nginx configuration is valid"
    echo ""
    echo "🔄 Reloading Nginx..."
    systemctl reload nginx || service nginx reload
    echo "✅ Nginx reloaded successfully"
    echo ""
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo "✅ Setup complete!"
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
    echo ""
    echo "📍 Test your site:"
    echo "   - https://tastypanel.site/"
    echo "   - https://tastypanel.site/login"
    echo "   - https://tastypanel.site/api/categories"
    echo ""
else
    echo "❌ Nginx configuration test failed!"
    echo "   Please check the configuration manually"
    exit 1
fi

