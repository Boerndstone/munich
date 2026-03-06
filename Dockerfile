# munichclimbs – PHP 8.4 + Apache for Symfony (lock file requires 8.4)
FROM php:8.4-apache

# Apache: use public/ as document root, enable mod_rewrite, and allow .htaccess
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf /etc/apache2/sites-available/*.conf \
    && a2enmod rewrite headers

# System packages needed for PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
    libicu-dev \
    libpng-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libxslt1-dev \
    && rm -rf /var/lib/apt/lists/*

# PHP extensions required by Symfony / your app (incl. xsl for lorenzo/pinky)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    intl \
    zip \
    gd \
    opcache \
    bcmath \
    xsl

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

# App lives here
WORKDIR /var/www/html

# Copy app (excluding what’s in .dockerignore)
COPY . .

# Minimal .env so post-install scripts (e.g. cache:clear) can run; .env is in .dockerignore
RUN echo "APP_ENV=dev" > .env \
    && echo "APP_SECRET=buildtime" >> .env \
    && echo 'DATABASE_URL="mysql://build:build@localhost:3306/build?serverVersion=8.0"' >> .env

# Install PHP dependencies (no dev in prod stage; override when building for dev)
ARG INSTALL_DEV_DEPS=1
RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then composer install --no-interaction --prefer-dist; \
    else composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader; fi

# Ensure var/ is writable by Apache
RUN chown -R www-data:www-data var/ && chmod -R 775 var/

# Optional: run assets build (Encore). Uncomment if you want built assets in the image.
# RUN npm ci && npm run build

EXPOSE 80
