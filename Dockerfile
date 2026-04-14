FROM php:8.2-apache

# Installation des dépendances
RUN apt-get update && apt-get install -y \
    libpng-dev libjpeg-dev libfreetype6-dev libzip-dev \
    zip unzip libpq-dev && \
    docker-php-ext-install gd zip pdo pdo_pgsql

# Config Apache (CORRIGÉE)
RUN a2enmod rewrite headers
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# On configure Apache pour pointer vers /public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# ON FORCE L'AUTORISATION DU .htaccess (Indispensable pour Laravel sur Render)
RUN echo '<Directory /var/www/html/public>\n\
    Options Indexes FollowSymLinks\n\
    AllowOverride All\n\
    Require all granted\n\
</Directory>' >> /etc/apache2/apache2.conf

WORKDIR /var/www/html
COPY . .

# Installation de Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# DROITS D'ACCÈS
RUN chmod -R 777 storage bootstrap/cache

# Commande de démarrage
CMD php artisan config:clear && php artisan view:clear && php artisan migrate --force && apache2-foreground