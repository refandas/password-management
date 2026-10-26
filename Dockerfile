# Password manager on PHP 8.5.12 + CodeIgniter 4 (ADR-0001).
FROM php:8.5.12-apache

# intl and mbstring are mandatory for CI4; MySQLi drives the MySQL 8.0 service.
RUN apt-get update && apt-get install -y --no-install-recommends libicu-dev libonig-dev unzip \
    && docker-php-ext-install intl mbstring mysqli pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Composer pinned for reproducible builds (see docs/research/2026-10-22).
COPY --from=composer:2.10.3 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/password-manager

# Dependencies first, so code changes don't bust this layer.
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --prefer-dist

COPY . .
RUN cp env .env

# The framework's public directory is the docroot (spec #1).
ENV APACHE_DOCUMENT_ROOT=/var/www/password-manager/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf \
    && a2enmod rewrite \
    && chown -R www-data:www-data writable

EXPOSE 80
