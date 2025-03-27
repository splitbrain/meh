#!/bin/sh
set -e

# Run database migrations
php /app/meh migrate-all

# Then execute the original entrypoint
exec docker-php-entrypoint "$@"
