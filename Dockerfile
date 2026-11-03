# Talo Aulas em produção: FrankenPHP (Caddy com PHP embutido), PHP 8.4 e as extensões que o
# sistema usa. Etapas:
#   assets   → o CSS e o JS do Vite
#   base     → PHP com as extensões e o Composer
#   teste    → com as dependências de desenvolvimento, para o CI rodar o Pest (inclusive no MySQL)
#   producao → sem dependências de desenvolvimento, como usuário sem privilégio

FROM node:24-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci --ignore-scripts --no-audit --no-fund
COPY resources ./resources
RUN npm run build

FROM dunglas/frankenphp:1-php8.4 AS base
RUN install-php-extensions pdo_mysql pdo_sqlite redis intl bcmath pcntl opcache zip
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app

FROM base AS teste
COPY composer.json composer.lock ./
RUN composer install --no-interaction --no-progress --no-scripts --prefer-dist
COPY . .
RUN cp -n .env.example .env && composer dump-autoload --optimize
CMD ["php", "vendor/bin/pest"]

FROM base AS producao
ENV APP_ENV=production APP_DEBUG=false SERVER_NAME=:8080
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --prefer-dist --optimize-autoloader
COPY . .
COPY --from=assets /app/public/build ./public/build
RUN rm -rf tests .env && composer dump-autoload --no-dev --optimize \
    && useradd --uid 10001 --no-create-home talo \
    && chown -R talo:talo storage bootstrap/cache /data /config
USER talo
EXPOSE 8080
HEALTHCHECK --interval=15s --timeout=3s --start-period=20s --retries=3 CMD curl -fsS http://127.0.0.1:8080/up || exit 1
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && exec frankenphp run --config /etc/caddy/Caddyfile"]
