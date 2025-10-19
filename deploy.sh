#!/bin/bash

# Quick deployment script for GCP
# Usage: ./deploy.sh [app-engine|cloud-run]

set -e

DEPLOYMENT_TYPE=${1:-app-engine}

echo "🚀 Starting deployment to GCP ($DEPLOYMENT_TYPE)..."

# Build frontend assets
echo "📦 Building frontend assets..."
npm ci
npm run build

# Install composer dependencies
echo "📦 Installing PHP dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

if [ "$DEPLOYMENT_TYPE" == "cloud-run" ]; then
    echo "🐳 Deploying to Cloud Run..."
    
    PROJECT_ID=$(gcloud config get-value project)
    
    # Build and push Docker image
    gcloud builds submit --tag gcr.io/$PROJECT_ID/admin-app
    
    # Deploy to Cloud Run
    gcloud run deploy admin-app \
        --image gcr.io/$PROJECT_ID/admin-app \
        --platform managed \
        --region us-central1 \
        --allow-unauthenticated \
        --min-instances 1 \
        --max-instances 10 \
        --memory 512Mi
    
    echo "✅ Deployed to Cloud Run!"
    gcloud run services describe admin-app --region us-central1 --format="value(status.url)"
    
else
    echo "🚂 Deploying to App Engine..."
    
    # Deploy to App Engine
    gcloud app deploy app.yaml --quiet
    
    echo "✅ Deployed to App Engine!"
    gcloud app browse
fi

echo ""
echo "📋 Post-deployment checklist:"
echo "  1. Run database migrations"
echo "  2. Create admin user if needed"
echo "  3. Test the application"
echo "  4. Check logs for any errors"
