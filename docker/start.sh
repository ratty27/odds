#!/bin/bash

# Stop existing PHP-FPM processes if any
# Find processes using port 9000 or PHP-FPM processes
for pid in $(find /proc -maxdepth 1 -type d -name '[0-9]*' 2>/dev/null | awk -F/ '{print $3}'); do
	if [ -f "/proc/$pid/cmdline" ]; then
		cmdline=$(cat /proc/$pid/cmdline 2>/dev/null | tr '\0' ' ')
		if echo "$cmdline" | grep -q "php-fpm\|9000"; then
			kill -9 "$pid" 2>/dev/null || true
		fi
	fi
done

# Also try to stop by checking socket connections
# Kill any process listening on port 9000
for pid in $(find /proc -maxdepth 1 -type d -name '[0-9]*' 2>/dev/null | awk -F/ '{print $3}'); do
	if [ -d "/proc/$pid/fd" ]; then
		for fd in /proc/$pid/fd/*; do
			if [ -L "$fd" ] && readlink "$fd" | grep -q "9000"; then
				kill -9 "$pid" 2>/dev/null || true
				break
			fi
		done
	fi
done

sleep 2

# Fix permissions for storage and bootstrap/cache directories
# These directories are mounted as volumes and may have wrong ownership
chown -R nginx:nginx /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache 2>/dev/null || true
# Ensure storage subdirectories exist and have correct permissions
mkdir -p /var/www/html/storage/framework/{sessions,views,cache} 2>/dev/null || true
mkdir -p /var/www/html/storage/logs 2>/dev/null || true
chown -R nginx:nginx /var/www/html/storage/framework /var/www/html/storage/logs 2>/dev/null || true
chmod -R 775 /var/www/html/storage/framework /var/www/html/storage/logs 2>/dev/null || true

# Start PHP-FPM in background with explicit config file
php-fpm -D -y /etc/php-fpm.conf

# Wait for PHP-FPM to be ready
sleep 2

# Check if PHP-FPM is running
if ! ps aux | grep -v grep | grep -q php-fpm; then
	echo "Error: PHP-FPM failed to start"
	php-fpm -t
	exit 1
fi

# Start nginx in foreground
nginx -g 'daemon off;'
