FROM php:8.3-cli-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    zip \
    unzip \
    curl \
    libzip-dev \
    && docker-php-ext-install zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy application
COPY . .

# Create non-root user
RUN adduser -D -u 1000 appuser && chown -R appuser:appuser /app
USER appuser

# Default command
CMD ["php", "bin/app.php", "get", "/"]