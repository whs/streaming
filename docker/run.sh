#!/bin/sh

set -e

if [ "$FB_ID" = "" ]; then
	echo "Specify -e FB_ID=..."
	exit 1
fi
if [ "$FB_SECRET" = "" ]; then
	echo "Specify -e FB_SECRET=..."
	exit 1
fi

echo "Starting server with options"
echo "FB_ID=$FB_ID"
echo "FB_SECRET=$FB_SECRET"
echo

php-fpm --fpm-config /usr/local/etc/php-fpm.conf
chown www-data /var/run/php-fpm.sock
exec nginx -g "daemon off;"
