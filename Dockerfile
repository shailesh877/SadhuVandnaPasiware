FROM php:8.2-apache

# Install system dependencies and PHP extensions required for MySQL, GD, cURL, Zip, etc.
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libonig-dev \
    libcurl4-openssl-dev \
    zip \
    unzip \
    curl \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) mysqli pdo pdo_mysql gd zip mbstring curl exif \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure PHP settings (upload limits, execution time, memory)
RUN echo "upload_max_filesize = 128M" > /usr/local/etc/php/conf.d/custom.ini \
    && echo "post_max_size = 128M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "max_input_time = 300" >> /usr/local/etc/php/conf.d/custom.ini \
    && echo "date.timezone = Asia/Kolkata" >> /usr/local/etc/php/conf.d/custom.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . /var/www/html/

# Ensure proper permissions for files and upload directories
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && if [ -d "/var/www/html/Api/uploads" ]; then chmod -R 777 /var/www/html/Api/uploads; fi

# Expose port 80 for Coolify
EXPOSE 80

CMD ["apache2-foreground"]
