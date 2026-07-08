#!/bin/sh
set -e

export APP_ENV="${APP_ENV:-prod}"
export APP_DEBUG="${APP_DEBUG:-0}"

if [ -z "$APP_SECRET" ]; then
    echo "ERREUR: la variable APP_SECRET doit être définie (dashboard Render)."
    exit 1
fi

if [ -z "$DATABASE_URL" ]; then
    echo "ERREUR: la variable DATABASE_URL doit être définie (ex. Aiven MySQL)."
    exit 1
fi

echo "Attente de la base de données…"
ready=0
for i in $(seq 1 30); do
    if php bin/console doctrine:query:sql "SELECT 1" --env=prod >/dev/null 2>&1; then
        ready=1
        break
    fi
    sleep 2
done

if [ "$ready" -ne 1 ]; then
    echo "ERREUR: impossible de joindre la base de données."
    exit 1
fi

echo "Préparation du cache Symfony…"
php bin/console cache:clear --env=prod --no-warmup
php bin/console cache:warmup --env=prod
php bin/console assets:install public --env=prod --no-interaction

PORT="${PORT:-10000}"
echo "Démarrage du serveur sur 0.0.0.0:${PORT}…"
exec php -S "0.0.0.0:${PORT}" -t public public/router.php
