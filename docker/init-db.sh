#!/bin/bash
set -e

echo "=== NicheNest Init Script ==="
echo "DB_HOST: $DB_HOST"
echo "DB_NAME: $DB_NAME"
echo "DB_USER: $DB_USER"

# Wait for MySQL to be ready
echo "Waiting for MySQL to be ready..."
max_attempts=60
attempt=0

while [ $attempt -lt $max_attempts ]; do
    if mysqladmin ping -h"$DB_HOST" --silent 2>/dev/null; then
        echo "MySQL is responding to ping!"
        # Now check if we can actually connect
        if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1" "$DB_NAME" 2>/dev/null; then
            echo "MySQL connection successful!"
            break
        fi
    fi
    attempt=$((attempt + 1))
    echo "Attempt $attempt/$max_attempts - MySQL not ready yet..."
    sleep 2
done

if [ $attempt -eq $max_attempts ]; then
    echo "ERROR: MySQL did not become ready in time"
    echo "Starting Apache anyway to show error page..."
    exec apache2-foreground
fi

# Check if users table exists (indicator that schema is loaded)
if ! mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1 FROM users LIMIT 1" "$DB_NAME" 2>/dev/null; then
    echo "Tables not found. Loading schema..."
    if mysql -h"$DB_HOST" -u"$DB_USER" -p"$DB_PASS" "$DB_NAME" < /var/www/html/data/schema.sql; then
        echo "Schema loaded successfully!"
    else
        echo "ERROR: Failed to load schema"
    fi
else
    echo "Database schema already exists."
fi

# Start Apache in foreground
echo "Starting Apache..."
exec apache2-foreground
