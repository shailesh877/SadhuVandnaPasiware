FROM php:8.2-apache

WORKDIR /var/www/html

COPY . /var/www/html

RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
