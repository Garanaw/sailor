FROM php:8.3-fpm

ARG WWWGROUP=1001
ARG NODE_VERSION=22
ARG MYSQL_CLIENT="mysql-client"
ARG POSTGRES_VERSION=15

WORKDIR /var/www/html

ENV DEBIAN_FRONTEND=noninteractive
ENV TZ=UTC
ENV SUPERVISOR_PHP_COMMAND="/usr/bin/php -d variables_order=EGPCS /var/www/html/artisan serve --host=0.0.0.0 --port=80"
ENV SUPERVISOR_PHP_USER="sail"

# Set the timezone
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# Prepare scripts
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions
COPY ./php/8.3/scripts/ /scripts/php/8.3/scripts/
RUN chmod +x /scripts/php/8.3/scripts/*.sh

# Install OS packages and dependencies
RUN /scripts/php/8.3/scripts/01-install-os-dependencies.sh

# Install keyring for PHP PPA
RUN apt-get update
RUN mkdir -p /etc/apt/keyrings
RUN curl -sS 'https://keyserver.ubuntu.com/pks/lookup?op=get&search=0x14aa40ec0831756756d7f66c4f4ea0aae5267a6c' | gpg --dearmor | tee /etc/apt/keyrings/ppa_ondrej_php.gpg /dev/null
RUN echo "deb [signed-by=/etc/apt/keyrings/ppa_ondrej_php.gpg] https://ppa.launchpadcontent.net/ondrej/php/ubuntu jammy main" > /etc/apt/sources.list.d/ppa_ondrej_php.list
RUN apt-get update

# Install PHP extensions
RUN /scripts/php/8.3/scripts/02-install-php-ext.sh

# Install tools
RUN /scripts/php/8.3/scripts/03-install-tools.sh

# Clean up
RUN /scripts/php/8.3/scripts/04-cleanup.sh

# Set capabilities
RUN setcap "cap_net_bind_service=+ep" /usr/local/bin/php

# Create sail user
RUN groupadd --force -g $WWWGROUP sail
RUN useradd -ms /bin/bash --no-user-group -g $WWWGROUP -u 1337 sail

# Copy scripts and configuration files
COPY ./php/8.3/start-container /usr/local/bin/start-container
COPY ./php/8.3/conf/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./php/8.3/conf/php.ini /etc/php/8.3/cli/conf.d/99-sail.ini
RUN chmod +x /usr/local/bin/start-container

ENTRYPOINT ["start-container"]
CMD ["php-fpm"]
