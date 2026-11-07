#!/bin/bash
set -e

# Configuration du port pour Render ($PORT)
PORT="${PORT:-10000}"
export APP_KEY="${APP_KEY:-base64:aYKRvM3bas28yeO65qpEzGcLCI2Q3TbP+ioSvTDRVF0=}"

# Verifie que APP_KEY est valide (Laravel exige 32 octets), sinon en genere une nouvelle
if ! php -r '
$key = getenv("APP_KEY");
$decoded = str_starts_with($key, "base64:") ? base64_decode(substr($key, 7), true) : $key;
exit(is_string($decoded) && strlen($decoded) === 32 ? 0 : 1);
'; then
    echo "==> APP_KEY invalide, generation d'une nouvelle cle..."
    export APP_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
fi
export LOG_CHANNEL="${LOG_CHANNEL:-stderr}"
export DB_CONNECTION="${DB_CONNECTION:-pgsql}"
export CACHE_STORE="${CACHE_STORE:-file}"
export SESSION_DRIVER="${SESSION_DRIVER:-file}"

echo "==> Configuration de Nginx pour écouter sur le port ${PORT}..."
sed -i "s/LISTEN_PORT/${PORT}/g" /etc/nginx/sites-available/default

# Permissions pour le stockage et le cache Laravel
echo "==> Configuration des permissions pour storage et bootstrap/cache..."
mkdir -p /var/www/storage/framework/cache/data /var/www/storage/framework/sessions /var/www/storage/framework/views /var/www/storage/logs /var/www/bootstrap/cache
touch /var/www/storage/logs/laravel.log
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Exécution automatique des migrations (activé par défaut si non défini)
RUN_MIGRATIONS="${RUN_MIGRATIONS:-true}"
if [ "$RUN_MIGRATIONS" = "true" ]; then
    echo "==> Exécution des migrations de la base de données..."
    php /var/www/artisan migrate --force || true
fi

# Exécution automatique des seeders si RUN_SEEDS=true
if [ "$RUN_SEEDS" = "true" ]; then
    echo "==> Exécution des seeders de démonstration..."
    php /var/www/artisan db:seed --force || true
fi

# Optimisation du cache Laravel en production si APP_KEY est défini
if [ -n "$APP_KEY" ]; then
    echo "==> Découverte des packages et optimisation des caches Laravel..."
    php /var/www/artisan package:discover --ansi || true
    php /var/www/artisan config:cache || true
    php /var/www/artisan route:cache || true
    php /var/www/artisan view:cache || true
fi

# Réapplication stricte des permissions après les commandes artisan
chmod -R 777 /var/www/storage /var/www/bootstrap/cache
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

echo "==> Lancement de Supervisord (PHP-FPM + Nginx sur 0.0.0.0:${PORT})..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
