# Use PHP 8.3 Apache image
FROM php:8.3-apache-bullseye

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    unzip \
    curl \
    git \
    supervisor \
    cron \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libmcrypt-dev \
    libgd-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        pgsql \
        zip \
        bcmath \
        gd \
        opcache \
        exif \
    && rm -rf /var/lib/apt/lists/*

# Install Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html/

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set permissions for Laravel directories
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/public

# Copy Apache configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Enable Apache modules
RUN a2enmod rewrite headers

# Set proper permissions and create storage link
RUN php artisan storage:link || true
RUN chown -R www-data:www-data /var/www/html/storage \
    && chmod -R 775 /var/www/html/storage \
    && chown -R www-data:www-data /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/bootstrap/cache \
    && chmod -R 755 /var/www/html/public

# Create startup script
RUN echo '#!/bin/bash' > /usr/local/bin/startup.sh \
    && echo 'set -e' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'echo "Starting Laravel application setup..."' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo '# Create .env file if it does not exist' >> /usr/local/bin/startup.sh \
    && echo 'if [ ! -f "/var/www/html/.env" ]; then' >> /usr/local/bin/startup.sh \
    && echo '    echo "Creating .env file from environment variables..."' >> /usr/local/bin/startup.sh \
    && echo '    cp /var/www/html/.env.example /var/www/html/.env' >> /usr/local/bin/startup.sh \
    && echo 'fi' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'if [ -z "$APP_KEY" ]; then' >> /usr/local/bin/startup.sh \
    && echo '    echo "Generating application key..."' >> /usr/local/bin/startup.sh \
    && echo '    php artisan key:generate --force' >> /usr/local/bin/startup.sh \
    && echo 'fi' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'echo "Clearing caches..."' >> /usr/local/bin/startup.sh \
    && echo 'php artisan config:clear' >> /usr/local/bin/startup.sh \
    && echo 'php artisan route:clear' >> /usr/local/bin/startup.sh \
    && echo 'php artisan view:clear' >> /usr/local/bin/startup.sh \
    && echo 'php artisan cache:clear' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'if [ "$APP_ENV" = "production" ]; then' >> /usr/local/bin/startup.sh \
    && echo '    echo "Optimizing for production..."' >> /usr/local/bin/startup.sh \
    && echo '    php artisan config:cache' >> /usr/local/bin/startup.sh \
    && echo '    php artisan route:cache' >> /usr/local/bin/startup.sh \
    && echo '    php artisan view:cache' >> /usr/local/bin/startup.sh \
    && echo 'fi' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'if [ "$APP_ENV" = "production" ]; then' >> /usr/local/bin/startup.sh \
    && echo '    echo "Waiting for database to be ready..."' >> /usr/local/bin/startup.sh \
    && echo '    for i in {1..30}; do' >> /usr/local/bin/startup.sh \
    && echo '        if pg_isready -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USERNAME" >/dev/null 2>&1; then' >> /usr/local/bin/startup.sh \
    && echo '            echo "Database is ready!"' >> /usr/local/bin/startup.sh \
    && echo '            break' >> /usr/local/bin/startup.sh \
    && echo '        fi' >> /usr/local/bin/startup.sh \
    && echo '        echo "Waiting for database... ($i/30)"' >> /usr/local/bin/startup.sh \
    && echo '        sleep 2' >> /usr/local/bin/startup.sh \
    && echo '    done' >> /usr/local/bin/startup.sh \
    && echo 'fi' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'echo "Running database migrations..."' >> /usr/local/bin/startup.sh \
    && echo 'php artisan migrate --force' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'if [ "$RUN_SEEDERS" = "true" ]; then' >> /usr/local/bin/startup.sh \
    && echo '    echo "Running database seeders..."' >> /usr/local/bin/startup.sh \
    && echo '    php artisan db:seed --force' >> /usr/local/bin/startup.sh \
    && echo 'fi' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'echo "Laravel setup complete. Starting Apache..."' >> /usr/local/bin/startup.sh \
    && echo 'exec apache2-foreground' >> /usr/local/bin/startup.sh \
    && chmod +x /usr/local/bin/startup.sh

# Expose port 80
EXPOSE 80

# Default command
CMD ["/usr/local/bin/startup.sh"]
