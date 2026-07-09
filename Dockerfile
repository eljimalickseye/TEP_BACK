FROM php:8.3-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    sqlite-dev

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_sqlite bcmath

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy existing application directory contents
COPY . /var/www

# Install composer dependencies
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Setup SQLite Database File and Permissions
RUN touch /var/www/database/database.sqlite \
    && chown -R www-data:www-data /var/www

EXPOSE 9000
CMD ["php-fpm"]
