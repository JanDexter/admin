# Deployment Guide: Laravel Inertia.js App on Google Cloud Platform (GCP)

## Prerequisites

1. **Google Cloud Account** with billing enabled
2. **Google Cloud SDK (gcloud CLI)** installed: https://cloud.google.com/sdk/docs/install
3. **A GCP Project** created

## Setup Instructions

### 1. Install and Configure Google Cloud SDK

```bash
# Install gcloud CLI (if not already installed)
# Visit: https://cloud.google.com/sdk/docs/install

# Initialize and authenticate
gcloud init

# Login to your Google account
gcloud auth login

# Set your project
gcloud config set project YOUR_PROJECT_ID
```

### 2. Enable Required APIs

```bash
# Enable necessary GCP services
gcloud services enable \
  appengine.googleapis.com \
  cloudbuild.googleapis.com \
  sqladmin.googleapis.com \
  secretmanager.googleapis.com
```

### 3. Set Up Cloud SQL (MySQL Database)

```bash
# Create a Cloud SQL instance
gcloud sql instances create admin-db \
  --database-version=MYSQL_8_0 \
  --tier=db-f1-micro \
  --region=us-central1

# Create database
gcloud sql databases create admin_dashboard \
  --instance=admin-db

# Create database user
gcloud sql users create admin-user \
  --instance=admin-db \
  --password=YOUR_SECURE_PASSWORD

# Get instance connection name (you'll need this)
gcloud sql instances describe admin-db --format="value(connectionName)"
```

### 4. Set Up Environment Variables with Secret Manager

```bash
# Generate Laravel APP_KEY
php artisan key:generate --show

# Create secrets in Secret Manager
echo -n "YOUR_APP_KEY_HERE" | gcloud secrets create app-key --data-file=-
echo -n "YOUR_DB_PASSWORD" | gcloud secrets create db-password --data-file=-

# Grant App Engine access to secrets
PROJECT_ID=$(gcloud config get-value project)
PROJECT_NUMBER=$(gcloud projects describe $PROJECT_ID --format="value(projectNumber)")

gcloud secrets add-iam-policy-binding app-key \
  --member="serviceAccount:${PROJECT_NUMBER}@cloudbuild.gserviceaccount.com" \
  --role="roles/secretmanager.secretAccessor"

gcloud secrets add-iam-policy-binding db-password \
  --member="serviceAccount:${PROJECT_NUMBER}@cloudbuild.gserviceaccount.com" \
  --role="roles/secretmanager.secretAccessor"
```

### 5. Create Production .env File

Create `.env.production` in your project root:

```env
APP_NAME="CO-Z Admin"
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_FROM_SECRET_MANAGER
APP_DEBUG=false
APP_URL=https://YOUR_PROJECT_ID.appspot.com

ADMIN_LOGIN_PATH=coz-control-access
ADMIN_AREA_PREFIX=coz-control

LOG_CHANNEL=stderr
LOG_LEVEL=warning

DB_CONNECTION=mysql
DB_HOST=/cloudsql/YOUR_CONNECTION_NAME
DB_PORT=3306
DB_DATABASE=admin_dashboard
DB_USERNAME=admin-user
DB_PASSWORD=YOUR_DB_PASSWORD_FROM_SECRET_MANAGER

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=true
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax

CACHE_DRIVER=database
QUEUE_CONNECTION=database

BCRYPT_ROUNDS=12
```

### 6. Update app.yaml with Cloud SQL Connection

Edit `app.yaml` and add:

```yaml
beta_settings:
  cloud_sql_instances: YOUR_CONNECTION_NAME
```

### 7. Build Frontend Assets Locally

```bash
# Install dependencies
npm ci

# Build production assets
npm run build

# Verify build folder exists
ls -la public/build
```

### 8. Deploy to App Engine

```bash
# Make build script executable
chmod +x gcp-build.sh gcp-deploy.sh

# Deploy to App Engine
gcloud app deploy app.yaml --quiet

# Run migrations after first deployment
gcloud app deploy dispatch.yaml
gcloud sql connect admin-db --user=admin-user
# Then run: php artisan migrate --force
```

## Alternative: Deploy to Cloud Run (Recommended for better scaling)

### 1. Build and Push Docker Image

```bash
# Set region
gcloud config set run/region us-central1

# Build image
gcloud builds submit --tag gcr.io/YOUR_PROJECT_ID/admin-app

# Deploy to Cloud Run
gcloud run deploy admin-app \
  --image gcr.io/YOUR_PROJECT_ID/admin-app \
  --platform managed \
  --region us-central1 \
  --allow-unauthenticated \
  --add-cloudsql-instances YOUR_CONNECTION_NAME \
  --set-env-vars="APP_ENV=production,APP_DEBUG=false" \
  --set-secrets="APP_KEY=app-key:latest,DB_PASSWORD=db-password:latest" \
  --min-instances 1 \
  --max-instances 10 \
  --memory 512Mi
```

## Post-Deployment Steps

### 1. Run Database Migrations

```bash
# Option A: Using Cloud Shell
gcloud cloud-shell ssh
cd /path/to/your/app
php artisan migrate --force

# Option B: Using Cloud SQL Proxy locally
cloud_sql_proxy -instances=YOUR_CONNECTION_NAME=tcp:3306 &
php artisan migrate --force --env=production
```

### 2. Create Admin User

```bash
php artisan tinker

# In tinker:
\App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('your-secure-password'),
    'role' => 'admin',
    'is_active' => true,
    'email_verified_at' => now()
]);
```

### 3. Set Up Custom Domain (Optional)

```bash
# Map custom domain
gcloud app domain-mappings create www.yourdomain.com

# Follow SSL certificate instructions
```

## Monitoring and Logs

```bash
# View logs
gcloud app logs tail -s default

# For Cloud Run
gcloud run services logs read admin-app --limit=50

# View in Cloud Console
https://console.cloud.google.com/logs
```

## CI/CD with Cloud Build (Optional)

Create `cloudbuild.yaml`:

```yaml
steps:
  # Install composer dependencies
  - name: 'composer'
    args: ['install', '--no-dev', '--optimize-autoloader']
  
  # Install npm dependencies and build
  - name: 'node:18'
    entrypoint: 'npm'
    args: ['ci']
  
  - name: 'node:18'
    entrypoint: 'npm'
    args: ['run', 'build']
  
  # Deploy to App Engine
  - name: 'gcr.io/cloud-builders/gcloud'
    args: ['app', 'deploy', 'app.yaml', '--quiet']

timeout: 1200s
```

## Cost Optimization Tips

1. **Use Cloud SQL f1-micro** for development ($7-10/month)
2. **Set min_instances: 0** in app.yaml for low-traffic periods
3. **Enable Cloud CDN** for static assets
4. **Use Cloud Storage** for uploaded files instead of local storage
5. **Monitor billing alerts** in GCP Console

## Troubleshooting

### App doesn't start
- Check logs: `gcloud app logs tail`
- Verify APP_KEY is set correctly
- Ensure all migrations ran successfully

### Database connection fails
- Verify Cloud SQL instance is running
- Check connection name in app.yaml matches
- Confirm user permissions

### Static assets not loading
- Ensure `npm run build` completed successfully
- Check public/build folder exists
- Verify handlers in app.yaml

### Session issues
- Switch SESSION_DRIVER to 'database'
- Run: `php artisan session:table && php artisan migrate`

## Security Checklist

- [ ] APP_DEBUG=false in production
- [ ] APP_KEY is unique and secure
- [ ] Database password is strong
- [ ] SESSION_SECURE_COOKIE=true
- [ ] All secrets stored in Secret Manager
- [ ] BCRYPT_ROUNDS set appropriately
- [ ] Enable Cloud Armor for DDoS protection
- [ ] Set up Cloud IAM roles properly
- [ ] Enable VPC Service Controls (for high security)

## Useful Commands

```bash
# SSH into App Engine instance
gcloud app instances ssh

# View active services
gcloud app services list

# View versions
gcloud app versions list

# Roll back to previous version
gcloud app versions stop VERSION_ID

# Delete old versions
gcloud app versions delete VERSION_ID
```

## Support

- GCP Documentation: https://cloud.google.com/appengine/docs
- Laravel Deployment: https://laravel.com/docs/deployment
- Cloud SQL Proxy: https://cloud.google.com/sql/docs/mysql/sql-proxy
