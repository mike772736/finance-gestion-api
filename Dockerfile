FROM php:8.2-apache

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    zip unzip libpq-dev && \
    docker-php-ext-install gd zip pdo pdo_pgsql

# Config Apache
RUN a2enmod rewrite headers
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# On configure Apache pour accepter les .htaccess et pointer vers le dossier public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN sed -i 's/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf
WORKDIR /var/www/html
COPY . .

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# DROITS D'ACCÈS CRITIQUES (On donne tous les droits pour le test)
RUN chmod -R 777 storage bootstrap/cache

# Commande de démarrage simplifiée
CMD php artisan config:clear && php artisan view:clear && php artisan migrate --force && apache2-foreground