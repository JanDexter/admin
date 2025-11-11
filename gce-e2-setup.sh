#!/bin/bash

# Google Compute Engine E2 Micro Setup Script
# This script sets up a complete Laravel application on a GCE e2-micro instance
# Usage: ./gce-e2-setup.sh [--with-sample-data]

set -e  # Exit on error

echo "=========================================="
echo "Google Compute Engine E2 Micro Setup"
echo "=========================================="
echo ""

# Parse arguments
WITH_SAMPLE_DATA=false
if [[ "$1" == "--with-sample-data" ]]; then
    WITH_SAMPLE_DATA=true
    echo "Sample data will be seeded"
fi

# Color codes for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${GREEN}Step 1: Updating system packages...${NC}"
sudo apt-get update
sudo apt-get upgrade -y

echo ""
echo -e "${GREEN}Step 2: Installing required software...${NC}"

# Install PHP 8.4 and extensions
sudo apt-get install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update

sudo apt-get install -y \
    php8.4 \
    php8.4-fpm \
    php8.4-cli \
    php8.4-mysql \
    php8.4-xml \
    php8.4-mbstring \
    php8.4-curl \
    php8.4-zip \
    php8.4-gd \
    php8.4-bcmath \
    php8.4-intl \
    php8.4-redis \
    unzip \
    git \
    curl

# Install Composer
if ! command -v composer &> /dev/null; then
    echo "Installing Composer..."
    curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
fi

# Install Node.js 18.x
if ! command -v node &> /dev/null; then
    echo "Installing Node.js..."
    curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
    sudo apt-get install -y nodejs
fi

# Install Nginx
sudo apt-get install -y nginx

# Install MySQL
echo "Installing MySQL..."
sudo apt-get install -y mysql-server

echo ""
echo -e "${GREEN}Step 3: Configuring MySQL...${NC}"

# Secure MySQL installation
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY 'temporary_root_password';"
sudo mysql -u root -ptemporary_root_password -e "CREATE DATABASE IF NOT EXISTS admin_dashboard;"
sudo mysql -u root -ptemporary_root_password -e "CREATE USER IF NOT EXISTS 'admin_user'@'localhost' IDENTIFIED BY 'Admin@2024Pass';"
sudo mysql -u root -ptemporary_root_password -e "GRANT ALL PRIVILEGES ON admin_dashboard.* TO 'admin_user'@'localhost';"
sudo mysql -u root -ptemporary_root_password -e "FLUSH PRIVILEGES;"

echo ""
echo -e "${GREEN}Step 4: Setting up application directory...${NC}"

# Create application directory
APP_DIR="/var/www/admin"
sudo mkdir -p $APP_DIR
sudo chown -R $USER:$USER $APP_DIR

# If not already cloned, we assume files are already in current directory
if [ ! -f "composer.json" ]; then
    echo -e "${RED}Error: composer.json not found. Please run this script from the application directory.${NC}"
    exit 1
fi

# Copy files to app directory if not already there
if [ "$PWD" != "$APP_DIR" ]; then
    echo "Copying application files to $APP_DIR..."
    sudo cp -r . $APP_DIR/
    cd $APP_DIR
fi

echo ""
echo -e "${GREEN}Step 5: Installing application dependencies...${NC}"

# Install Composer dependencies
composer install --no-dev --optimize-autoloader --no-interaction

# Install NPM dependencies
npm ci

# Build frontend assets
npm run build

echo ""
echo -e "${GREEN}Step 6: Configuring environment...${NC}"

# Create .env file if it doesn't exist
if [ ! -f ".env" ]; then
    cp .env.example .env
    
    # Generate application key
    php artisan key:generate --force
    
    # Update .env with database credentials
    sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
    sed -i 's/DB_HOST=127.0.0.1/DB_HOST=localhost/' .env
    sed -i 's/DB_PORT=3306/DB_PORT=3306/' .env
    sed -i 's/DB_DATABASE=laravel/DB_DATABASE=admin_dashboard/' .env
    sed -i 's/DB_USERNAME=root/DB_USERNAME=admin_user/' .env
    sed -i 's/DB_PASSWORD=/DB_PASSWORD=Admin@2024Pass/' .env
    
    # Set production environment
    sed -i 's/APP_ENV=local/APP_ENV=production/' .env
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
fi

echo ""
echo -e "${GREEN}Step 7: Setting up database...${NC}"

# Run migrations
php artisan migrate --force

# Seed initial data
php artisan db:seed --force

# Optionally seed sample data
if [ "$WITH_SAMPLE_DATA" = true ]; then
    echo "Seeding sample data..."
    php artisan db:seed --class=SampleDataSeeder --force
fi

echo ""
echo -e "${GREEN}Step 8: Optimizing Laravel...${NC}"

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

echo ""
echo -e "${GREEN}Step 9: Setting file permissions...${NC}"

sudo chown -R www-data:www-data $APP_DIR
sudo chmod -R 775 $APP_DIR/storage
sudo chmod -R 775 $APP_DIR/bootstrap/cache

echo ""
echo -e "${GREEN}Step 10: Configuring Nginx...${NC}"

# Create Nginx configuration
sudo tee /etc/nginx/sites-available/admin > /dev/null <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name _;
    root /var/www/admin/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php index.html;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

# Enable site
sudo ln -sf /etc/nginx/sites-available/admin /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default

# Test Nginx configuration
sudo nginx -t

# Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx

echo ""
echo -e "${GREEN}Step 11: Configuring firewall...${NC}"

# Allow HTTP and HTTPS
sudo ufw allow 'Nginx Full'
sudo ufw allow 22/tcp
sudo ufw --force enable

echo ""
echo -e "${GREEN}Step 12: Setting up automatic restarts...${NC}"

# Enable services to start on boot
sudo systemctl enable nginx
sudo systemctl enable php8.4-fpm
sudo systemctl enable mysql

echo ""
echo "=========================================="
echo -e "${GREEN}Setup completed successfully!${NC}"
echo "=========================================="
echo ""
echo "Your application is now running!"
echo ""
echo "Access your application at:"
echo "  http://$(curl -s ifconfig.me)"
echo ""
echo "Database credentials:"
echo "  Database: admin_dashboard"
echo "  Username: admin_user"
echo "  Password: Admin@2024Pass"
echo ""
echo "MySQL root password: temporary_root_password"
echo ""
echo -e "${YELLOW}IMPORTANT SECURITY STEPS:${NC}"
echo "1. Change MySQL root password immediately"
echo "2. Change database user password"
echo "3. Update .env file with new passwords"
echo "4. Set up SSL certificate (Let's Encrypt recommended)"
echo "5. Change admin panel password after first login"
echo "6. Review and update security settings"
echo ""
echo "Admin credentials are in database/seeders/AdminSeeder.php"
echo ""

if [ "$WITH_SAMPLE_DATA" = true ]; then
    echo "Sample data seeded:"
    echo "  - 8 sample customers"
    echo "  - 16 sample reservations"
    echo "  - Transaction logs"
    echo ""
fi

echo "Next steps:"
echo "1. Set up SSL with: sudo certbot --nginx -d your-domain.com"
echo "2. Configure your domain DNS to point to: $(curl -s ifconfig.me)"
echo "3. Update APP_URL in .env to your domain"
echo "4. Run: php artisan config:cache"
echo ""
echo "To view logs:"
echo "  Application: tail -f $APP_DIR/storage/logs/laravel.log"
echo "  Nginx: sudo tail -f /var/log/nginx/error.log"
echo "  PHP-FPM: sudo tail -f /var/log/php8.4-fpm.log"
echo ""
