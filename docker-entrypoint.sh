#!/bin/bash

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL to be ready..."
while ! nc -z symatech-db 5432; do
  sleep 1
done
echo "PostgreSQL is ready!"

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Run database seeders
echo "Running database seeders..."
php artisan db:seed --force

# Start Apache
echo "Starting Apache..."
apache2-foreground
