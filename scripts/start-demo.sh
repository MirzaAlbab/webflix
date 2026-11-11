#!/bin/sh
set -eu

mkdir -p /app/storage/demo
touch "$DB_DATABASE"
export APP_KEY="$(php artisan key:generate --show --no-ansi)"
php artisan migrate --force --no-interaction
php artisan db:seed --class=DemoSeeder --force --no-interaction
exec php artisan serve --no-reload --host=0.0.0.0 --port=8001
