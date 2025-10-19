#!/bin/bash

# Google Cloud Build script for Laravel deployment
# This runs during the build process

echo "Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Installing Node dependencies..."
npm ci --production=false

echo "Building frontend assets..."
npm run build

echo "Optimizing Laravel..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Build completed successfully!"
