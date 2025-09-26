#!/usr/bin/env bash
set -euo pipefail
# Wait for WordPress to be ready and run WP-CLI commands inside the container
WP_CONTAINER_NAME="metplugin_wordpress_1"
# If container name isn't that, we'll find the wordpress container
WP_CONTAINER_NAME=$(docker ps --format '{{.Names}}' | grep wordpress || true)
if [ -z "$WP_CONTAINER_NAME" ]; then
  WP_CONTAINER_NAME=$(docker ps --format '{{.Names}}' | head -n1)
fi
if [ -z "$WP_CONTAINER_NAME" ]; then
  echo "Cannot find a running container to exec into"
  exit 1
fi

# Install WP-CLI if not present
docker exec -u root "$WP_CONTAINER_NAME" bash -lc "if ! command -v wp >/dev/null; then curl -sSL https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar -o /usr/local/bin/wp && chmod +x /usr/local/bin/wp; fi"

# Wait until WP returns 200
for i in {1..30}; do
  status=$(docker exec "$WP_CONTAINER_NAME" curl -s -o /dev/null -w "%{http_code}" http://localhost || true)
  if [ "$status" = "200" ]; then
    echo "WordPress is responding"
    break
  fi
  echo "Waiting for WordPress... ($i)"
  sleep 2
done

# Configure WP (use defaults)
URL="http://localhost:8000"
TITLE="Local MET Plugin Test"
ADMIN_USER="admin"
ADMIN_PASS="password"
ADMIN_EMAIL="admin@example.test"

docker exec "$WP_CONTAINER_NAME" wp core install --url="$URL" --title="$TITLE" --admin_user="$ADMIN_USER" --admin_password="$ADMIN_PASS" --admin_email="$ADMIN_EMAIL" --skip-email || true

echo "Activating plugin met-plugin"
docker exec "$WP_CONTAINER_NAME" wp plugin activate met-plugin || true

echo "Done"
