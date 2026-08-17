#!/usr/bin/env bash

set -euo pipefail

ZIP_PATH="${1:-npcink-site-toolbox.zip}"
WORDPRESS_IMAGE="${WORDPRESS_IMAGE:-wordpress:php8.2-apache}"
WORDPRESS_CLI_IMAGE="${WORDPRESS_CLI_IMAGE:-wordpress:cli-php8.2}"
DATABASE_IMAGE="${DATABASE_IMAGE:-mariadb:10.11}"

fail() {
  printf 'release:wordpress-org-check: %s\n' "$*" >&2
  exit 1
}

for command_name in docker shasum awk sed; do
  command -v "$command_name" >/dev/null 2>&1 || fail "required command not found: $command_name"
done

[ -f "$ZIP_PATH" ] || fail "release ZIP not found: $ZIP_PATH"

ZIP_PATH="$(cd "$(dirname "$ZIP_PATH")" && pwd)/$(basename "$ZIP_PATH")"
ZIP_SHA256_BEFORE="$(shasum -a 256 "$ZIP_PATH" | awk '{print $1}')"
RUN_SUFFIX="${GITHUB_RUN_ID:-local}-$$-${RANDOM}"
RUN_SUFFIX="$(printf '%s' "$RUN_SUFFIX" | tr -cd '[:alnum:]-')"
PREFIX="npcink-wordpress-org-$RUN_SUFFIX"
NETWORK_NAME="$PREFIX-net"
DATABASE_VOLUME="$PREFIX-db-volume"
WORDPRESS_VOLUME="$PREFIX-wp-volume"
DATABASE_CONTAINER="$PREFIX-db"
WORDPRESS_CONTAINER="$PREFIX-wp"

cleanup() {
  docker rm -f "$WORDPRESS_CONTAINER" "$DATABASE_CONTAINER" >/dev/null 2>&1 || true
  docker volume rm "$WORDPRESS_VOLUME" "$DATABASE_VOLUME" >/dev/null 2>&1 || true
  docker network rm "$NETWORK_NAME" >/dev/null 2>&1 || true
}
trap cleanup EXIT

docker network create "$NETWORK_NAME" >/dev/null
docker volume create "$DATABASE_VOLUME" >/dev/null
docker volume create "$WORDPRESS_VOLUME" >/dev/null

docker run -d \
  --name "$DATABASE_CONTAINER" \
  --network "$NETWORK_NAME" \
  -v "$DATABASE_VOLUME:/var/lib/mysql" \
  -e MARIADB_DATABASE=wordpress \
  -e MARIADB_USER=wordpress \
  -e MARIADB_PASSWORD=wordpress \
  -e MARIADB_ROOT_PASSWORD=rootpass \
  "$DATABASE_IMAGE" >/dev/null

for attempt in $(seq 1 60); do
  if docker exec "$DATABASE_CONTAINER" \
    mariadb -uwordpress -pwordpress wordpress -e 'SELECT 1' >/dev/null 2>&1; then
    break
  fi
  [ "$attempt" -lt 60 ] || fail 'database did not become ready'
  sleep 1
done

docker run -d \
  --name "$WORDPRESS_CONTAINER" \
  --network "$NETWORK_NAME" \
  -v "$WORDPRESS_VOLUME:/var/www/html" \
  -e WORDPRESS_DB_HOST="$DATABASE_CONTAINER:3306" \
  -e WORDPRESS_DB_USER=wordpress \
  -e WORDPRESS_DB_PASSWORD=wordpress \
  -e WORDPRESS_DB_NAME=wordpress \
  "$WORDPRESS_IMAGE" >/dev/null

for attempt in $(seq 1 60); do
  if docker exec "$WORDPRESS_CONTAINER" test -f /var/www/html/wp-includes/version.php; then
    break
  fi
  [ "$attempt" -lt 60 ] || fail 'WordPress files did not become ready'
  sleep 1
done

docker cp "$ZIP_PATH" "$WORDPRESS_CONTAINER:/var/www/html/release-under-test.zip" >/dev/null

wp_cli() {
  docker run --rm \
    --user 0 \
    --network "$NETWORK_NAME" \
    --volumes-from "$WORDPRESS_CONTAINER" \
    -e WORDPRESS_DB_HOST="$DATABASE_CONTAINER:3306" \
    -e WORDPRESS_DB_USER=wordpress \
    -e WORDPRESS_DB_PASSWORD=wordpress \
    -e WORDPRESS_DB_NAME=wordpress \
    "$WORDPRESS_CLI_IMAGE" \
    wp --allow-root --path=/var/www/html "$@"
}

wp_cli core install \
  --url=http://localhost \
  --title='Npcink WordPress.org Release Check' \
  --admin_user=admin \
  --admin_password=adminpass123 \
  --admin_email=admin@example.com \
  --skip-email >/dev/null

wp_cli config set WP_DEBUG true --raw >/dev/null
wp_cli config set WP_DEBUG_LOG true --raw >/dev/null
wp_cli config set WP_DEBUG_DISPLAY false --raw >/dev/null
wp_cli plugin install /var/www/html/release-under-test.zip --force >/dev/null
wp_cli plugin activate npcink-site-toolbox >/dev/null

docker exec "$WORDPRESS_CONTAINER" sh -lc 'rm -f /var/www/html/wp-content/debug.log /tmp/npcink-release-cookie.txt'

frontend_status="$(docker exec "$WORDPRESS_CONTAINER" curl -sS -o /dev/null -w '%{http_code}' http://localhost/)"
[ "$frontend_status" = '200' ] || fail "frontend smoke request returned HTTP $frontend_status"

docker exec "$WORDPRESS_CONTAINER" curl -sS \
  -c /tmp/npcink-release-cookie.txt \
  -o /dev/null \
  http://localhost/wp-login.php

login_status="$(docker exec "$WORDPRESS_CONTAINER" curl -sS -L \
  -b /tmp/npcink-release-cookie.txt \
  -c /tmp/npcink-release-cookie.txt \
  -o /tmp/npcink-login-result.html \
  -w '%{http_code}' \
  --data-urlencode 'log=admin' \
  --data-urlencode 'pwd=adminpass123' \
  --data-urlencode 'wp-submit=Log In' \
  --data-urlencode 'redirect_to=http://localhost/wp-admin/' \
  --data-urlencode 'testcookie=1' \
  http://localhost/wp-login.php)"
[ "$login_status" = '200' ] || fail "admin login returned HTTP $login_status"

admin_status="$(docker exec "$WORDPRESS_CONTAINER" curl -sS -L \
  -b /tmp/npcink-release-cookie.txt \
  -o /tmp/npcink-admin-result.html \
  -w '%{http_code}' \
  http://localhost/wp-admin/plugins.php)"
[ "$admin_status" = '200' ] || fail "admin smoke request returned HTTP $admin_status"
docker exec "$WORDPRESS_CONTAINER" grep -Fq 'id="adminmenu"' /tmp/npcink-admin-result.html \
  || fail 'admin authentication did not reach the WordPress dashboard'

plugin_page_status="$(docker exec "$WORDPRESS_CONTAINER" curl -sS -L \
  -b /tmp/npcink-release-cookie.txt \
  -o /tmp/npcink-plugin-page-result.html \
  -w '%{http_code}' \
  'http://localhost/wp-admin/plugins.php?page=npcink-site-toolbox')"
[ "$plugin_page_status" = '200' ] || fail "plugin page smoke request returned HTTP $plugin_page_status"
docker exec "$WORDPRESS_CONTAINER" grep -Fq 'id="root"' /tmp/npcink-plugin-page-result.html \
  || fail 'plugin settings page did not render its application root'

if docker exec "$WORDPRESS_CONTAINER" test -s /var/www/html/wp-content/debug.log; then
  docker exec "$WORDPRESS_CONTAINER" sed -n '1,240p' /var/www/html/wp-content/debug.log >&2
  fail 'WP_DEBUG log is not empty after frontend and admin smoke requests'
fi

wp_cli plugin install plugin-check --force --activate >/dev/null
WORDPRESS_VERSION="$(wp_cli core version)"
PHP_VERSION="$(docker exec "$WORDPRESS_CONTAINER" php -r 'echo PHP_VERSION;')"
PCP_VERSION="$(wp_cli plugin get plugin-check --field=version)"
PLUGIN_STATUS="$(wp_cli plugin get npcink-site-toolbox --field=status)"
[ "$PLUGIN_STATUS" = 'active' ] || fail "plugin status is $PLUGIN_STATUS"

set +e
PCP_OUTPUT="$(wp_cli plugin check npcink-site-toolbox --format=table 2>&1)"
PCP_STATUS=$?
set -e
printf '%s\n' "$PCP_OUTPUT"

ERROR_COUNT="$(printf '%s\n' "$PCP_OUTPUT" | awk -F '\t' '$3 == "ERROR" { count++ } END { print count + 0 }')"
WARNING_COUNT="$(printf '%s\n' "$PCP_OUTPUT" | awk -F '\t' '$3 == "WARNING" { count++ } END { print count + 0 }')"
UNEXPECTED_WARNING_COUNT="$(printf '%s\n' "$PCP_OUTPUT" | awk -F '\t' '
  /^FILE: / {
    current_file = substr($0, 7)
    next
  }
  $3 == "WARNING" {
    allowed = current_file == "admin/partials/performance/media_health/webp_batch.php" \
      && $4 == "WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound" \
      && ($5 ~ /wp_generate_attachment_metadata/ || $5 ~ /intermediate_image_sizes_advanced/)
    if (!allowed) {
      count++
    }
  }
  END { print count + 0 }
')"
[ "$ERROR_COUNT" -eq 0 ] || fail "Plugin Check reported $ERROR_COUNT error(s)"
[ "$UNEXPECTED_WARNING_COUNT" -eq 0 ] || fail "Plugin Check reported $UNEXPECTED_WARNING_COUNT unexpected warning(s)"
[ "$PCP_STATUS" -eq 0 ] || fail "Plugin Check exited with status $PCP_STATUS"

if docker exec "$WORDPRESS_CONTAINER" test -s /var/www/html/wp-content/debug.log; then
  docker exec "$WORDPRESS_CONTAINER" sed -n '1,240p' /var/www/html/wp-content/debug.log >&2
  fail 'WP_DEBUG log is not empty after Plugin Check'
fi

ZIP_SHA256_AFTER="$(shasum -a 256 "$ZIP_PATH" | awk '{print $1}')"
[ "$ZIP_SHA256_BEFORE" = "$ZIP_SHA256_AFTER" ] || fail 'release ZIP changed during verification'

printf 'WordPress.org release check passed: wordpress=%s php=%s pcp=%s errors=%s warnings=%s unexpected_warnings=%s sha256=%s\n' \
  "$WORDPRESS_VERSION" \
  "$PHP_VERSION" \
  "$PCP_VERSION" \
  "$ERROR_COUNT" \
  "$WARNING_COUNT" \
  "$UNEXPECTED_WARNING_COUNT" \
  "$ZIP_SHA256_AFTER"

cleanup
trap - EXIT
