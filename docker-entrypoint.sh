#!/bin/bash

# Change to Laravel directory
cd /var/www/html

# Load Laravel environment variables
if [ -f .env ]; then
    export $(cat .env | grep -v '^#' | xargs)
    echo "Loaded environment variables from .env file"
else
    echo "Warning: .env file not found, using default environment variables"
fi

# Debug: Show database configuration
echo "Database configuration:"
echo "  DB_CONNECTION: ${DB_CONNECTION:-not set}"
echo "  DB_HOST: ${DB_HOST:-not set}"
echo "  DB_PORT: ${DB_PORT:-not set}"
echo "  DB_DATABASE: ${DB_DATABASE:-not set}"

# Wait for PostgreSQL to be ready
echo "Waiting for PostgreSQL to be ready..."
DB_HOST=${DB_HOST:-symatech-db}
echo "Trying to connect to database host: $DB_HOST"

# For Render, try the Render database service name first
RENDER_DB_HOST=${DATABASE_URL:-${DB_HOST}}
echo "Render database host: $RENDER_DB_HOST"

# Try the actual Render database hostname
ACTUAL_RENDER_HOST="dpg-d6dcgdvgi27c738fu6ag-a"
echo "Trying actual Render database host: $ACTUAL_RENDER_HOST"

# Try common database hosts
for host in "$ACTUAL_RENDER_HOST" "$RENDER_DB_HOST" "$DB_HOST" "symatech-db" "pgsql" "postgres" "localhost"; do
    echo "Attempting connection to $host:5432..."
    if nc -z $host 5432 2>/dev/null; then
        echo "Successfully connected to PostgreSQL at $host:5432"
        # Update DB_HOST for Laravel to use the working host
        export DB_HOST=$host
        echo "Updated DB_HOST to: $DB_HOST"
        
        # Update .env file with correct database host
        if [ -f .env ]; then
            sed -i "s/DB_HOST=.*/DB_HOST=$host/" .env
            sed -i "s/APP_DEBUG=.*/APP_DEBUG=true/" .env
            sed -i "s/DB_USERNAME=.*/DB_USERNAME=symatech_user/" .env
            sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=oLEeBggKN3fQi8YqXtPFWGPgEANsO3sm/" .env
            echo "Updated .env file with DB_HOST=$host, correct credentials and enabled debug"
            
            # Clear Laravel cache to reload configuration
            php artisan config:clear
            php artisan cache:clear
            echo "Cleared Laravel configuration cache"
        fi
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
