FROM composer:2@sha256:af98f42dfff7c68ba8d53c2164fd9fde1087b7d449514baa38c418b1f6bc4bac AS composer
FROM php:8.3-cli-bookworm@sha256:c24b55afdf860c874b9ec6267ced132ff52de97d308ab08e6cf41e8596e509f7

RUN apt-get update \
    && apt-get install -y --no-install-recommends libonig-dev unzip ca-certificates \
    && docker-php-ext-install -j"$(nproc)" mbstring sockets pcntl \
    && rm -rf /var/lib/apt/lists/*
COPY --from=composer /usr/bin/composer /usr/local/bin/composer
RUN pecl install xdebug-3.5.3 \
    && docker-php-ext-enable xdebug
COPY docker/php/xdebug.ini /usr/local/etc/php/conf.d/99-xdebug.ini
WORKDIR /app
CMD ["sleep", "infinity"]
