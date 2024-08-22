FROM php:7.4-apache
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
COPY . /var/www/html/
RUN docker-php-ext-install mysqli
EXPOSE 80