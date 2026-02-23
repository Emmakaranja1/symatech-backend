# Use PHP 8.3 with Apache
FROM php:8.3-apache-bullseye

# Set working directory
WORKDIR /var/www/html

# Install system dependencies + PostgreSQL client (for pg_isready)
RUN apt-get update && apt-get install -y \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    unzip \
    curl \
    git \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libgd-dev \
    postgresql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        pgsql \
        zip \
        bcmath \
        gd \
        opcache \
        exif \
    && rm -rf /var/lib/apt/lists/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . /var/www/html/

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Set correct permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/bootstrap/cache

# Configure Apache to use Laravel public folder
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-available/000-default.conf

# Enable Apache modules
RUN a2enmod rewrite headers

# Create startup script
RUN echo '#!/bin/bash' > /usr/local/bin/startup.sh \
    && echo 'set -e' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'echo "Starting Laravel setup..."' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'echo "Waiting for PostgreSQL..."' >> /usr/local/bin/startup.sh \
    && echo 'until pg_isready -h "$DB_HOST" -p "${DB_PORT:-5432}" -U "$DB_USERNAME"; do' >> /usr/local/bin/startup.sh \
    && echo '  echo "Database not ready, sleeping..."' >> /usr/local/bin/startup.sh \
    && echo '  sleep 2' >> /usr/local/bin/startup.sh \
    && echo 'done' >> /usr/local/bin/startup.sh \
    && echo 'echo "Database ready!"' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'if [ -z "$APP_KEY" ]; then' >> /usr/local/bin/startup.sh \
    && echo '  php artisan key:generate --force' >> /usr/local/bin/startup.sh \
    && echo 'fi' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'php artisan config:clear' >> /usr/local/bin/startup.sh \
    && echo 'php artisan route:clear' >> /usr/local/bin/startup.sh \
    && echo 'php artisan view:clear' >> /usr/local/bin/startup.sh \
    && echo 'php artisan cache:clear' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'if [ "$APP_ENV" = "production" ]; then' >> /usr/local/bin/startup.sh \
    && echo '  php artisan config:cache' >> /usr/local/bin/startup.sh \
    && echo '  php artisan route:cache' >> /usr/local/bin/startup.sh \
    && echo '  php artisan view:cache' >> /usr/local/bin/startup.sh \
    && echo 'fi' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'php artisan migrate --force' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'if [ "$RUN_SEEDERS" = "true" ]; then' >> /usr/local/bin/startup.sh \
    && echo '  php artisan db:seed --force' >> /usr/local/bin/startup.sh \
    && echo 'fi' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'php artisan storage:link || true' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'chown -R www-data:www-data storage bootstrap/cache' >> /usr/local/bin/startup.sh \
    && echo 'chmod -R 775 storage bootstrap/cache' >> /usr/local/bin/startup.sh \
    && echo '' >> /usr/local/bin/startup.sh \
    && echo 'echo "Laravel ready. Starting Apache..."' >> /usr/local/bin/startup.sh \
    && echo 'exec apache2-foreground' >> /usr/local/bin/startup.sh \
    && chmod +x /usr/local/bin/startup.sh

# Expose port
EXPOSE 80

# Start container
CMD ["/usr/local/bin/startup.sh"]
