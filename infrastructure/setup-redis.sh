#!/bin/bash
# Redis Setup Script for TastyPanel Platform

echo "🚀 Setting up Redis for performance..."

# Install Redis
echo "📦 Installing Redis..."
if command -v apt-get &> /dev/null; then
    # Ubuntu/Debian
    sudo apt update
    sudo apt install -y redis-server php-redis
    sudo systemctl enable redis-server
    sudo systemctl start redis-server
elif command -v yum &> /dev/null; then
    # CentOS/RHEL
    sudo yum install -y redis php-redis
    sudo systemctl enable redis
    sudo systemctl start redis
elif command -v brew &> /dev/null; then
    # macOS
    brew install redis
    brew services start redis
fi

# Verify Redis is running
if redis-cli ping | grep -q "PONG"; then
    echo "✅ Redis is running!"
else
    echo "❌ Redis failed to start"
    exit 1
fi

# Configure Redis for production
echo "⚙️  Configuring Redis..."

REDIS_CONF="/etc/redis/redis.conf"
if [ ! -f "$REDIS_CONF" ]; then
    REDIS_CONF="/usr/local/etc/redis.conf"
fi

if [ -f "$REDIS_CONF" ]; then
    # Backup original config
    sudo cp "$REDIS_CONF" "${REDIS_CONF}.backup"
    
    # Optimize settings
    sudo sed -i 's/^# maxmemory .*/maxmemory 256mb/' "$REDIS_CONF"
    sudo sed -i 's/^# maxmemory-policy .*/maxmemory-policy allkeys-lru/' "$REDIS_CONF"
    
    # Restart Redis
    sudo systemctl restart redis-server 2>/dev/null || sudo systemctl restart redis 2>/dev/null || brew services restart redis 2>/dev/null
fi

echo "✅ Redis setup complete!"
echo ""
echo "📋 Redis Info:"
redis-cli INFO server | grep redis_version
echo ""
echo "🔥 Ready for high performance caching!"
