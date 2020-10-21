FROM php:7.4-fpm
# build-args defaults to a production image variant

RUN DEBIAN_FRONTEND=noninteractive apt-get update \
	&& DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
		libicu-dev \
		libpq-dev \
		unzip \
	&& docker-php-ext-install \
		intl \
		pdo_pgsql \
		pgsql \
	&& docker-php-ext-enable \
		opcache \
	&& pecl install \
		apcu \
	&& echo "extension=apcu.so" > /usr/local/etc/php/conf.d/ext-apcu.ini \
	&& sed -e 's/access.log/;access.log/' -i /usr/local/etc/php-fpm.d/docker.conf \
	&& php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
		&& php -r "if (hash_file('SHA384', 'composer-setup.php') === '48e3236262b34d30969dca3c37281b3b4bbe3221bda826ac6a9a62d6444cdb0dcd0615698a5cbe587c3f0fe57a54d8f5') { echo 'Installer verified'; } else { echo 'Installer verification failed!'; } echo PHP_EOL;" \
		&& php composer-setup.php --filename=composer --install-dir=/usr/local/bin \
		&& php -r "unlink('composer-setup.php');"
ARG IS_PROD_BUILD=true
ENV IS_PROD_BUILD=$IS_PROD_BUILD
RUN if [ "$IS_PROD_BUILD" != true ]; then \
		pecl install xdebug; \
		docker-php-ext-enable xdebug; \
	fi
COPY ./.docker/bin/tini-0.19.0_amd64 /usr/local/bin/tini
COPY ./.docker/bin/wait-for-it /usr/local/bin/
COPY ./.docker/php.ini /usr/local/etc/php/

# Prepare app workdir & tools, switch to unprivileged user
WORKDIR /app
RUN mkdir -p \
		logs \
		temp/cache/phpstan \
	&& chown -R www-data:www-data \
		/app \
		/var/www

USER www-data
RUN composer global require hirak/prestissimo

# Install app dependencies
COPY ./composer.json ./composer.lock ./
RUN composer install --no-autoloader --no-interaction --no-scripts --no-suggest \
	&& composer clearcache

# Copy app sources & initialize app
COPY ./config ./config/
COPY ./src ./src/
COPY ./www ./www/
RUN composer dump-autoload --optimize
