#!/bin/bash
set -e

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
max_attempts=30
attempt=0

while [ $attempt -lt $max_attempts ]; do
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" "$DB_NAME" &>/dev/null; then
        echo "MySQL is ready!"
        break
    fi
    attempt=$((attempt + 1))
    echo "Attempt $attempt/$max_attempts - MySQL not ready yet..."
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "ERROR: MySQL did not become ready in time"
    exit 1
fi

# Check if users table exists (indicator that schema is loaded)
if ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1 FROM users LIMIT 1" "$DB_NAME" &>/dev/null; then
    echo "Tables not found. Loading schema..."
    mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/data/schema.sql
    echo "Schema loaded successfully!"
else
    echo "Database schema already exists."
fi

# Start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground
