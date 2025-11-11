# Quick Setup Guide for Google Cloud

## First-Time Deployment Checklist

### 1. Prerequisites
- [ ] Google Cloud project created
- [ ] Cloud SQL instance created and running
- [ ] Database credentials configured
- [ ] `gcloud` CLI installed and authenticated

### 2. Deploy Application
```bash
# Deploy to App Engine
gcloud app deploy app.yaml
```

### 3. First-Time Setup

**SSH into your instance:**
```bash
# List instances to get INSTANCE_ID and VERSION
gcloud app instances list

# SSH into instance
gcloud app instances ssh [INSTANCE_ID] --service=default --version=[VERSION]
```

**Run setup script:**
```bash
# Navigate to app directory
cd /workspace

# For Production (admin user + spaces only)
./gcp-setup.sh

# For Testing/Demo (includes sample data)
./gcp-setup.sh --with-sample-data
```

### 4. Access Your Application
- URL: `https://YOUR_PROJECT_ID.appspot.com`
- Admin login: Check `database/seeders/AdminSeeder.php` for credentials
- **IMPORTANT:** Change admin password immediately after first login!

---

## What Gets Seeded?

### Basic Setup (`./gcp-setup.sh`)
- ✅ Admin user account
- ✅ Initial space types and spaces
- ✅ Database structure (migrations)

### With Sample Data (`./gcp-setup.sh --with-sample-data`)
- ✅ Everything from basic setup, plus:
- ✅ 8 sample customers (4 individual, 4 company)
- ✅ 16 sample reservations (various statuses)
- ✅ Transaction logs (payments, refunds, cancellations)

---

## Regular Updates (After Initial Setup)

For subsequent deployments, just run:
```bash
gcloud app deploy app.yaml
```

The `gcp-deploy.sh` script automatically:
- Runs new migrations
- Caches configuration
- Sets permissions

**No need to re-run setup script!**

---

## Troubleshooting

### Can't SSH into instance?
```bash
# Make sure instance is running
gcloud app instances list

# If no instances, try accessing your app URL to spin one up
# Then try SSH again
```

### Permission denied on script?
```bash
# Make script executable
chmod +x gcp-setup.sh
```

### Database connection failed?
- Check Cloud SQL instance is running: `gcloud sql instances list`
- Verify `app.yaml` has correct `cloud_sql_instances` configuration
- Check database credentials in environment variables

### Need to reset database?
```bash
# SSH into instance
gcloud app instances ssh [INSTANCE_ID] --service=default --version=[VERSION]

# Fresh migration (WARNING: destroys all data)
cd /workspace
php artisan migrate:fresh --force

# Re-seed
php artisan db:seed --force
```

---

## Quick Commands Reference

```bash
# View logs
gcloud app logs tail -s default

# List versions
gcloud app versions list

# List instances
gcloud app instances list

# SSH into instance
gcloud app instances ssh [INSTANCE_ID] --service=default --version=[VERSION]

# Deploy
gcloud app deploy app.yaml

# Open app in browser
gcloud app browse
```

---

## Need More Help?

See complete documentation:
- `GCP_README.md` - Overview and quick start
- `DEPLOYMENT.md` - Detailed deployment guide
