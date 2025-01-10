FROM composer:latest AS composer
FROM ghcr.io/roadrunner-server/roadrunner:2024 AS roadrunner
FROM php:8.4.2-zts

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions sockets

# Set working directory
WORKDIR /worker

# Copy application files
COPY ./ /worker

# Install RoadRunner CLI globally
COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr

RUN rr --version

# RoadRunner configuration
COPY .rr.base.yaml /etc/roadrunner/.rr.base.yaml
COPY .rr.prod.yaml /etc/roadrunner/config.yaml

EXPOSE 8080

# Run RoadRunner
CMD ["rr", "serve", "-c", "/etc/roadrunner/config.yaml"]
