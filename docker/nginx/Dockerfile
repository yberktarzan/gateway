FROM nginx:1.27-alpine
RUN apk add --no-cache bash curl
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/conf.d/app.conf /etc/nginx/conf.d/default.conf
WORKDIR /var/www/html
