#!/bin/sh
set -e

# `.:/var/www/html` is a bind mount, so it overrides whatever ownership the
# image had at build time - a chown baked into the Dockerfile is a no-op at
# runtime. Root-run CLI commands (`docker exec ... php flarum ...`) also
# leave storage/logs files root-owned, which then makes php-fpm (www-data)
# fail to write its own log with a silently-swallowed 500. Re-assert
# ownership/permissions on every container start instead.
chown -R www-data:www-data /var/www/html/storage
chmod -R 755 /var/www/html/storage

exec "$@"
