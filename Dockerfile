FROM debian:jessie

RUN echo "deb http://packages.dotdeb.org jessie all" > /etc/apt/sources.list.d/dotdeb.list \
	&& apt-key adv --keyserver hkp://keys.gnupg.net --recv-keys 89DF5277 \
	&& apt-get update && apt-get install -y ca-certificates nginx-extras php5-fpm php5-curl
 	&& apt-get clean \
 	&& rm -rf /var/lib/apt/lists/* \
	&& rm /var/www/html/* \
 	&& ln -sf /dev/stdout /var/log/nginx/access.log \
 	&& ln -sf /dev/stderr /var/log/nginx/error.log

COPY . /var/www/html

RUN mv /var/www/html/docker/nginx.conf /etc/nginx/nginx.conf \
	&& mv /var/www/html/docker/run.sh / \
	&& chmod +x /run.sh \
	&& mv /var/www/html/docker/config.php /var/www/html/config.php \
	&& rm -r /var/www/html/docker /var/www/html/Dockerfile \
	&& echo 'env[FB_ID] = $FB_ID\nenv[FB_SECRET] = $FB_SECRET\nenv[FB_REQUIRE_EVENT] = $FB_REQUIRE_EVENT' >> /etc/php5/fpm/pool.d/www.conf

EXPOSE 80
CMD ["/bin/sh", "/run.sh"]
