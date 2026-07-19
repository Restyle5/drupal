# Drupal 11 requires PHP 8.3+
FROM drupal:11-php8.3-apache

# Add Composer, since most real Drupal projects/tests expect it
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Optional: extra PHP extensions some Drupal modules/tests need
RUN docker-php-ext-install opcache

# mysql client — required by `drush sqlq` and other drush DB commands
RUN apt-get update && apt-get install -y --no-install-recommends default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Disable SSL for CLI mysql client — our MariaDB container has no SSL certs configured,
# and newer mysql clients default to requiring SSL, causing "TLS/SSL error" on connect.
RUN mkdir -p /etc/mysql/conf.d && \
    printf '[client]\nssl-mode=DISABLED\n' > /etc/mysql/conf.d/no-ssl.cnf

WORKDIR /opt/drupal