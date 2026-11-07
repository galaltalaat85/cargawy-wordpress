#!/usr/bin/env bash
# Creates a throwaway local WordPress site in .local/site with cargawy-core linked in.
# Requires: PHP 8.1+, a MySQL/MariaDB server, `composer install` already run.
# Usage: DB_NAME=cargawy_dev DB_USER=cg DB_PASS=cg_local_pw bin/setup-local.sh
set -euo pipefail
ROOT="$(cd "$(dirname "$0")/.." && pwd)"
SITE="$ROOT/.local/site"
WP="$ROOT/vendor/bin/wp --path=$SITE --allow-root"
URL="${SITE_URL:-http://localhost:8080}"

mkdir -p "$SITE"
cp -R "$ROOT/vendor/roots/wordpress-no-content/." "$SITE/"
mkdir -p "$SITE/wp-content/plugins" "$SITE/wp-content/themes"
ln -sfn "$ROOT/wp-content/plugins/cargawy-core" "$SITE/wp-content/plugins/cargawy-core"

if [ ! -f "$SITE/wp-config.php" ]; then
  $WP config create --dbname="${DB_NAME:-cargawy_dev}" --dbuser="${DB_USER:-cg}" --dbpass="${DB_PASS:-cg_local_pw}" --dbhost="${DB_HOST:-localhost}" --skip-check
  $WP config set WP_DEBUG true --raw
  $WP config set WP_DEBUG_LOG true --raw
fi
$WP core is-installed 2>/dev/null || $WP core install --url="$URL" --title="CarGawy (local)" \
  --admin_user=admin --admin_password="${ADMIN_PASS:-admin-local-only}" --admin_email=admin@example.org --skip-email
$WP plugin activate cargawy-core
echo "Local site ready at $URL (admin / ${ADMIN_PASS:-admin-local-only}). Serve with: php -S localhost:8080 -t $SITE"
