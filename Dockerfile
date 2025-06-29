# Dockerfile using official PHP-FPM and installing Nginx
FROM php:8.2-fpm-alpine

# Update package index and install all necessary system dependencies including build tools and PHP extension development libraries
RUN apk update && apk add --no-cache \
    nginx \
    git \
    unzip \
    zip \
    curl \
    autoconf \
    build-base \
    libzip-dev \
    libpng-dev \
    jpeg-dev \
    webp-dev \
    onig-dev \
    libxml2-dev \
    icu-dev \
    gmp-dev \
    sqlite-dev \
    mariadb-client-dev \
    libexif-dev \
    && rm -rf /var/cache/apk/* # Clean up apk cache

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Configure GD extension with JPEG and WebP support
RUN docker-php-ext-configure gd \
    --with-jpeg \
    --with-webp

# Install common PHP extensions required by Laravel
RUN docker-php-ext-install \
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
    gmp \
    && docker-php-ext-enable opcache # Enable opcache here, only once

# Create nginx directories
RUN mkdir -p /run/nginx

# Create a basic nginx configuration for Laravel
RUN echo 'server { \
    listen 80; \
    server_name localhost; \
    root /var/www/html/public; \
    index index.php index.html; \
    \
    location / { \
    try_files $uri $uri/ /index.php?$query_string; \
    } \
    \
    location ~ \.php$ { \
    fastcgi_pass 127.0.0.1:9000; \
    fastcgi_index index.php; \
    fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
    include fastcgi_params; \
    } \
    \
    location ~ /\.ht { \
    deny all; \
    } \
    }' > /etc/nginx/http.d/default.conf

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
