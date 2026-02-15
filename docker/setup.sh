#!/bin/bash

# Execute composer install
echo "Running composer install..."
docker compose exec nginx-php composer install

# Execute database migrations
echo "Running database migrations..."
docker compose exec nginx-php php artisan migrate

echo "Setup completed!"
