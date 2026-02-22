#!/bin/bash

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL to be ready..."
DB_HOST=${DB_HOST:-symatech-db}
echo "Trying to connect to database host: $DB_HOST"

# Try common database hosts
for host in "$DB_HOST" "symatech-db" "pgsql" "postgres" "localhost"; do
    echo "Attempting connection to $host:5432..."
    if nc -z $host 5432 2>/dev/null; then
        echo "Successfully connected to PostgreSQL at $host:5432"
        break
    fi
    if [ "$host" = "localhost" ]; then
        echo "Could not connect to any database host, exiting..."
        exit 1
    fi
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
