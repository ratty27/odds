#!/bin/bash
set -euo pipefail

# Storage may be host-mounted; keep it writable for artisan (nginx user).
mkdir -p /var/www/html/storage/framework/{sessions,views,cache} \
	/var/www/html/storage/logs \
	/var/www/html/bootstrap/cache
chown -R nginx:nginx /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true

# cronie ignores executable files under /etc/cron.d
install -m 644 /usr/local/etc/odds-crontab /etc/cron.d/odds

echo "Starting crond (mail: every 1 min, odds: every 5 min)"
exec crond -n
