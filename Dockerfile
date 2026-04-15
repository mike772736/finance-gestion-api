FROM php:8.2-apache

# Installation des dépendances système
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    zip unzip libpq-dev && \
    docker-php-ext-install gd zip pdo pdo_pgsql

# Activation des modules Apache
RUN a2enmod rewrite headers

# Configuration du DocumentRoot vers /public (Essentiel pour Laravel)
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# Autoriser le .htaccess
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Droits d'accès cruciaux pour Render
RUN chmod -R 777 storage bootstrap/cache

# COMMANDE DE DÉMARRAGE : Tout sur une seule ligne sans coupure
CMD php artisan config:cache && php artisan route:cache && apache2-foreground