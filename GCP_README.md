# Google Cloud Platform Deployment Files

This directory contains configuration files for deploying the application to Google Cloud Platform (GCP).

## Files Overview

- **app.yaml** - App Engine configuration
- **Dockerfile** - Container definition for Cloud Run
- **cloudbuild.yaml** - Cloud Build CI/CD pipeline
- **deploy.sh** - Quick deployment script
- **gcp-build.sh** - Build script for App Engine
- **gcp-deploy.sh** - Post-deployment script
- **DEPLOYMENT.md** - Complete deployment guide

## Quick Start

### Option 1: App Engine (Simpler, managed)

```bash
# Make script executable
chmod +x deploy.sh

# Deploy
./deploy.sh app-engine
```

### Option 2: Cloud Run (More flexible, containerized)

```bash
# Deploy
./deploy.sh cloud-run
```

## Prerequisites

1. Google Cloud account with billing enabled
2. `gcloud` CLI installed and authenticated
3. Cloud SQL instance created for database
4. Secrets configured in Secret Manager

## Full Documentation

See [DEPLOYMENT.md](./DEPLOYMENT.md) for complete setup instructions.
