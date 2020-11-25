FROM php:7.4-fpm
# build-args defaults to a production image variant

RUN DEBIAN_FRONTEND=noninteractive apt-get update \
	&& DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
		libgmp-dev \
		libicu-dev \
		libpq-dev \
		unzip \
	&& docker-php-ext-install \
		gmp \
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
        && php -r "if (hash_file('sha384', 'composer-setup.php') === '756890a4488ce9024fc62c56153228907f1545c228516cbf63f885e036d37e9a59d27d63f46af1d4d07ee0f76181c7d3') { echo 'Installer verified'; } else { echo 'Installer corrupt'; } echo PHP_EOL;" \
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
		temp/cache \
		temp/sessions \
	&& chown -R www-data:www-data \
		/app

USER www-data

# Install app dependencies
COPY ./composer.json ./composer.lock ./
RUN composer install --no-autoloader --no-interaction --no-scripts --no-suggest \
	&& composer clearcache

# Copy app sources & initialize app
COPY ./config ./config/
COPY ./src ./src/
COPY ./www ./www/
RUN composer dump-autoload --optimize
