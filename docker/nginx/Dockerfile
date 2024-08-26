FROM laravel.test as frontend

FROM nginx:alpine

# Security packages
RUN apk update && apk upgrade

COPY ./docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY . /var/www/html

# Copying assets
COPY --from=frontend /var/www/html/public /var/www/html/public

EXPOSE 80
