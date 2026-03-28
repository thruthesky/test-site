#!/bin/sh
set -eu

cd /var/www/html

mkdir -p storage/sessions public/uploads/profiles
chmod -R 0777 storage public/uploads
chown -R www-data:www-data storage public/uploads || true

for i in $(seq 1 30); do
  if php -r 'require_once "app/Bootstrap/autoload.php"; App\Config\Env::load(getcwd()); App\Config\Database::connection(); echo "ok";' >/dev/null 2>&1; then
    break
  fi
  echo "Waiting for database..."
  sleep 2
done

php scripts/migrate.php

exec php-fpm
