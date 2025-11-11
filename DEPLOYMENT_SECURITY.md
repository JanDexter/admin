# Deployment Security Best Practices

## Overview

This document outlines security best practices for deploying the Co-Z Reservation System. Following these guidelines will help protect your application and data.

## 🔐 Password Management

### During Deployment

1. **Randomly Generated Passwords**: Our deployment scripts automatically generate secure random passwords using `openssl rand -base64 32`
2. **Temporary Storage**: Passwords are saved to `~/coz-db-credentials.txt` with `600` permissions (readable only by you)
3. **Immediate Action Required**: Save passwords to a secure password manager immediately
4. **Delete After Saving**: Run `rm ~/coz-db-credentials.txt` after securely storing credentials

### Password Requirements

- **MySQL Root Password**: 32+ character random string
- **Database User Password**: 32+ character random string  
- **Admin Panel Password**: Change default immediately after first login
- **Never Use**: Dictionary words, personal information, or predictable patterns

## 🚫 What Never to Commit to Git

### Absolutely Never Commit

- `.env` files (already in `.gitignore`)
- Database credentials files (`*-credentials.txt`)
- SSL certificates and private keys (`*.pem`, `*.key`, `*.crt`)
- API keys and secrets
- Production configuration with sensitive data

### Our Protections

```gitignore
# Already protected in .gitignore
.env
.env.*
*-credentials.txt
*.pem
*.key
*.crt
```

## 🔒 Environment File Security

### .env File Best Practices

1. **Never Commit**: `.env` is in `.gitignore` - keep it that way
2. **Different Per Environment**: Use different credentials for dev/staging/production
3. **Restrict Permissions**: `chmod 600 .env` on server
4. **Backup Securely**: Store encrypted backups in password manager, not in repository

### Example .env Structure

```env
# Application
APP_KEY=base64:RANDOM_KEY_HERE  # Auto-generated, keep secure
APP_ENV=production              # Never 'local' in production
APP_DEBUG=false                 # Never 'true' in production

# Database - Use strong random passwords
DB_CONNECTION=mysql
DB_HOST=localhost
DB_DATABASE=coz_reservation
DB_USERNAME=coz_user
DB_PASSWORD=RANDOM_32_CHAR_PASSWORD_HERE  # Never hardcoded!

# Mail - Use app-specific passwords
MAIL_PASSWORD=APP_SPECIFIC_PASSWORD  # Never your actual email password
```

## 🛡️ Server Security

### Firewall Configuration

```bash
# Only allow necessary ports
sudo ufw allow 22/tcp    # SSH
sudo ufw allow 80/tcp    # HTTP (redirects to HTTPS)
sudo ufw allow 443/tcp   # HTTPS
sudo ufw enable
```

### SSH Security

```bash
# Use SSH keys, not passwords
# Disable password authentication in /etc/ssh/sshd_config:
PasswordAuthentication no

# Use Google Cloud IAM for access control
# Don't share SSH keys
```

### File Permissions

```bash
# Application files
sudo chown -R www-data:www-data /var/www/admin
sudo chmod -R 755 /var/www/admin

# Storage and cache (writable)
sudo chmod -R 775 /var/www/admin/storage
sudo chmod -R 775 /var/www/admin/bootstrap/cache

# .env file (readable only by owner)
chmod 600 /var/www/admin/.env
```

## 🔄 Secure Update Process

### Before Updating

1. **Backup Database**:
   ```bash
   mysqldump -u coz_user -p coz_reservation > backup-$(date +%Y%m%d).sql
   ```

2. **Test in Staging**: Never test updates directly in production

3. **Review Changes**: Check git diff before pulling updates

### During Updates

```bash
# Pull from GitHub (never commit secrets!)
git pull origin main

# Update dependencies
composer install --no-dev --optimize-autoloader
npm ci && npm run build

# Run migrations
php artisan migrate --force

# Clear caches
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## 🌐 SSL/TLS Configuration

### Production SSL

```bash
# Use Let's Encrypt for trusted certificates
sudo certbot --nginx -d yourdomain.com

# Auto-renewal is configured automatically
```

### Development/Testing

- Self-signed certificates are OK for testing
- Never use self-signed in production (browser warnings)
- Update to Let's Encrypt before going live

## 📊 Monitoring and Logging

### What to Monitor

- Failed login attempts
- Database connection errors
- Disk space usage
- Memory usage
- Suspicious traffic patterns

### Log Files

```bash
# Application logs
tail -f /var/www/admin/storage/logs/laravel.log

# Nginx logs
tail -f /var/log/nginx/error.log
tail -f /var/log/nginx/access.log

# MySQL logs
tail -f /var/log/mysql/error.log
```

### Log Rotation

Ensure logs don't fill disk space:
```bash
# Laravel logs are automatically rotated
# Nginx logs rotate via logrotate (already configured)
```

## 🚨 Incident Response

### If Credentials Are Compromised

1. **Immediately Change All Passwords**:
   ```bash
   # MySQL
   mysql -u root -p
   ALTER USER 'coz_user'@'localhost' IDENTIFIED BY 'NEW_RANDOM_PASSWORD';
   
   # Update .env
   nano /var/www/admin/.env
   php artisan config:cache
   ```

2. **Review Access Logs**: Check who accessed what
3. **Revoke Access**: Remove compromised API keys, tokens
4. **Notify Stakeholders**: If customer data was accessed

### If .env is Accidentally Committed

1. **Remove from Git History**:
   ```bash
   # This is complex - use tools like git-filter-repo
   # Better: Rotate all secrets immediately
   ```

2. **Rotate All Secrets**: Change every password, API key, token
3. **Review**: Who had access to the repository?

## ✅ Security Checklist

Before going live, verify:

- [ ] `.env` is in `.gitignore` and not committed
- [ ] All passwords are random and 32+ characters
- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production` in production
- [ ] SSL certificate is installed (Let's Encrypt)
- [ ] Firewall is configured (only ports 22, 80, 443)
- [ ] Database passwords are unique and secure
- [ ] Admin panel password changed from default
- [ ] File permissions are correct (775 for storage, 600 for .env)
- [ ] Logs are monitored
- [ ] Backups are automated and tested
- [ ] No credentials in git history
- [ ] `~/coz-db-credentials.txt` deleted from server

## 📚 Additional Resources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [Laravel Security Best Practices](https://laravel.com/docs/security)
- [Google Cloud Security](https://cloud.google.com/security/best-practices)
- [Let's Encrypt Documentation](https://letsencrypt.org/docs/)

## 🆘 Need Help?

If you discover a security vulnerability:

1. **Do NOT** create a public issue
2. Report privately to the repository maintainer
3. Allow time for a fix before public disclosure

---

**Remember**: Security is not a one-time setup. Regularly review and update your security practices.
