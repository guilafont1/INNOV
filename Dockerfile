FROM php:8.2-cli

# Installe les extensions PHP nécessaires
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    libzip-dev \
    libpq-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    && docker-php-ext-install pdo pdo_mysql zip

# Installe Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copie ton projet
WORKDIR /app
COPY . .

# Installe les dépendances
RUN composer install --no-dev --optimize-autoloader --no-scripts

RUN php bin/console cache:clear --env=prod

# Expose le port
EXPOSE 10000

# Commande de démarrage
CMD php -S 0.0.0.0:10000 -t public
