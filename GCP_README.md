# Google Cloud Platform Deployment Files

This directory contains configuration files for deploying the application to Google Cloud Platform (GCP).

## Deployment Options

### 1. App Engine (Managed, Serverless)
- **Cost**: ~$50-100/month
- **Best for**: Zero server management, auto-scaling
- **Setup**: Simple, fully managed
- **Guide**: See `DEPLOYMENT.md`

### 2. Cloud Run (Containerized, Serverless)
- **Cost**: ~$20-50/month
- **Best for**: Container-based deployments, flexible scaling
- **Setup**: Docker-based
- **Guide**: See `DEPLOYMENT.md`

### 3. Compute Engine E2 Micro (VM-based)
- **Cost**: ~$7-10/month (FREE tier eligible!)
- **Best for**: Budget-conscious, full control, small-medium traffic
- **Setup**: Traditional VM deployment
- **Guide**: See `GCE_E2_DEPLOYMENT.md` ⭐ **RECOMMENDED FOR COST SAVINGS**

## Files Overview

- **app.yaml** - App Engine configuration
- **Dockerfile** - Container definition for Cloud Run
- **cloudbuild.yaml** - Cloud Build CI/CD pipeline
- **deploy.sh** - Quick deployment script
- **gcp-build.sh** - Build script for App Engine
- **gcp-deploy.sh** - Post-deployment script (for updates)
- **gcp-setup.sh** - First-time setup script (with data seeding) for App Engine
- **gce-e2-setup.sh** - Complete setup script for E2 micro instance
- **DEPLOYMENT.md** - Complete App Engine/Cloud Run deployment guide
- **GCE_E2_DEPLOYMENT.md** - Complete E2 micro deployment guide

## Quick Start

### Option 1: App Engine (Simpler, managed)

```bash
# Make scripts executable
chmod +x deploy.sh gcp-setup.sh

# Deploy application
./deploy.sh app-engine

# First-time setup (after deployment)
gcloud app instances ssh [INSTANCE_ID] --service=default --version=[VERSION]
cd /workspace
./gcp-setup.sh

# OR with sample data (for testing/demo)
./gcp-setup.sh --with-sample-data
```

### Option 2: Cloud Run (More flexible, containerized)

```bash
# Deploy
./deploy.sh cloud-run
```

### Option 3: Compute Engine E2 Micro (Most cost-effective) ⭐

```bash
# 1. Create instance and upload files
gcloud compute instances create admin-app \
  --zone=us-central1-a \
  --machine-type=e2-micro \
  --boot-disk-size=20GB \
  --image-family=ubuntu-2204-lts \
  --image-project=ubuntu-os-cloud \
  --tags=http-server,https-server

# 2. Upload application and setup script
tar -czf admin-app.tar.gz --exclude='node_modules' --exclude='vendor' .
gcloud compute scp admin-app.tar.gz admin-app:~ --zone=us-central1-a
gcloud compute scp gce-e2-setup.sh admin-app:~ --zone=us-central1-a

# 3. SSH and run setup
gcloud compute ssh admin-app --zone=us-central1-a
tar -xzf admin-app.tar.gz
chmod +x gce-e2-setup.sh
sudo ./gce-e2-setup.sh
```

**See `GCE_E2_DEPLOYMENT.md` for complete E2 micro setup guide.**

## Setup Scripts

### gcp-setup.sh (First-Time Setup)

Run this script **after first deployment** to:
- Run database migrations
- Seed admin user and initial spaces
- Optionally seed sample data (customers, reservations, transactions)
- Cache configuration
- Set proper permissions

**Usage:**
```bash
# Basic setup (production)
./gcp-setup.sh

# Setup with sample data (testing/demo)
./gcp-setup.sh --with-sample-data
```

### gcp-deploy.sh (Regular Deployments)

Automatically runs after each deployment to:
- Run database migrations
- Clear and cache configuration
- Set permissions

This does NOT re-seed data.

## Prerequisites

1. Google Cloud account with billing enabled
2. `gcloud` CLI installed and authenticated
3. Cloud SQL instance created for database
4. Secrets configured in Secret Manager

## Sample Data

When using `--with-sample-data` flag, the setup will create:
- 8 sample customers (4 individual, 4 company profiles)
- 16 sample reservations (active, completed, cancelled, pending)
- Transaction logs for all payments, refunds, and cancellations

**Note:** Only use sample data for testing/demo environments, not production!

## Full Documentation

See [DEPLOYMENT.md](./DEPLOYMENT.md) for complete setup instructions.

