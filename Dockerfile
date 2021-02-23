FROM php:8.0-fpm
# build-args defaults to a production image variant

RUN DEBIAN_FRONTEND=noninteractive apt-get update \
	&& DEBIAN_FRONTEND=noninteractive apt-get install -y --no-install-recommends \
		gnupg \
		libgmp-dev \
		libicu-dev \
		libpq-dev \
		unixodbc-dev \
		unzip \
	&& curl -q https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
	&& curl -q https://packages.microsoft.com/config/debian/10/prod.list > /etc/apt/sources.list.d/mssql-release.list \
	&& apt-get update \
	&& ACCEPT_EULA=Y apt-get install -y --no-install-recommends \
		msodbcsql17 \
		mssql-tools \
	&& docker-php-ext-install \
		gmp \
		intl \
	&& pecl install \
		apcu \
		pdo_sqlsrv \
		sqlsrv \
	&& docker-php-ext-enable \
		opcache \
		pdo_sqlsrv \
	&& echo "extension=apcu.so" > /usr/local/etc/php/conf.d/ext-apcu.ini \
	&& sed -e 's/access.log/;access.log/' -i /usr/local/etc/php-fpm.d/docker.conf \
	&& php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
		&& php -r "if (hash_file('sha256', 'composer-setup.php') === 'df553aecf6cb5333f067568fd50310bfddce376505c9de013a35977789692366') { echo 'Installer verified'; } else { echo 'Installer corrupt'; } echo PHP_EOL;" \
		&& php composer-setup.php --filename=composer --install-dir=/usr/local/bin \
		&& php -r "unlink('composer-setup.php');"
ARG IS_PROD_BUILD=true
ENV IS_PROD_BUILD=$IS_PROD_BUILD
RUN if [ "$IS_PROD_BUILD" != true ]; then \
		pecl install xdebug; \
		docker-php-ext-enable xdebug; \
	fi
COPY ./.docker/php.ini /usr/local/etc/php/

# Prepare app workdir & tools, switch to unprivileged user
WORKDIR /app
RUN mkdir -p \
		temp/cache \
		temp/sessions \
		/var/www/.composer \
	&& chown -R www-data:www-data \
		/app \
		/var/www/.composer

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
