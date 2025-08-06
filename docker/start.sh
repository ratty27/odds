#!/bin/bash

service cron start

if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
    php artisan key:generate
fi

php-fpm -D

# Start Nginx in foreground
nginx -g "daemon off;"
