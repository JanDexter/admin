# Google Cloud Platform Deployment Files

This directory contains configuration files for deploying the application to Google Cloud Platform (GCP).

## Files Overview

- **app.yaml** - App Engine configuration
- **Dockerfile** - Container definition for Cloud Run
- **cloudbuild.yaml** - Cloud Build CI/CD pipeline
- **deploy.sh** - Quick deployment script
- **gcp-build.sh** - Build script for App Engine
- **gcp-deploy.sh** - Post-deployment script (for updates)
- **gcp-setup.sh** - First-time setup script (with data seeding)
- **DEPLOYMENT.md** - Complete deployment guide

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

