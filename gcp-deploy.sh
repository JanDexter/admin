#!/bin/bash

# Post-deployment script
# This runs after the application is deployed

echo "Running database migrations..."
php artisan migrate --force

echo "Clearing and caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Setting proper permissions..."
chmod -R 775 storage bootstrap/cache

echo "Deployment completed successfully!"
