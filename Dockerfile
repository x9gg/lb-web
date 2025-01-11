FROM composer:latest AS composer

WORKDIR /app
COPY composer.* ./
    
RUN composer install --optimize-autoloader --no-dev --no-scripts --ignore-platform-req=ext-sockets

FROM ghcr.io/roadrunner-server/roadrunner:2024 AS roadrunner
FROM php:8.4-alpine AS production

WORKDIR /app
    
COPY --from=roadrunner /usr/bin/rr /usr/local/bin/rr
COPY .rr.prod.yaml /etc/roadrunner/config.yaml

COPY --from=composer /app/ ./
COPY src/ ./src/
COPY LICENSE ./
COPY psr-worker.php ./
    
EXPOSE 8080    
CMD ["rr", "serve", "-c", "/etc/roadrunner/config.yaml"]
