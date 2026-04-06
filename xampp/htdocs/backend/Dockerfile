FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Install PHP extensions and dependencies
RUN apt-get update && apt-get install -y \
    zip \
    unzip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql gd zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy custom Apache config to allow .htaccess overrides and set DocumentRoot
RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf \
    && sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && echo '<Directory /var/www/html/public>\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' > /etc/apache2/conf-available/allow-override.conf \
&& a2enconf allow-override

# Force PHP logs to stderr to be visible in `docker logs`
RUN echo "log_errors = On\nerror_log = /dev/stderr" > /usr/local/etc/php/conf.d/zz-custom-logging.ini

# Set working directory
WORKDIR /var/www/html

# Copy composer files and install dependencies first to leverage Docker layer caching
COPY composer.json composer.lock ./
RUN composer install --no-scripts --no-autoloader --prefer-dist

# Copy the rest of the application source code
COPY . .

# Set permissions for uploads directory
RUN mkdir -p /var/www/html/uploads && chmod -R 777 /var/www/html/uploads

# Re-generate autoloader after all files are in place
RUN composer dump-autoload --optimize

EXPOSE 80
