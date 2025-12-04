FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libzip-dev \
    zip \
    unzip \
    default-mysql-client \
    && docker-php-ext-install pdo pdo_mysql zip

# Enable mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy composer files first to leverage caching
COPY composer.json composer.lock ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-autoloader

# Copy application files
COPY . .

# Dump autoload
RUN composer dump-autoload --optimize --no-dev

# Create logs directory if it doesn't exist
RUN mkdir -p logs \
    && mkdir -p public/uploads

# Copy and make init script executable
COPY docker/init-db.sh /usr/local/bin/init-db.sh
RUN sed -i 's/\r$//' /usr/local/bin/init-db.sh && chmod +x /usr/local/bin/init-db.sh

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html \
    && chmod -R 775 /var/www/html/public/uploads \
    && chmod -R 775 /var/www/html/logs

# Expose port 80
EXPOSE 80

# Use init script as entrypoint
CMD ["bash", "/usr/local/bin/init-db.sh"]
