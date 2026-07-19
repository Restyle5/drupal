# Drupal 11 requires PHP 8.3+
FROM drupal:11-php8.3-apache

# Add Composer, since most real Drupal projects/tests expect it
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Optional: extra PHP extensions some Drupal modules/tests need
RUN docker-php-ext-install opcache

WORKDIR /opt/drupal