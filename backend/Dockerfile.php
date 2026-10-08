FROM php:8.2-apache

# Install PHP extensions required for the app
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
  && docker-php-ext-install mysqli \
  && pecl install redis \
  && docker-php-ext-enable redis

# Enable Apache mod_rewrite (useful for API routing)
RUN a2enmod rewrite
