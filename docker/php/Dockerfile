FROM php:8.3-fpm

USER root

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    curl \
    libpq-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    --no-install-recommends && rm -rf /var/lib/apt/lists/* \
  && curl -fsSL https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    -o /usr/local/bin/install-php-extensions \
  && chmod +x /usr/local/bin/install-php-extensions

RUN install-php-extensions pdo_pgsql gd zip

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

RUN git config --global --add safe.directory /app

RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && apt-get install -y nodejs \
  && npm install -g npm@11.4.2

# Copy custom PHP configuration
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

# Create an entrypoint script to handle initialization
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]