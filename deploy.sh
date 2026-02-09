#!/bin/bash
###############################################################################
# TastyPanel - Zero-Downtime Deployment Script
# Usage: ./deploy.sh [--skip-assets] [--skip-migrations]
###############################################################################

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Config
APP_DIR="/var/www/tastypanel"
BRANCH="main"
SKIP_ASSETS=false
SKIP_MIGRATIONS=false

# Parse args
while [[ $# -gt 0 ]]; do
    case $1 in
        --skip-assets) SKIP_ASSETS=true; shift ;;
        --skip-migrations) SKIP_MIGRATIONS=true; shift ;;
        *) shift ;;
    esac
done

log() { echo -e "${GREEN}[✓]${NC} $1"; }
warn() { echo -e "${YELLOW}[!]${NC} $1"; }
error() { echo -e "${RED}[✗]${NC} $1"; exit 1; }
info() { echo -e "${BLUE}[i]${NC} $1"; }

###############################################################################
# Start Deployment
###############################################################################

echo ""
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}          TastyPanel - Zero-Downtime Deployment              ${NC}"
echo -e "${BLUE}═══════════════════════════════════════════════════════════${NC}"
echo ""

cd $APP_DIR || error "Could not navigate to $APP_DIR"

# Check if git repo
if [[ ! -d .git ]]; then
    error "Not a git repository"
fi

START_TIME=$(date +%s)

###############################################################################
# 1. Maintenance Mode
###############################################################################

info "Entering maintenance mode..."
php artisan down --retry=60 --secret="tastypanel-deploy-$(date +%s)" 2>/dev/null || true
log "Maintenance mode enabled"

###############################################################################
# 2. Backup Current Release
###############################################################################

info "Creating backup..."
BACKUP_DIR="/var/www/backups/tastypanel-$(date +%Y%m%d-%H%M%S)"
mkdir -p $BACKUP_DIR
cp .env $BACKUP_DIR/.env
mysqldump -u ${DB_USER:-tastypanel} -p${DB_PASS:-password} ${DB_NAME:-tastypanel} | gzip > $BACKUP_DIR/db.sql.gz 2>/dev/null || warn "DB backup skipped"
log "Backup created at $BACKUP_DIR"

###############################################################################
# 3. Pull Latest Code
###############################################################################

info "Pulling latest code from $BRANCH..."
git fetch origin $BRANCH
git reset --hard origin/$BRANCH
log "Code updated"

###############################################################################
# 4. Install Dependencies
###############################################################################

info "Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction --quiet
log "PHP dependencies installed"

###############################################################################
# 5. Build Assets
###############################################################################

if [[ "$SKIP_ASSETS" == false ]]; then
    info "Building assets..."
    npm ci --silent
    npm run build
    log "Assets built"
else
    warn "Skipping asset build"
fi

###############################################################################
# 6. Run Migrations
###############################################################################

if [[ "$SKIP_MIGRATIONS" == false ]]; then
    info "Running migrations..."
    php artisan migrate --force
    log "Migrations complete"
else
    warn "Skipping migrations"
fi

###############################################################################
# 7. Clear & Optimize Caches
###############################################################################

info "Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
log "Application optimized"

###############################################################################
# 8. Restart Queue Workers
###############################################################################

info "Restarting queue workers..."
sudo supervisorctl restart tastypanel-worker:* 2>/dev/null || php artisan queue:restart
log "Queue workers restarted"

###############################################################################
# 9. Exit Maintenance Mode
###############################################################################

info "Exiting maintenance mode..."
php artisan up
log "Application is live"

###############################################################################
# Done!
###############################################################################

END_TIME=$(date +%s)
DURATION=$((END_TIME - START_TIME))

echo ""
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}          ✅ Deployment Complete in ${DURATION}s              ${NC}"
echo -e "${GREEN}═══════════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Deployed:${NC} $(git log -1 --pretty=format:'%h - %s (%an)')"
echo -e "${BLUE}Time:${NC}     $(date)"
echo ""
