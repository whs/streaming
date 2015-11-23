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
echo "FB_REQUIRE_EVENT=$FB_REQUIRE_EVENT"
echo

/usr/sbin/php5-fpm --fpm-config /etc/php5/fpm/php-fpm.conf
exec /usr/sbin/nginx -g "daemon off;"