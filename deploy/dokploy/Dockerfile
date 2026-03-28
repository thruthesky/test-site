FROM php:8.3-fpm-alpine

RUN apk add --no-cache bash postgresql-dev libpq nginx \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/html

COPY . /var/www/html

RUN chmod +x /var/www/html/scripts/start-app.sh

CMD ["/var/www/html/scripts/start-app.sh"]

