FROM php:8.4-apache

ARG APP_PATH=apps/frontend

ENV APP_PATH=${APP_PATH}
ENV APACHE_DOCUMENT_ROOT=/var/www/html/${APP_PATH}/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && sed -ri -e 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf /etc/apache2/sites-available/*.conf \
    && a2enmod rewrite headers

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

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    intl \
    zip \
    gd \
    opcache \
    bcmath \
    xsl

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /var/www/html
COPY . .

WORKDIR /var/www/html/${APP_PATH}

RUN test -f .env || cp .env.docker.example .env || true

ARG INSTALL_DEV_DEPS=1
RUN if [ "$INSTALL_DEV_DEPS" = "1" ]; then composer install --no-interaction --prefer-dist; \
    else composer install --no-interaction --prefer-dist --no-dev --optimize-autoloader; fi

RUN mkdir -p var && chown -R www-data:www-data var && chmod -R 775 var

EXPOSE 80
