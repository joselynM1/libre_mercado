FROM php:8.2-apache

RUN docker-php-ext-install pdo pdo_mysql

COPY php-errors.ini /usr/local/etc/php/conf.d/zz-errors.ini
COPY src/ /var/www/html/

RUN chown -R www-data:www-data /var/www/html
