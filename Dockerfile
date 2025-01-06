FROM composer:latest AS composer
FROM ghcr.io/roadrunner-server/roadrunner:2024 AS roadrunner
FROM php:zts

# Install system dependencies
#RUN apk add --no-cache \
#    unzip \
#    curl

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions sockets

# Set working directory
WORKDIR /worker

# Copy application files
COPY ./ /worker

# Install Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN composer --version

# Install RoadRunner CLI globally
COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

RUN rr --version

# Install dependencies
RUN composer install --no-dev --no-scripts --no-autoloader

# Generate autoloader
RUN composer dump-autoload --no-dev --optimize

# RoadRunner configuration
COPY .rr.prod.yaml /etc/roadrunner/config.yaml

# Expose port
EXPOSE 8080

# Run RoadRunner
CMD ["rr", "serve", "-c", "/etc/roadrunner/config.yaml"]
