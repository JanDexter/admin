# Google Compute Engine E2 Micro Deployment Guide

## Overview

This guide shows how to deploy the Laravel application on a **Google Compute Engine e2-micro** instance. This is a cost-effective alternative to App Engine, giving you more control and lower costs.

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

# Create e2-micro instance
gcloud compute instances create admin-app \
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

### 2. Get Instance IP Address

```bash
# Get external IP
gcloud compute instances describe admin-app \
  --zone=us-central1-a \
  --format='get(networkInterfaces[0].accessConfigs[0].natIP)'
```

Save this IP address - you'll need it to access your application.

### 3. Upload Application Files

```bash
# Create a tarball of your application (exclude unnecessary files)
tar -czf admin-app.tar.gz \
  --exclude='node_modules' \
  --exclude='vendor' \
  --exclude='.git' \
  --exclude='storage/logs/*' \
  --exclude='storage/framework/cache/*' \
  --exclude='storage/framework/sessions/*' \
  --exclude='storage/framework/views/*' \
  .

# Upload to instance
gcloud compute scp admin-app.tar.gz admin-app:~ --zone=us-central1-a

# Upload setup script
gcloud compute scp gce-e2-setup.sh admin-app:~ --zone=us-central1-a
```

### 4. SSH into Instance

```bash
gcloud compute ssh admin-app --zone=us-central1-a
```

### 5. Extract and Run Setup

```bash
# Extract application files
tar -xzf admin-app.tar.gz
cd admin

# Make setup script executable
chmod +x gce-e2-setup.sh

# Run setup script (production mode)
sudo ./gce-e2-setup.sh

# OR run with sample data (for testing/demo)
sudo ./gce-e2-setup.sh --with-sample-data
```

The setup script will:
- ✅ Install PHP 8.4, Nginx, MySQL, Node.js
- ✅ Configure MySQL database
- ✅ Install application dependencies
- ✅ Build frontend assets
- ✅ Run migrations and seed data
- ✅ Configure Nginx
- ✅ Set up firewall
- ✅ Optimize Laravel for production

### 6. Access Your Application

Open your browser and navigate to:
```
http://YOUR_INSTANCE_IP
```

Default admin credentials are in `database/seeders/AdminSeeder.php`.

## Post-Setup Configuration

### 1. Set Up Domain Name (Recommended)

```bash
# In your domain registrar, create an A record pointing to your instance IP
# Example: admin.yourdomain.com -> YOUR_INSTANCE_IP

# Update APP_URL in .env
sudo nano /var/www/admin/.env
# Change APP_URL to: https://admin.yourdomain.com

# Clear cache
cd /var/www/admin
php artisan config:cache
```

### 2. Install SSL Certificate (Let's Encrypt)

```bash
# Install Certbot
sudo apt-get install -y certbot python3-certbot-nginx

# Get SSL certificate
sudo certbot --nginx -d admin.yourdomain.com

# Certbot will automatically configure Nginx for HTTPS
# Certificate will auto-renew via cron
```

### 3. Change Default Passwords

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

### 4. Configure Email (Gmail SMTP)

```bash
# Edit .env file
sudo nano /var/www/admin/.env

# Add email configuration:
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=your-email@gmail.com
MAIL_FROM_NAME="Admin Panel"

# Clear cache
cd /var/www/admin
php artisan config:cache
```

## Maintenance & Management

### Application Updates

```bash
# SSH into instance
gcloud compute ssh admin-app --zone=us-central1-a

# Navigate to app directory
cd /var/www/admin

# Backup database first
sudo mysqldump -u admin_user -pAdmin@2024Pass admin_dashboard > ~/backup-$(date +%Y%m%d).sql

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
# Create backup
sudo mysqldump -u admin_user -pAdmin@2024Pass admin_dashboard > backup-$(date +%Y%m%d-%H%M%S).sql

# Compress backup
gzip backup-*.sql

# Download backup to local machine
# On your local machine:
gcloud compute scp admin-app:~/backup-*.sql.gz . --zone=us-central1-a
```

### Monitoring & Logs

```bash
# Application logs
sudo tail -f /var/www/admin/storage/logs/laravel.log

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

# Or use Cloud Scheduler to stop/start instance
gcloud compute instances stop admin-app --zone=us-central1-a
gcloud compute instances start admin-app --zone=us-central1-a
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

# Test database connection
mysql -u admin_user -pAdmin@2024Pass admin_dashboard

# Check .env file
cat /var/www/admin/.env | grep DB_
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
cd /var/www/admin
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

## Scaling Options

### Vertical Scaling (Upgrade Instance)

```bash
# Stop instance
gcloud compute instances stop admin-app --zone=us-central1-a

# Change machine type to e2-small (2GB RAM)
gcloud compute instances set-machine-type admin-app \
  --machine-type=e2-small \
  --zone=us-central1-a

# Start instance
gcloud compute instances start admin-app --zone=us-central1-a
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
# SSH into instance
gcloud compute ssh admin-app --zone=us-central1-a

# Copy files to instance
gcloud compute scp local-file.txt admin-app:~ --zone=us-central1-a

# Copy files from instance
gcloud compute scp admin-app:~/remote-file.txt . --zone=us-central1-a

# Stop instance
gcloud compute instances stop admin-app --zone=us-central1-a

# Start instance
gcloud compute instances start admin-app --zone=us-central1-a

# Delete instance
gcloud compute instances delete admin-app --zone=us-central1-a

# View instance details
gcloud compute instances describe admin-app --zone=us-central1-a
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
