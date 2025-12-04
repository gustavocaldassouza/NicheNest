#!/bin/bash
set -e

echo "Starting NicheNest initialization..."

# Run schema initialization
php /var/www/html/docker/init-schema.php

echo "Starting Apache..."
exec apache2-foreground
