FROM php:8.3-fpm-alpine

# Build-time argument for enabling Opcache (default: 0, means opcache is not installed)
ARG INSTALL_OPCACHE=0

RUN apk update
RUN apk upgrade
RUN apk add bash
RUN pecl -q channel-update pecl.php.net

RUN apk add --no-cache \
		$PHPIZE_DEPS \
		openssl-dev

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN docker-php-ext-install pcntl pdo_mysql

# Install dependencies for GD and install GD with support for jpeg, png webp and freetype
# Info about installing GD in PHP https://www.php.net/manual/en/image.installation.php
RUN apk add libjpeg-turbo-dev \
        libpng-dev \
        libwebp-dev \
        freetype-dev \
        libzip-dev \
        zip \
        c-ares-dev

RUN docker-php-ext-install bcmath

# As of PHP 7.4 we don't need to add --with-png
RUN docker-php-ext-configure gd --with-jpeg --with-webp --with-freetype
RUN docker-php-ext-install gd

# Conditionally install Opcache based on the INSTALL_OPCACHE argument
RUN if [ "$INSTALL_OPCACHE" = "1" ] ; then \
    docker-php-ext-install opcache; \
    fi

RUN apk add shadow && usermod -u 1000 www-data && groupmod -g 1000 www-data

RUN apk add supervisor
RUN mkdir -p /etc/supervisor/logs && chown -R www-data:www-data /etc/supervisor/logs

ADD opcache.ini "$PHP_INI_DIR/conf.d/opcache.ini"

# Set PHP configurations
RUN echo "max_execution_time = 60" >> "$PHP_INI_DIR/conf.d/custom.ini"
RUN echo "memory_limit = 512M" >> "$PHP_INI_DIR/conf.d/custom.ini"
