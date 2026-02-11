#!/bin/bash
set -e

TENANT_KEY=$1
TENANT_ROOT=$2
APP_TYPE=$3 # wordpress, laravel, git
REPO_URL=$4
SYSTEM_USER=$5
SYSTEM_GROUP=${6:-www-data}
DB_NAME=$7
DB_USER=$8
DB_PASS=$9
ADMIN_EMAIL=${10}
ADMIN_USER=${11}
ADMIN_PASS=${12}
APP_URL=${13}
INSTALL_LOG=${14:-}

log() {
  echo "[$(date -Iseconds)] $*"
  [ -n "$INSTALL_LOG" ] && echo "[$(date -Iseconds)] $*" >> "$INSTALL_LOG"
}

if [ -z "$TENANT_ROOT" ]; then
    log "ERROR: Tenant root is required"
    exit 1
fi

if [ -z "$SYSTEM_USER" ]; then
    log "ERROR: System user is required"
    exit 1
fi

log "Installing $APP_TYPE to $TENANT_ROOT for user $SYSTEM_USER..."

# Ensure directory exists and is empty
mkdir -p "$TENANT_ROOT"
find "$TENANT_ROOT" -mindepth 1 -delete

# Set initial ownership so we can work as SYSTEM_USER
chown -R "$SYSTEM_USER":"$SYSTEM_GROUP" "$TENANT_ROOT"

if [ "$APP_TYPE" == "wordpress" ]; then
    log "Downloading WordPress..."
    wget -q https://wordpress.org/latest.tar.gz -O /tmp/wp-$TENANT_KEY.tar.gz
    sudo -u "$SYSTEM_USER" tar -xzf /tmp/wp-$TENANT_KEY.tar.gz -C "$TENANT_ROOT" --strip-components=1
    rm /tmp/wp-$TENANT_KEY.tar.gz
    
    if [ ! -z "$DB_NAME" ]; then
        log "Configuring WordPress..."
        sudo -u "$SYSTEM_USER" wp config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" --dbhost="localhost" --path="$TENANT_ROOT" --skip-check
        
        if [ ! -z "$ADMIN_EMAIL" ]; then
            log "Installing WordPress Core..."
            sudo -u "$SYSTEM_USER" wp core install --url="$APP_URL" --title="$TENANT_KEY Site" --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL" --path="$TENANT_ROOT"
        fi
    fi
    
elif [ "$APP_TYPE" == "laravel" ]; then
    LARAVEL_REPO="${REPO_URL:-https://github.com/laravel/laravel.git}"
    if [ -n "$REPO_URL" ]; then
        log "Installing Laravel from $REPO_URL (theme + dashboard)..."
    else
        log "Installing Laravel Starter (default)..."
    fi
    sudo -u "$SYSTEM_USER" git clone "${LARAVEL_REPO}" "$TENANT_ROOT"
    cd "$TENANT_ROOT"
    
    if command -v composer &> /dev/null; then
        log "Running composer install..."
        sudo -u "$SYSTEM_USER" composer install --no-dev --no-interaction --optimize-autoloader
        [ ! -f .env ] && sudo -u "$SYSTEM_USER" cp .env.example .env
        
        if [ ! -z "$DB_NAME" ]; then
            log "Configuring Laravel database..."
            sudo -u "$SYSTEM_USER" sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/" .env 2>/dev/null || true
            sudo -u "$SYSTEM_USER" sed -i "s/# DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/" .env 2>/dev/null || true
            sudo -u "$SYSTEM_USER" sed -i "s/# DB_PORT=3306/DB_PORT=3306/" .env 2>/dev/null || true
            sudo -u "$SYSTEM_USER" sed -i "s/# DB_DATABASE=laravel/DB_DATABASE=$DB_NAME/" .env 2>/dev/null || true
            sudo -u "$SYSTEM_USER" sed -i "s/# DB_USERNAME=root/DB_USERNAME=$DB_USER/" .env 2>/dev/null || true
            sudo -u "$SYSTEM_USER" sed -i "s/# DB_PASSWORD=/DB_PASSWORD=$DB_PASS/" .env 2>/dev/null || true
            sudo -u "$SYSTEM_USER" sed -i "s|APP_URL=.*|APP_URL=$APP_URL|" .env 2>/dev/null || true
            
            log "Running Laravel migrations..."
            sudo -u "$SYSTEM_USER" php artisan key:generate --force 2>/dev/null || true
            sudo -u "$SYSTEM_USER" php artisan migrate --force || log "WARNING: Migration failed."
            
            if [ ! -z "$ADMIN_EMAIL" ]; then
                log "Creating Laravel Admin User..."
                sudo -u "$SYSTEM_USER" php artisan tinker --execute="App\Models\User::create(['name' => '$ADMIN_USER', 'email' => '$ADMIN_EMAIL', 'password' => Hash::make('$ADMIN_PASS'), 'email_verified_at' => now()])" || log "WARNING: Admin creation failed."
            fi
        fi
    fi

elif [ "$APP_TYPE" == "git" ] && [ ! -z "$REPO_URL" ]; then
    log "Cloning from $REPO_URL..."
    sudo -u "$SYSTEM_USER" git clone "$REPO_URL" "$TENANT_ROOT"
    cd "$TENANT_ROOT"
    
    if [ -f "composer.json" ] && command -v composer &> /dev/null; then
        sudo -u "$SYSTEM_USER" composer install --no-dev --no-interaction --optimize-autoloader || true
    fi
    if [ -f ".env.example" ] && [ ! -f ".env" ]; then
        sudo -u "$SYSTEM_USER" cp .env.example .env
         if [ ! -z "$DB_NAME" ]; then
            sudo -u "$SYSTEM_USER" sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/" .env || true
            sudo -u "$SYSTEM_USER" sed -i "s/DB_DATABASE=laravel/DB_DATABASE=$DB_NAME/" .env || true
            sudo -u "$SYSTEM_USER" sed -i "s/DB_USERNAME=root/DB_USERNAME=$DB_USER/" .env || true
            sudo -u "$SYSTEM_USER" sed -i "s/DB_PASSWORD=/DB_PASSWORD=$DB_PASS/" .env || true
        fi
    fi
    if [ -f "artisan" ]; then
        sudo -u "$SYSTEM_USER" php artisan key:generate || true
        if [ ! -z "$DB_NAME" ]; then
             sudo -u "$SYSTEM_USER" php artisan migrate --force || true
             if [ ! -z "$ADMIN_EMAIL" ]; then
                sudo -u "$SYSTEM_USER" php artisan tinker --execute="App\Models\User::create(['name' => '$ADMIN_USER', 'email' => '$ADMIN_EMAIL', 'password' => Hash::make('$ADMIN_PASS'), 'email_verified_at' => now()])" || true
             fi
        fi
    fi
fi

# Final permission fix
log "Finalizing permissions..."
chown -R "$SYSTEM_USER":"$SYSTEM_GROUP" "$TENANT_ROOT"
find "$TENANT_ROOT" -type d -exec chmod 755 {} \;
find "$TENANT_ROOT" -type f -exec chmod 644 {} \;

# Laravel specific permissions
if [ -d "$TENANT_ROOT/storage" ]; then
    chmod -R 775 "$TENANT_ROOT/storage"
    chmod -R 775 "$TENANT_ROOT/bootstrap/cache"
fi

log "Application installed successfully."
