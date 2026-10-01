FROM php:8.2-apache

# Enable Apache mod_rewrite & headers
RUN a2enmod rewrite headers

# Install cURL and required dependencies
RUN apt-get update && apt-get install -y \
    libcurl4-openssl-dev \
    && docker-php-ext-install curl \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy project files
COPY . /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html

# Port
EXPOSE 80

CMD ["apache2-foreground"]
