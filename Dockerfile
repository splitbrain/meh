FROM php:8.4-apache

RUN sed -ri -e 's!/var/www/html!/app/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/app/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN a2enmod rewrite
RUN a2enmod env

VOLUME /app/data
WORKDIR /app
COPY ./release /app/

# Set up the entrypoint script
ENTRYPOINT ["/app/docker-entrypoint.sh"]
CMD ["apache2-foreground"]
