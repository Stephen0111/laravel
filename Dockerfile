# Dockerfile using official PHP-FPM and installing Nginx
FROM php:8.2-fpm-alpine # Or php:8.3-fpm-alpine

# Install system dependencies, including Nginx and common PHP extensions
RUN apk add --no-cache \
    nginx \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libonig-dev \
    libxml2-dev \
    curl-dev \
    icu-dev \
    gmp-dev \ # for bcmath, if not covered by default
&& rm -rf /var/cache/apk/* # Clean up apk cache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Install common PHP extensions required by Laravel and packages
RUN docker-php-ext-install -j$(nproc) \
    pdo_sqlite \
    pdo_mysql \ # Include if you might ever switch to MySQL/MariaDB
opcache \
    zip \
    gd \
    mbstring \
    xml \
    curl \
    intl \
    exif \
    bcmath \
    sockets \
    gmp # For bcmath, sometimes needed explicitly
# Add any other specific extensions your app needs that aren't listed
# e.g., pcntl for queue workers, if needed
&& docker-php-ext-enable opcache # Enable opcache explicitly

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
    && chmod -R 775 database # Ensure database folder is writable

# Expose Nginx port
EXPOSE 80

# Start Nginx and PHP-FPM (this CMD is for the official php-fpm images)
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]
