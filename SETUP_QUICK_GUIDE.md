# Quick Setup Guide for Google Cloud

## Choose Your Deployment Method

### Option 1: Compute Engine E2 Micro (RECOMMENDED - Most Cost-Effective)
- **Cost**: ~$7-10/month (FREE tier eligible!)
- **Best for**: Budget-conscious, full control, small-medium traffic
- **Setup time**: ~10-15 minutes
- **See**: `GCE_E2_DEPLOYMENT.md` for complete guide

### Option 2: App Engine (Easiest - Fully Managed)
- **Cost**: ~$50-100/month
- **Best for**: Zero server management, auto-scaling
- **Setup time**: ~5-10 minutes
- **See**: `DEPLOYMENT.md` for complete guide

### Option 3: Cloud Run (Flexible - Containerized)
- **Cost**: ~$20-50/month
- **Best for**: Container-based, flexible scaling
- **Setup time**: ~10-15 minutes
- **See**: `DEPLOYMENT.md` for complete guide

---

## E2 Micro Quick Setup (Recommended)

### 1. Create Instance
```bash
gcloud compute instances create admin-app \
  --zone=us-central1-a \
  --machine-type=e2-micro \
  --boot-disk-size=20GB \
  --boot-disk-type=pd-standard \
  --image-family=ubuntu-2204-lts \
  --image-project=ubuntu-os-cloud \
  --tags=http-server,https-server
```

### 2. Upload Files
```bash
# Create tarball
tar -czf admin-app.tar.gz --exclude='node_modules' --exclude='vendor' --exclude='.git' .

# Upload to instance
gcloud compute scp admin-app.tar.gz admin-app:~ --zone=us-central1-a
gcloud compute scp gce-e2-setup.sh admin-app:~ --zone=us-central1-a
```

### 3. Run Setup
```bash
# SSH into instance
gcloud compute ssh admin-app --zone=us-central1-a

# Extract and setup
tar -xzf admin-app.tar.gz
chmod +x gce-e2-setup.sh

# Run setup (production)
sudo ./gce-e2-setup.sh

# OR with sample data (testing/demo)
sudo ./gce-e2-setup.sh --with-sample-data
```

### 4. Access Application
```bash
# Get instance IP
gcloud compute instances describe admin-app \
  --zone=us-central1-a \
  --format='get(networkInterfaces[0].accessConfigs[0].natIP)'

# Open in browser: http://YOUR_IP
```

---

## App Engine Quick Setup

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
