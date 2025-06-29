# Dockerfile using official PHP-FPM and installing Nginx
FROM php:8.2-fpm-alpine

# Install essential system dependencies and common PHP extension libs
RUN apk add --no-cache \
    nginx \
    git \
    unzip \
    zip \
    curl \
    build-base \
    autoconf \
    libtool \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    webp-dev \
    onig-dev \
    libxml2-dev \
    icu-dev \
    gmp-dev \
    # Removed: mariadb-client-dev, libexif-dev for debugging exit code 3
    && rm -rf /var/cache/apk/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install common PHP extensions required by Laravel and packages
RUN docker-php-ext-install -j$(nproc) \
    pdo_sqlite \
    # pdo_mysql \ # Temporarily removed due to mariadb-client-dev removal
    opcache \
    zip \
    gd \
    mbstring \
    xml \
    intl \
    # exif \ # Temporarily removed due to libexif-dev removal
    bcmath \
    sockets \
    && docker-php-ext-enable opcache

# Configure Nginx (assuming you have .docker/nginx/default.conf)
COPY .docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Set the working directory
WORKDIR /var/www/html

# Copy composer files first to optimize Docker layer caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --prefer-dist

# Copy application files into the container
COPY . .

# Set proper permissions for Laravel
RUN chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chmod -R 775 database

# Expose Nginx port
EXPOSE 80

# Start Nginx and PHP-FPM (this CMD is for the official php-fpm images)
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]
