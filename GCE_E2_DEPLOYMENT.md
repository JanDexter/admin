# Google Compute Engine E2 Micro Deployment Guide

## Overview

This guide shows how to deploy the **Co-Z Reservation System** on a **Google Compute Engine e2-micro** instance. This is a cost-effective alternative to App Engine, giving you more control and lower costs.

### Cost Comparison
- **App Engine**: ~$50-100/month (always-on with F2 instance)
- **GCE e2-micro**: ~$7-10/month with 1 e2-micro instance (free tier eligible!)

### E2 Micro Specs
- **vCPU**: 2 shared cores
- **RAM**: 1GB
- **Disk**: 10-30GB standard persistent disk
- **Suitable for**: Small to medium traffic (up to 1000-2000 daily users)

## Prerequisites

1. Google Cloud account with billing enabled
2. `gcloud` CLI installed and configured
3. Basic knowledge of SSH and Linux commands

## Step-by-Step Deployment

### 1. Create GCE Instance

```bash
# Set your project
gcloud config set project YOUR_PROJECT_ID

# Create e2-micro instance (you can change the name)
gcloud compute instances create my-admin-instance \
  --zone=us-central1-a \
  --machine-type=e2-micro \
  --boot-disk-size=20GB \
  --boot-disk-type=pd-standard \
  --image-family=ubuntu-2204-lts \
  --image-project=ubuntu-os-cloud \
  --tags=http-server,https-server

# Allow HTTP and HTTPS traffic
gcloud compute firewall-rules create allow-http \
  --allow tcp:80 \
  --target-tags http-server \
  --description="Allow HTTP traffic"

gcloud compute firewall-rules create allow-https \
  --allow tcp:443 \
  --target-tags https-server \
  --description="Allow HTTPS traffic"
```

**Note**: Change `my-admin-instance` to whatever name you prefer for your instance.

### 2. Get Instance IP Address

```bash
# Get external IP (replace my-admin-instance with your instance name)
gcloud compute instances describe my-admin-instance \
  --zone=us-central1-a \
  --format='get(networkInterfaces[0].accessConfigs[0].natIP)'
```

Save this IP address - you'll need it to access your application.

### 3. SSH into Instance

Connect to your new instance.

```bash
# Replace my-admin-instance with your instance name
gcloud compute ssh my-admin-instance --zone=us-central1-a
```

### 4. Run Deployment Commands

Once connected, execute these commands to deploy the application:

```bash
# Update system and install dependencies
sudo apt-get update && sudo apt-get upgrade -y

# Install required software
sudo apt-get install -y software-properties-common
sudo add-apt-repository ppa:ondrej/php -y
sudo apt-get update

sudo apt-get install -y \
    php8.4 php8.4-fpm php8.4-cli php8.4-mysql php8.4-xml \
    php8.4-mbstring php8.4-curl php8.4-zip php8.4-gd \
    php8.4-bcmath php8.4-intl php8.4-redis \
    nginx mysql-server git curl unzip

# Install Composer
curl -sS https://getcomposer.org/installer | sudo php -- --install-dir=/usr/local/bin --filename=composer

# Install Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt-get install -y nodejs

# Generate secure random passwords
DB_ROOT_PASSWORD=$(openssl rand -base64 32)
DB_USER_PASSWORD=$(openssl rand -base64 32)

# Configure MySQL
sudo mysql -e "ALTER USER 'root'@'localhost' IDENTIFIED WITH mysql_native_password BY '${DB_ROOT_PASSWORD}';"
sudo mysql -u root -p${DB_ROOT_PASSWORD} -e "CREATE DATABASE IF NOT EXISTS coz_reservation;"
sudo mysql -u root -p${DB_ROOT_PASSWORD} -e "CREATE USER IF NOT EXISTS 'coz_user'@'localhost' IDENTIFIED BY '${DB_USER_PASSWORD}';"
sudo mysql -u root -p${DB_ROOT_PASSWORD} -e "GRANT ALL PRIVILEGES ON coz_reservation.* TO 'coz_user'@'localhost';"
sudo mysql -u root -p${DB_ROOT_PASSWORD} -e "FLUSH PRIVILEGES;"

# Save credentials to a secure file (readable only by you)
cat > ~/coz-db-credentials.txt <<EOF
MySQL Root Password: ${DB_ROOT_PASSWORD}
Database User Password: ${DB_USER_PASSWORD}
Database Name: coz_reservation
Database User: coz_user

⚠️  IMPORTANT: Save these credentials securely and delete this file after saving!
⚠️  These passwords are randomly generated and cannot be recovered.
EOF
chmod 600 ~/coz-db-credentials.txt

echo "=========================================="
echo "Database credentials saved to: ~/coz-db-credentials.txt"
echo "IMPORTANT: View and save these credentials NOW!"
echo "=========================================="
cat ~/coz-db-credentials.txt
echo "=========================================="
read -p "Press Enter after you have saved the credentials..."

# Clone repository
cd /var/www
sudo git clone https://github.com/JanDexter/coz-reservation.git coz-reservation
sudo chown -R $USER:$USER coz-reservation
cd coz-reservation

# Install dependencies
composer install --no-dev --optimize-autoloader --no-interaction
npm ci
npm run build

# Configure environment
cp .env.example .env
php artisan key:generate --force
sed -i 's/DB_CONNECTION=sqlite/DB_CONNECTION=mysql/' .env
sed -i 's/DB_HOST=127.0.0.1/DB_HOST=localhost/' .env
sed -i 's/DB_DATABASE=laravel/DB_DATABASE=coz_reservation/' .env
sed -i 's/DB_USERNAME=root/DB_USERNAME=coz_user/' .env
sed -i "s/DB_PASSWORD=/DB_PASSWORD=${DB_USER_PASSWORD}/" .env
sed -i 's/APP_ENV=local/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' .env

# Setup database
php artisan migrate --force
php artisan db:seed --force

# Optional: Seed sample data for testing
# php artisan db:seed --class=SampleDataSeeder --force

# Optimize Laravel
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link

# Set permissions
sudo chown -R www-data:www-data /var/www/coz-reservation
sudo chmod -R 775 /var/www/coz-reservation/storage
sudo chmod -R 775 /var/www/coz-reservation/bootstrap/cache

# Generate SSL certificate
sudo mkdir -p /etc/nginx/ssl
sudo openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/nginx/ssl/nginx-selfsigned.key \
    -out /etc/nginx/ssl/nginx-selfsigned.crt \
    -subj "/C=US/ST=California/L=SanFrancisco/O=CoZ/OU=IT/CN=$(curl -s ifconfig.me)"

# Configure Nginx
sudo tee /etc/nginx/sites-available/coz-reservation > /dev/null <<'EOF'
server {
    listen 80;
    listen [::]:80;
    server_name _;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name _;
    root /var/www/coz-reservation/public;

    ssl_certificate /etc/nginx/ssl/nginx-selfsigned.crt;
    ssl_certificate_key /etc/nginx/ssl/nginx-selfsigned.key;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers off;

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

    location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
EOF

sudo ln -sf /etc/nginx/sites-available/coz-reservation /etc/nginx/sites-enabled/
sudo rm -f /etc/nginx/sites-enabled/default
sudo nginx -t
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx

# Configure firewall
sudo ufw allow 'Nginx Full'
sudo ufw allow 22/tcp
sudo ufw --force enable

# Enable services on boot
sudo systemctl enable nginx
sudo systemctl enable php8.4-fpm
sudo systemctl enable mysql

echo ""
echo "=========================================="
echo "Deployment complete!"
echo "=========================================="
echo "Access your app at: https://$(curl -s ifconfig.me)"
echo ""
echo "⚠️  IMPORTANT SECURITY REMINDER:"
echo "Your database credentials are in: ~/coz-db-credentials.txt"
echo "Save them securely, then delete the file:"
echo "  rm ~/coz-db-credentials.txt"
echo "=========================================="
```

**This will take approximately 10-15 minutes to complete.**

**IMPORTANT:** The script will pause to show you the randomly generated database passwords. Make sure to save them in a secure password manager before continuing!

### 5. Access Your Application

Open your browser and navigate to the HTTPS URL shown at the end of the deployment (your instance's IP address).

**Note:** You will see a browser warning because the SSL certificate is self-signed. This is expected. Click "Advanced" and proceed to access the Co-Z Reservation System.

Default admin credentials are in `database/seeders/AdminSeeder.php`.

## Post-Setup Configuration

### 1. Secure Your Database Credentials

```bash
# View the saved credentials
cat ~/coz-db-credentials.txt

# After saving them securely to a password manager, delete the file
rm ~/coz-db-credentials.txt
```

### 2. Set Up Domain Name (Recommended)

```bash
# In your domain registrar, create an A record pointing to your instance IP
# Example: coz.yourdomain.com -> YOUR_INSTANCE_IP

# Update APP_URL in .env
sudo nano /var/www/coz-reservation/.env
# Change APP_URL to: https://coz.yourdomain.com

# Clear cache
cd /var/www/coz-reservation
php artisan config:cache
```

### 3. Install SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Get SSL certificate (replace with your domain)
sudo certbot --nginx -d coz.yourdomain.com

# Certbot will automatically configure Nginx for HTTPS
# Certificate will auto-renew via cron
```

### 4. Configure Email (Optional)

```bash
# Change MySQL root password
sudo mysql -u root -ptemporary_root_password
ALTER USER 'root'@'localhost' IDENTIFIED BY 'NewStrongPassword123!';
EXIT;

# Change database user password
sudo mysql -u root -pNewStrongPassword123!
ALTER USER 'admin_user'@'localhost' IDENTIFIED BY 'NewDbPassword123!';
EXIT;

# Update .env file
sudo nano /var/www/admin/.env
# Update DB_PASSWORD=NewDbPassword123!

# Clear cache
cd /var/www/admin
php artisan config:cache
sudo systemctl restart php8.4-fpm
```

### 4. Configure Email (Optional)

```bash
# Edit .env file
sudo nano /var/www/coz-reservation/.env

# Add your email settings (example for Gmail)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Co-Z Reservation System"

# Clear cache
cd /var/www/coz-reservation
php artisan config:cache
```

**SECURITY NOTE:** Never commit your `.env` file to Git. It's already in `.gitignore`.

## Maintenance & Management

### Application Updates

```bash
# SSH into instance (replace YOUR_INSTANCE with your instance name)
gcloud compute ssh YOUR_INSTANCE --zone=us-central1-a

# Navigate to app directory
cd /var/www/coz-reservation

# Backup database first (you'll be prompted for password)
sudo mysqldump -u coz_user -p coz_reservation > ~/backup-$(date +%Y%m%d).sql

# Pull latest changes (if using git)
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader
npm ci
npm run build

# Run migrations
php artisan migrate --force

# Clear and cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart services
sudo systemctl restart php8.4-fpm
sudo systemctl restart nginx
```

### Database Backup

```bash
# Create backup (you'll be prompted for the database password)
sudo mysqldump -u coz_user -p coz_reservation > backup-$(date +%Y%m%d-%H%M%S).sql

# Compress backup
gzip backup-*.sql

# Download backup to local machine
# On your local machine:
gcloud compute scp YOUR_INSTANCE:~/backup-*.sql.gz . --zone=us-central1-a
```

### Monitoring & Logs

```bash
# Application logs
sudo tail -f /var/www/coz-reservation/storage/logs/laravel.log

# Nginx access logs
sudo tail -f /var/log/nginx/access.log

# Nginx error logs
sudo tail -f /var/log/nginx/error.log

# PHP-FPM logs
sudo tail -f /var/log/php8.4-fpm.log

# System resources
htop
df -h
free -m
```

### Performance Optimization

```bash
# Enable OPcache (already enabled by default)
# Configure PHP-FPM for better performance
sudo nano /etc/php/8.4/fpm/pool.d/www.conf

# Adjust these values based on your traffic:
pm = dynamic
pm.max_children = 5
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3

# Restart PHP-FPM
sudo systemctl restart php8.4-fpm
```

### Enable Swap (Recommended for 1GB RAM)

```bash
# Create 2GB swap file
sudo fallocate -l 2G /swapfile
sudo chmod 600 /swapfile
sudo mkswap /swapfile
sudo swapon /swapfile

# Make permanent
echo '/swapfile none swap sw 0 0' | sudo tee -a /etc/fstab

# Verify
free -m
```

## Cost Optimization

### Always Free Tier Eligible
Google Cloud offers an **e2-micro** instance in the free tier:
- **1 e2-micro instance per month** (us-west1, us-central1, or us-east1)
- **30 GB-months standard persistent disk**
- **1 GB of outbound data transfer**

To stay within free tier:
1. Use `us-central1-a`, `us-west1-b`, or `us-east1-b` zone
2. Keep disk ≤ 30GB
3. Limit outbound traffic to ~1GB/month

### Additional Cost Savings

```bash
# Schedule automatic shutdown during off-hours (optional)
# Create a shutdown script
echo '0 2 * * * root /sbin/shutdown -h now' | sudo tee -a /etc/crontab

# Or use Cloud Scheduler to stop/start instance (replace YOUR_INSTANCE)
gcloud compute instances stop YOUR_INSTANCE --zone=us-central1-a
gcloud compute instances start YOUR_INSTANCE --zone=us-central1-a
```

## Troubleshooting

### Application Not Accessible

```bash
# Check if Nginx is running
sudo systemctl status nginx

# Check if PHP-FPM is running
sudo systemctl status php8.4-fpm

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.4-fpm

# Check firewall rules
sudo ufw status
```

### Database Connection Failed

```bash
# Check MySQL status
sudo systemctl status mysql

# Test database connection (you'll be prompted for password)
mysql -u coz_user -p coz_reservation

# Check .env file
cat /var/www/coz-reservation/.env | grep DB_

# Verify credentials in your saved file (if not deleted)
cat ~/coz-db-credentials.txt
```

### Out of Memory Errors

```bash
# Check memory usage
free -m

# Enable swap if not already done (see above)

# Reduce PHP-FPM workers
sudo nano /etc/php/8.4/fpm/pool.d/www.conf
# Set pm.max_children = 3

sudo systemctl restart php8.4-fpm
```

### Permission Issues

```bash
# Fix storage permissions
cd /var/www/coz-reservation
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Scaling Options

### Vertical Scaling (Upgrade Instance)

```bash
# Stop instance (replace YOUR_INSTANCE with your instance name)
gcloud compute instances stop YOUR_INSTANCE --zone=us-central1-a

# Change machine type to e2-small (2GB RAM)
gcloud compute instances set-machine-type YOUR_INSTANCE \
  --machine-type=e2-small \
  --zone=us-central1-a

# Start instance
gcloud compute instances start YOUR_INSTANCE --zone=us-central1-a
```

### Horizontal Scaling (Multiple Instances + Load Balancer)

For high traffic, consider:
1. Use Cloud SQL instead of local MySQL
2. Use Cloud Storage for file uploads
3. Set up multiple instances behind a load balancer
4. Use Cloud CDN for static assets

## Security Best Practices

1. ✅ **Change all default passwords** immediately
2. ✅ **Set up SSL certificate** with Let's Encrypt
3. ✅ **Enable firewall** (UFW is enabled by setup script)
4. ✅ **Regular backups** (database and application files)
5. ✅ **Keep system updated**: `sudo apt-get update && sudo apt-get upgrade -y`
6. ✅ **Monitor logs** regularly for suspicious activity
7. ✅ **Use SSH keys** instead of passwords
8. ✅ **Restrict database** access to localhost only
9. ✅ **Set APP_DEBUG=false** in production
10. ✅ **Use environment variables** for sensitive data

## Useful Commands

```bash
# SSH into instance (replace YOUR_INSTANCE with your instance name)
gcloud compute ssh YOUR_INSTANCE --zone=us-central1-a

# Copy files to instance
gcloud compute scp local-file.txt YOUR_INSTANCE:~ --zone=us-central1-a

# Copy files from instance
gcloud compute scp YOUR_INSTANCE:~/remote-file.txt . --zone=us-central1-a

# Stop instance
gcloud compute instances stop YOUR_INSTANCE --zone=us-central1-a

# Start instance
gcloud compute instances start YOUR_INSTANCE --zone=us-central1-a

# Delete instance
gcloud compute instances delete YOUR_INSTANCE --zone=us-central1-a

# View instance details
gcloud compute instances describe YOUR_INSTANCE --zone=us-central1-a
```

## Support & Resources

- [GCE Documentation](https://cloud.google.com/compute/docs)
- [GCE Pricing Calculator](https://cloud.google.com/products/calculator)
- [Always Free Tier](https://cloud.google.com/free/docs/free-cloud-features#compute)
- [Laravel Deployment](https://laravel.com/docs/deployment)

## Summary

✅ **Cost-effective**: ~$7-10/month (or FREE with always free tier)  
✅ **Full control**: Root access, custom configurations  
✅ **Scalable**: Easy to upgrade instance type  
✅ **Reliable**: Google's infrastructure  
✅ **Automated setup**: One-script deployment  

Perfect for small to medium traffic applications with budget constraints!
