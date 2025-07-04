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

# Crée un dossier de travail
WORKDIR /app

# Copie tout le projet
COPY . .

# Définit l'environnement Symfony
ENV APP_ENV=prod

# Prépare les variables d’environnement compilées
RUN composer dump-env prod

# Installe les dépendances (sans les devs)
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

# Vérifie la présence de symfony/runtime (optionnel mais sécurisant)
RUN test -f vendor/symfony/runtime/composer.json

# Vide le cache proprement
RUN php bin/console cache:clear --no-warmup --env=prod

# Expose un port HTTP pour php -S
EXPOSE 10000

# Lancement du serveur PHP interne
CMD php -S 0.0.0.0:10000 -t public
