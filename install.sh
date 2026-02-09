#!/bin/bash
###############################################################################
# TastyPanel Platform - One-Command Installer
# Usage: curl -sSL https://install.tastypanel.site | sudo bash
# Or: bash install.sh --domain=example.com --db-name=tastypanel
###############################################################################

set -e # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
INSTALL_DIR="/var/www/tastypanel"
PHP_VERSION="8.3"
DB_NAME="tastypanel"
DB_USER="tastypanel"
DB_PASS=$(openssl rand -base64 32)
DOMAIN="tastypanel.local"
PORT="8080"
ADMIN_EMAIL="admin@tastypanel.local"

# Parse arguments
while [[ $# -gt 0 ]]; do
    case $1 in
        --domain=*)
            DOMAIN="${1#*=}"
            shift
            ;;
        --db-name=*)
            DB_NAME="${1#*=}"
            shift
            ;;
        --admin-email=*)
            ADMIN_EMAIL="${1#*=}"
            shift
            ;;
        --port=*)
            PORT="${1#*=}"
            shift
            ;;
        --skip-ssl)
            SKIP_SSL=true
            shift
            ;;
        *)
            echo -e "${RED}Unknown option: $1${NC}"
            exit 1
            ;;
    esac
done

###############################################################################
# Helper Functions
###############################################################################

log() {
    echo -e "${GREEN}[✓]${NC} $1"
}

error() {
    echo -e "${RED}[✗]${NC} $1"
    exit 1
}

warn() {
    echo -e "${YELLOW}[!]${NC} $1"
}

info() {
    echo -e "${BLUE}[i]${NC} $1"
}

###############################################################################
# 1. System Requirements Check
###############################################################################

info "Checking system requirements..."

# Check if running as root
if [[ $EUID -ne 0 ]]; then
   error "This script must be run as root (use sudo)"
fi

# Check OS
if [[ ! -f /etc/os-release ]]; then
    error "Cannot detect OS. Only Ubuntu 20.04+ and Debian 11+ are supported."
fi

source /etc/os-release
if [[ "$ID" != "ubuntu" && "$ID" != "debian" ]]; then
    error "Only Ubuntu and Debian are supported. Detected: $ID"
fi

log "OS: $PRETTY_NAME"

# Check available memory
TOTAL_MEM=$(free -m | awk '/^Mem:/{print $2}')
if [[ $TOTAL_MEM -lt 1024 ]]; then
    warn "Low memory detected (${TOTAL_MEM}MB). Recommended: 2GB+"
fi

# Check available disk
AVAILABLE_DISK=$(df / | awk 'NR==2 {print $4}')
if [[ $AVAILABLE_DISK -lt 5242880 ]]; then # 5GB in KB
    warn "Low disk space. Recommended: 10GB+ free"
fi

log "System requirements check passed"

###############################################################################
#  2. Install Dependencies
###############################################################################

info "Installing system dependencies..."

export DEBIAN_FRONTEND=noninteractive

apt-get update -qq
apt-get install -y -qq \
    software-properties-common \
    curl \
    wget \
    git \
    unzip \
    ca-certificates \
    lsb-release \
    gnupg2 \
    > /dev/null 2>&1

log "Base dependencies installed"

###############################################################################
# 3. Install PHP
###############################################################################

info "Installing PHP ${PHP_VERSION}..."

if ! command -v php &> /dev/null; then
    add-apt-repository -y ppa:ondrej/php > /dev/null 2>&1 || true
    apt-get update -qq
    
    apt-get install -y -qq \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-cli \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-redis \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-bcmath \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-zip \
        php${PHP_VERSION}-gd \
        php${PHP_VERSION}-intl \
        > /dev/null 2>&1
fi

PHP_INSTALLED=$(php -v | head -n1 | cut -d" " -f2 | cut -d. -f1-2)
log "PHP ${PHP_INSTALLED} installed"

###############################################################################
# 4. Install Composer
###############################################################################

info "Installing Composer..."

if ! command -v composer &> /dev/null; then
    EXPECTED_SIGNATURE="$(wget -q -O - https://composer.github.io/installer.sig)"
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    ACTUAL_SIGNATURE="$(php -r "echo hash_file('sha384', 'composer-setup.php');")"
    
    if [[ "$EXPECTED_SIGNATURE" != "$ACTUAL_SIGNATURE" ]]; then
        rm composer-setup.php
        error "Invalid Composer installer signature"
    fi
    
    php composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
    rm composer-setup.php
fi

log "Composer $(composer --version --no-ansi | cut -d' ' -f3) installed"

###############################################################################
# 5. Install MySQL
###############################################################################

info "Installing MySQL..."

if ! command -v mysql &> /dev/null; then
    apt-get install -y -qq mysql-server > /dev/null 2>&1
    systemctl start mysql
    systemctl enable mysql > /dev/null 2>&1
fi

log "MySQL installed and running"

###############################################################################
# 6. Install Redis
###############################################################################

info "Installing Redis..."

if ! command -v redis-server &> /dev/null; then
    apt-get install -y -qq redis-server > /dev/null 2>&1
    systemctl start redis
    systemctl enable redis > /dev/null 2>&1
fi

log "Redis installed and running"

###############################################################################
# 7. Install Nginx
###############################################################################

info "Installing Nginx..."

if ! command -v nginx &> /dev/null; then
    apt-get install -y -qq nginx > /dev/null 2>&1
    systemctl start nginx
    systemctl enable nginx > /dev/null 2>&1
fi

log "Nginx installed and running"

###############################################################################
# 8. Node.js is intentionally skipped (Blade-only mode)
###############################################################################

info "Skipping Node.js/NPM install (Blade-only admin mode)..."

###############################################################################
# 9. Clone/Setup Application
###############################################################################

info "Setting up TastyPanel application..."

# Create directory
mkdir -p $INSTALL_DIR
cd $INSTALL_DIR

# If current directory already has project files, use them
if [[ ! -f composer.json ]]; then
    error "composer.json not found. Please run this script from the project directory."
fi

# Set permissions
chown -R www-data:www-data $INSTALL_DIR
chmod -R 755 $INSTALL_DIR

log "Application directory prepared"

###############################################################################
# 10. Install PHP Dependencies
###############################################################################

info "Installing PHP dependencies (this may take a few minutes)..."

cd $INSTALL_DIR
sudo -u www-data composer install --no-dev --optimize-autoloader --no-interaction --quiet

log "PHP dependencies installed"

###############################################################################
# 11. Frontend assets skipped (Blade-only mode)
###############################################################################

info "Skipping npm asset build (Blade-only admin mode)..."

###############################################################################
# 12. Setup Database
###############################################################################

info "Configuring database..."

# Create database and user
mysql -e "CREATE DATABASE IF NOT EXISTS ${DB_NAME} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql -e "CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql -e "GRANT ALL PRIVILEGES ON ${DB_NAME}.* TO '${DB_USER}'@'localhost';"
mysql -e "FLUSH PRIVILEGES;"

log "Database '${DB_NAME}' created"

###############################################################################
# 13. Configure Environment
###############################################################################

info "Configuring environment..."

if [[ ! -f .env ]]; then
    cp .env.example .env
fi

# Generate APP_KEY
sudo -u www-data php artisan key:generate --force > /dev/null 2>&1

# Update .env
sed -i "s/APP_ENV=.*/APP_ENV=production/" .env
sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" .env
sed -i "s#APP_NAME=.*#APP_NAME=\"TastyPanel\"#" .env
sed -i "s/APP_MODE=.*/APP_MODE=platform/" .env
sed -i "s/TENANT_MODE=.*/TENANT_MODE=false/" .env
sed -i "s#APP_URL=.*#APP_URL=http://${DOMAIN}:${PORT}#" .env
sed -i "s/DB_DATABASE=.*/DB_DATABASE=${DB_NAME}/" .env
sed -i "s/DB_USERNAME=.*/DB_USERNAME=${DB_USER}/" .env
sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/" .env
sed -i "s/REDIS_HOST=.*/REDIS_HOST=127.0.0.1/" .env
sed -i "s/QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/" .env
sed -i "s/CACHE_STORE=.*/CACHE_STORE=redis/" .env
sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=redis/" .env
sed -i "s/AUTO_PROVISION_ON_TENANT_CREATE=.*/AUTO_PROVISION_ON_TENANT_CREATE=false/" .env
sed -i "s/FRONTEND_AUTO=.*/FRONTEND_AUTO=false/" .env

log "Environment configured"

###############################################################################
# 14. Run Migrations
###############################################################################

info "Running database migrations..."

sudo -u www-data php artisan migrate --force > /dev/null 2>&1

log "Database migrated"

###############################################################################
# 15. Configure Nginx
###############################################################################

info "Configuring Nginx..."

cat > /etc/nginx/sites-available/tastypanel << EOF
server {
    listen ${PORT};
    server_name ${DOMAIN};
    root ${INSTALL_DIR}/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \\.php$ {
        fastcgi_pass unix:/var/run/php/php${PHP_VERSION}-fpm.sock;
        fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\\.(?!well-known).* {
        deny all;
    }
}
EOF

ln -sf /etc/nginx/sites-available/tastypanel /etc/nginx/sites-enabled/
rm -f /etc/nginx/sites-enabled/default

nginx -t > /dev/null 2>&1 || error "Nginx configuration invalid"
systemctl reload nginx

log "Nginx configured for ${DOMAIN}"

###############################################################################
# 16. Setup SSL (Optional)
###############################################################################

if [[ -z "$SKIP_SSL" && "${PORT}" == "80" ]]; then
    info "Setting up SSL certificate..."
    
    if ! command -v certbot &> /dev/null; then
        apt-get install -y -qq certbot python3-certbot-nginx > /dev/null 2>&1
    fi
    
    certbot --nginx -d ${DOMAIN} --non-interactive --agree-tos -m ${ADMIN_EMAIL} --redirect > /dev/null 2>&1 || {
        warn "SSL setup failed. You can set it up later with: certbot --nginx -d ${DOMAIN}"
    }
fi

###############################################################################
# 17. Setup Queue Worker
###############################################################################

info "Setting up queue worker..."

cat > /etc/supervisor/conf.d/tastypanel-worker.conf << EOF
[program:tastypanel-worker]
process_name=%(program_name)s_%(process_num)02d
command=php ${INSTALL_DIR}/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=${INSTALL_DIR}/storage/logs/worker.log
stopwaitsecs=3600
EOF

if command -v supervisorctl &> /dev/null; then
    supervisorctl reread > /dev/null 2>&1
    supervisorctl update > /dev/null 2>&1
    supervisorctl start tastypanel-worker:* > /dev/null 2>&1
    log "Queue worker started"
else
    apt-get install -y -qq supervisor > /dev/null 2>&1
    systemctl enable supervisor > /dev/null 2>&1
    systemctl start supervisor
fi

###############################################################################
# 18. Setup Scheduler
###############################################################################

info "Setting up task scheduler..."

(crontab -l 2>/dev/null; echo "* * * * * cd ${INSTALL_DIR} && php artisan schedule:run >> /dev/null 2>&1") | crontab -

log "Scheduler configured"

###############################################################################
# 19. Final Optimizations
###############################################################################

info "Optimizing application..."

cd $INSTALL_DIR
sudo -u www-data php artisan config:cache > /dev/null 2>&1
sudo -u www-data php artisan route:cache > /dev/null 2>&1
sudo -u www-data php artisan view:cache > /dev/null 2>&1
sudo -u www-data php artisan storage:link > /dev/null 2>&1

# Set final permissions
chown -R www-data:www-data $INSTALL_DIR
chmod -R 755 $INSTALL_DIR
chmod -R 775 $INSTALL_DIR/storage $INSTALL_DIR/bootstrap/cache

log "Application optimized"

###############################################################################
# DONE!
###############################################################################

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}          TastyPanel Platform Installation Complete!         ${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Access your platform:${NC}"
echo -e "  URL:      http://${DOMAIN}:${PORT}/platform/install"
echo -e "  Next:     Complete the installer page to create your superadmin account"
echo ""
echo -e "${BLUE}Database Credentials:${NC}"
echo -e "  Database: ${DB_NAME}"
echo -e "  User:     ${DB_USER}"
echo -e "  Password: ${DB_PASS}"
echo ""
echo -e "${YELLOW}⚠️  IMPORTANT: Save these credentials securely!${NC}"
echo ""
echo -e "${BLUE}Next Steps:${NC}"
echo -e "  1. Open /platform/install and finish setup"
echo -e "  2. Login at /platform/login"
echo -e "  3. Configure email settings in .env"
echo -e "  4. Create your first tenant"
echo ""
echo -e "${GREEN}Documentation: https://docs.tastypanel.site${NC}"
echo -e "${GREEN}Support: https://support.tastypanel.site${NC}"
echo ""

# Save credentials
cat > /root/tastypanel-credentials.txt << EOF
TastyPanel Installation - $(date)
════════════════════════════════════════════

URL: http://${DOMAIN}:${PORT}/platform/install
Admin setup: Complete via /platform/install

Database Name: ${DB_NAME}
Database User: ${DB_USER}
Database Password: ${DB_PASS}

Installation Directory: ${INSTALL_DIR}
EOF

chmod 600 /root/tastypanel-credentials.txt
echo -e "${GREEN}Credentials saved to: /root/tastypanel-credentials.txt${NC}"
echo ""
