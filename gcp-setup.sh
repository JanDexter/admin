#!/bin/bash

# Google Cloud First-Time Setup Script
# Run this after deploying to GCloud for the first time
# Usage: ./gcp-setup.sh [--with-sample-data]

set -e  # Exit on error

echo "=========================================="
echo "Google Cloud Laravel Setup Script"
echo "=========================================="
echo ""

# Parse arguments
WITH_SAMPLE_DATA=false
if [[ "$1" == "--with-sample-data" ]]; then
    WITH_SAMPLE_DATA=true
    echo "Sample data will be seeded"
fi

echo "Step 1: Running database migrations..."
php artisan migrate --force

echo ""
echo "Step 2: Seeding initial data (Admin user and Spaces)..."
php artisan db:seed --force

if [ "$WITH_SAMPLE_DATA" = true ]; then
    echo ""
    echo "Step 3: Seeding sample data (Customers, Reservations, Transactions)..."
    php artisan db:seed --class=SampleDataSeeder --force
fi

echo ""
echo "Step 4: Clearing and caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo ""
echo "Step 5: Setting proper permissions..."
chmod -R 775 storage bootstrap/cache

echo ""
echo "Step 6: Creating storage link..."
php artisan storage:link || echo "Storage link already exists or not needed"

echo ""
echo "=========================================="
echo "Setup completed successfully!"
echo "=========================================="
echo ""
echo "Next steps:"
echo "1. Access your application URL"
echo "2. Login with admin credentials"
echo "3. Configure space types and pricing in Space Management"
echo ""

if [ "$WITH_SAMPLE_DATA" = true ]; then
    echo "Sample data has been seeded:"
    echo "- 8 sample customers (individual and company)"
    echo "- 16 sample reservations (various statuses)"
    echo "- Transaction logs for all payments/refunds"
    echo ""
fi

echo "Admin credentials are defined in AdminSeeder"
echo "Check database/seeders/AdminSeeder.php for details"
echo ""
