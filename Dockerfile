FROM php:8.4-cli

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    zip \
    unzip

# Install PostgreSQL extension
RUN docker-php-ext-install pdo pdo_pgsql

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy application files
COPY . /app

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Copy environment file if it doesn't exist
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Generate application key if not set (will be overridden by env var if set)
RUN php artisan key:generate --ansi || true

# Create necessary directories and set permissions
RUN mkdir -p /app/storage/framework/cache \
    /app/storage/framework/sessions \
    /app/storage/framework/views \
    /app/storage/logs \
    /app/bootstrap/cache \
    && chown -R www-data:www-data /app \
    && chmod -R 775 /app/storage \
    && chmod -R 775 /app/bootstrap/cache

# Expose port
EXPOSE 8000

# Start the application
CMD php artisan serve --host=0.0.0.0 --port=8000
