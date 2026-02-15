#!/bin/bash
set -e

# Find mysqld path (try common locations)
if [ -x /usr/sbin/mysqld ]; then
	MYSQLD=/usr/sbin/mysqld
elif [ -x /usr/bin/mysqld ]; then
	MYSQLD=/usr/bin/mysqld
else
	MYSQLD=$(find /usr -name mysqld -type f -executable 2>/dev/null | head -1)
fi

if [ -z "$MYSQLD" ]; then
	echo "Error: mysqld not found"
	exit 1
fi

# Initialize MySQL data directory if it's empty
if [ ! -d /var/lib/mysql/mysql ]; then
	echo "Initializing MySQL data directory..."
	# Run as root for initialization (mysqld will handle user switching)
	$MYSQLD --initialize-insecure --datadir=/var/lib/mysql --user=mysql
	# Change ownership after initialization
	chown -R mysql:mysql /var/lib/mysql
fi

# Ensure proper ownership
chown -R mysql:mysql /var/lib/mysql /var/run/mysqld

# Start MySQL in background
# mysqld will automatically switch to mysql user when --user option is specified
$MYSQLD --user=mysql --datadir=/var/lib/mysql --pid-file=/var/run/mysqld/mysqld.pid --socket=/var/lib/mysql/mysql.sock &

# Wait for MySQL to be ready
until mysqladmin ping -h localhost --silent 2>/dev/null; do
	echo "Waiting for MySQL to start..."
	sleep 1
done

# Run initialization script if it exists and database is not initialized
if [ -f /docker-entrypoint-initdb.d/mysql-init.sh ] && [ ! -f /var/lib/mysql/.initialized ]; then
	/docker-entrypoint-initdb.d/mysql-init.sh
	touch /var/lib/mysql/.initialized
fi

# Keep container running - mysqld will switch to mysql user automatically
exec $MYSQLD --user=mysql --datadir=/var/lib/mysql --pid-file=/var/run/mysqld/mysqld.pid --socket=/var/lib/mysql/mysql.sock
