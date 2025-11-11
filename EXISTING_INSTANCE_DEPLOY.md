# Deploy Co-Z Reservation System to an Existing GCE Instance

This guide provides step-by-step instructions to deploy the Co-Z Reservation System onto an existing Ubuntu GCE instance.

---

## Step 1: SSH into Your Instance

First, connect to your GCE instance using the Google Cloud SDK.

```bash
# Replace with YOUR actual instance name and zone
gcloud compute ssh YOUR_INSTANCE_NAME --zone=YOUR_ZONE
```

---

## Step 2: System Preparation & Updates

Update your system's package list and install essential software.

```bash
# Update package lists and upgrade existing packages
sudo apt-get update && sudo apt-get upgrade -y

# Install prerequisites
sudo apt-get install -y software-properties-common curl unzip git ca-certificates
```

---

## Step 3: Install PHP 8.4

We'll use a PPA to get the latest version of PHP.

```bash
# Add the PHP PPA and install PHP 8.4 with required extensions
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update
sudo apt-get install -y \
    php8.2 php8.2-fpm php8.2-cli php8.2-mysql php8.2-xml \
    php8.2-mbstring php8.2-curl php8.2-zip php8.2-gd \
    php8.2-bcmath php8.2-intl php8.2-redis
```

---

## Step 4: Install and Configure MariaDB

We will use MariaDB as the database server. This replaces the previous MySQL setup.

```bash
# Install MariaDB server
sudo apt-get install -y mariadb-server

# Start and enable the MariaDB service
sudo systemctl start mariadb
sudo systemctl enable mariadb

# Wait for MariaDB to be ready before proceeding
echo "Waiting for MariaDB to start..."
while ! sudo mysqladmin ping -h localhost --silent; do
    sleep 1
done
echo "MariaDB is running."
```

---

## Step 5: Secure MariaDB and Create the Application Database

Generate secure passwords and configure the database and user for the application.

```bash
# Generate secure, random passwords for the database
DB_ROOT_PASSWORD=$(openssl rand -base64 32)
DB_USER_PASSWORD=$(openssl rand -base64 32)

# Secure the MariaDB root user and create the database and application user
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED BY '${DB_ROOT_PASSWORD}';"
sudo mysql -u root -p"${DB_ROOT_PASSWORD}" -e "CREATE DATABASE IF NOT EXISTS coz_reservation;"
sudo mysql -u root -p"${DB_ROOT_PASSWORD}" -e "CREATE USER IF NOT EXISTS 'coz_user'@'localhost' IDENTIFIED BY '${DB_USER_PASSWORD}';"
sudo mysql -u root -p"${DB_ROOT_PASSWORD}" -e "GRANT ALL PRIVILEGES ON coz_reservation.* TO 'coz_user'@'localhost';"
sudo mysql -u root -p"${DB_ROOT_PASSWORD}" -e "FLUSH PRIVILEGES;"

# Save credentials to a secure, temporary file
cat > ~/coz-db-credentials.txt <<EOF
MariaDB Root Password: ${DB_ROOT_PASSWORD}
Database User Password: ${DB_USER_PASSWORD}
Database Name: coz_reservation
Database User: coz_user
EOF
chmod 600 ~/coz-db-credentials.txt

# Display credentials and wait for user confirmation
echo "========================================================"
echo "IMPORTANT: Database credentials saved to: ~/coz-db-credentials.txt"
echo "View, copy, and save these credentials in a secure location NOW."
echo "The script will pause until you press Enter."
echo "========================================================"
cat ~/coz-db-credentials.txt
echo "========================================================"
read -p "Press Enter once you have saved the credentials..."
```

---

## Step 6: Install Node.js 22.x and Composer

Install the latest LTS version of Node.js for frontend asset building and Composer for PHP dependencies.

```bash
# Install Node.js v22.x (Latest LTS)
curl -fsSL https://deb.nodesource.com/setup_22.x | sudo -E bash -
sudo apt-get install -y nodejs

# Install Composer globally
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer
```

---

## Step 7: Clone and Set Up the Application

Clone the project from GitHub and install all dependencies.

```bash
# Navigate to the web root directory
cd /var/www

# Clone the repository and set initial ownership
sudo git clone https://github.com/JanDexter/coz-reservation.git coz-reservation
sudo chown -R $USER:$USER coz-reservation
cd coz-reservation

# Install PHP and Node.js dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build
```

---

## Step 8: Configure Application Environment

Set up the `.env` file with the correct database credentials and production settings.

```bash
# Get the server's public IP address
PUBLIC_IP=$(curl -s ifconfig.me)

# Create the .env file and generate an application key
cp .env.example .env
php artisan key:generate --force

# Automatically configure the .env file for production
sed -i "s#APP_URL=http://localhost#APP_URL=https://${PUBLIC_IP}#" .env
sed -i 's/APP_ENV=local/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env
sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
sed -i 's/DB_HOST=127.0.0.1/DB_HOST=localhost/' .env
sed -i 's/DB_DATABASE=laravel/DB_DATABASE=coz_reservation/' .env
sed -i 's/DB_USERNAME=root/DB_USERNAME=coz_user/' .env
sed -i "s#DB_PASSWORD=#DB_PASSWORD=${DB_USER_PASSWORD}#" .env
sed -i "s#GOOGLE_REDIRECT_URI=http://127.0.0.1:8000/auth/google/callback#GOOGLE_REDIRECT_URI=https://${PUBLIC_IP}/auth/google/callback#" .env
```

---

## Step 9: Finalize Application Setup

Run database migrations, seed initial data, and optimize the application.

```bash
# Run database migrations and seeders
php artisan migrate --force
php artisan db:seed --force
# Optional: php artisan db:seed --class=SampleDataSeeder --force

# Optimize Laravel for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

# Set final web server permissions
sudo chown -R www-data:www-data /var/www/coz-reservation
sudo chmod -R 775 /var/www/coz-reservation/storage
sudo chmod -R 775 /var/www/coz-reservation/bootstrap/cache
```

---

## Step 10: Install and Configure Nginx

Set up the Nginx web server to serve the application over HTTPS.

```bash
# Install Nginx
sudo apt-get install -y nginx

# Create a directory for the self-signed SSL certificate
sudo mkdir -p /etc/nginx/ssl

# Generate a self-signed SSL certificate
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/nginx-selfsigned.key \
    -out /etc/nginx/ssl/nginx-selfsigned.crt \
    -subj "/C=US/ST=CA/L=SF/O=CoZ/CN=$(curl -s ifconfig.me)"

# Create the Nginx server block configuration
sudo tee /etc/nginx/sites-available/coz-reservation > /dev/null <<'EOF'
server {
    listen 80;
    server_name _;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    server_name _;
    root /var/www/coz-reservation/public;

    ssl_certificate /etc/nginx/ssl/nginx-selfsigned.crt;
    ssl_certificate_key /etc/nginx/ssl/nginx-selfsigned.key;

    index index.php;
    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOF

# Enable the site and restart services
sudo ln -sf /etc/nginx/sites-available/coz-reservation /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart nginx
sudo systemctl restart php8.2-fpm
```

---

## Step 11: Configure Firewall

Allow HTTP and HTTPS traffic through the firewall.

```bash
# Allow Nginx and SSH traffic
sudo ufw allow 'Nginx Full'
sudo ufw allow 'OpenSSH'
sudo ufw --force enable
```

---

## Step 12: Deployment Complete!

Your application is now deployed.

```bash
# Final confirmation message
echo "=========================================="
echo "✅ Deployment complete!"
echo "Access your app at: https://$(curl -s ifconfig.me)"
echo ""
echo "⚠️ REMINDER: Your database credentials were saved to ~/coz-db-credentials.txt"
echo "   Delete the file after you have stored them securely: rm ~/coz-db-credentials.txt"
echo "=========================================="
```
