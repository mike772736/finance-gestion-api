FROM php:8.2-apache

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    zip unzip libpq-dev && \
    docker-php-ext-install gd zip pdo pdo_pgsql

# Config Apache
RUN a2enmod rewrite headers

# On définit le dossier public comme racine
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# On autorise le .htaccess de manière très simple
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Droits d'accès
RUN chmod -R 777 storage bootstrap/cache

# Commande de démarrage
CMD php artisan config:clear && php artisan view:clear 
&& apache2-foreground