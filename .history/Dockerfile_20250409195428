FROM php:8.2-apache

# Copie des fichiers PHP
COPY . /var/www/html/

# Configuration Apache
RUN a2enmod rewrite
COPY .htaccess /var/www/html/

# Port exposé
EXPOSE 80

CMD ["apache2-foreground"]
