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
    # NEW: Dependencies for pdo_mysql and exif
    mariadb-client-dev \
    libexif-dev \
    # Clean up apk cache
    && rm -rf /var/cache/apk/*

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install common PHP extensions required by Laravel and packages
RUN docker-php-ext-install -j$(nproc) \
    pdo_sqlite \
    pdo_mysql \
    opcache \
    zip \
    gd \
    mbstring \
    xml \
    intl \
    exif \
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

# Start Nginx and PHP-FPM
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]
