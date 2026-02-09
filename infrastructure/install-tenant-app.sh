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

if [ -z "$TENANT_ROOT" ]; then
    echo "Tenant root is required"
    exit 1
fi

if [ -z "$SYSTEM_USER" ]; then
    echo "System user is required"
    exit 1
fi

echo "Installing $APP_TYPE to $TENANT_ROOT for user $SYSTEM_USER..."

# Ensure directory exists and is empty
mkdir -p "$TENANT_ROOT"
find "$TENANT_ROOT" -mindepth 1 -delete

# Set initial ownership so we can work as SYSTEM_USER
chown -R "$SYSTEM_USER":"$SYSTEM_GROUP" "$TENANT_ROOT"

if [ "$APP_TYPE" == "wordpress" ]; then
    echo "Downloading WordPress..."
    wget -q https://wordpress.org/latest.tar.gz -O /tmp/wp-$TENANT_KEY.tar.gz
    sudo -u "$SYSTEM_USER" tar -xzf /tmp/wp-$TENANT_KEY.tar.gz -C "$TENANT_ROOT" --strip-components=1
    rm /tmp/wp-$TENANT_KEY.tar.gz
    
    if [ ! -z "$DB_NAME" ]; then
        echo "Configuring WordPress..."
        sudo -u "$SYSTEM_USER" wp config create --dbname="$DB_NAME" --dbuser="$DB_USER" --dbpass="$DB_PASS" --dbhost="localhost" --path="$TENANT_ROOT" --skip-check
        
        if [ ! -z "$ADMIN_EMAIL" ]; then
            echo "Installing WordPress Core..."
            sudo -u "$SYSTEM_USER" wp core install --url="$APP_URL" --title="$TENANT_KEY Site" --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL" --path="$TENANT_ROOT"
        fi
    fi
    
elif [ "$APP_TYPE" == "laravel" ]; then
    echo "Installing Laravel Starter..."
    sudo -u "$SYSTEM_USER" git clone https://github.com/laravel/laravel.git "$TENANT_ROOT"
    cd "$TENANT_ROOT"
    
    if command -v composer &> /dev/null; then
        echo "Running composer install..."
        sudo -u "$SYSTEM_USER" composer install --no-dev --no-interaction --optimize-autoloader
        sudo -u "$SYSTEM_USER" cp .env.example .env
        
        if [ ! -z "$DB_NAME" ]; then
            echo "Configuring Laravel database..."
            sudo -u "$SYSTEM_USER" sed -i "s/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/" .env
            sudo -u "$SYSTEM_USER" sed -i "s/# DB_HOST=127.0.0.1/DB_HOST=127.0.0.1/" .env
            sudo -u "$SYSTEM_USER" sed -i "s/# DB_PORT=3306/DB_PORT=3306/" .env
            sudo -u "$SYSTEM_USER" sed -i "s/# DB_DATABASE=laravel/DB_DATABASE=$DB_NAME/" .env
            sudo -u "$SYSTEM_USER" sed -i "s/# DB_USERNAME=root/DB_USERNAME=$DB_USER/" .env
            sudo -u "$SYSTEM_USER" sed -i "s/# DB_PASSWORD=/DB_PASSWORD=$DB_PASS/" .env
            
            sudo -u "$SYSTEM_USER" php artisan key:generate || true
            sudo -u "$SYSTEM_USER" php artisan migrate --force || echo "Migration failed."
            
            if [ ! -z "$ADMIN_EMAIL" ]; then
                echo "Creating Laravel Admin User..."
                # Create user via Tinker/Artisan
                sudo -u "$SYSTEM_USER" php artisan tinker --execute="App\Models\User::create(['name' => '$ADMIN_USER', 'email' => '$ADMIN_EMAIL', 'password' => Hash::make('$ADMIN_PASS'), 'email_verified_at' => now()])" || echo "Admin creation failed."
            fi
        fi
    fi

elif [ "$APP_TYPE" == "git" ] && [ ! -z "$REPO_URL" ]; then
    echo "Cloning from $REPO_URL..."
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
echo "Finalizing permissions..."
chown -R "$SYSTEM_USER":"$SYSTEM_GROUP" "$TENANT_ROOT"
find "$TENANT_ROOT" -type d -exec chmod 755 {} \;
find "$TENANT_ROOT" -type f -exec chmod 644 {} \;

# Laravel specific permissions
if [ -d "$TENANT_ROOT/storage" ]; then
    chmod -R 775 "$TENANT_ROOT/storage"
    chmod -R 775 "$TENANT_ROOT/bootstrap/cache"
fi

echo "Application installed successfully."
