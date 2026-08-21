#!/usr/bin/env bash

set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd -P)"
LANGUAGES_DIR="$PROJECT_ROOT/languages"
DOMAIN="npcink-site-toolbox"
LOCALE="en_US"
PO_PATH="$LANGUAGES_DIR/$DOMAIN-$LOCALE.po"
MO_PATH="$LANGUAGES_DIR/$DOMAIN-$LOCALE.mo"
ADMIN_JSON="$LANGUAGES_DIR/$DOMAIN-$LOCALE-be96897d1813598cc6ffe96654a4f062.json"
COUNTDOWN_JSON="$LANGUAGES_DIR/$DOMAIN-$LOCALE-d4372d764458b4d5899ad1740400c0a9.json"

fail() {
  printf 'i18n:build: %s\n' "$*" >&2
  exit 1
}

for command_name in msgattrib msgfmt wp; do
  command -v "$command_name" >/dev/null 2>&1 || fail "required command not found: $command_name"
done

[ -f "$PO_PATH" ] || fail "missing translation source: $PO_PATH"

if msgattrib --untranslated --no-obsolete "$PO_PATH" | grep -q '^msgid '; then
  fail "$PO_PATH contains untranslated messages"
fi

msgfmt --check --check-format --statistics --output-file="$MO_PATH" "$PO_PATH"

temporary_root="$(mktemp -d "${TMPDIR:-/tmp}/npcink-site-toolbox-language.XXXXXX")"
cleanup() {
  rm -rf -- "$temporary_root"
}
trap cleanup EXIT HUP INT TERM

wp i18n make-json "$PO_PATH" "$temporary_root" \
  --domain="$DOMAIN" \
  --no-purge \
  --pretty-print

[ -f "$temporary_root/$(basename -- "$ADMIN_JSON")" ] \
  || fail "missing Admin translation JSON: $(basename -- "$ADMIN_JSON")"
[ -f "$temporary_root/$(basename -- "$COUNTDOWN_JSON")" ] \
  || fail "missing countdown translation JSON: $(basename -- "$COUNTDOWN_JSON")"

find "$LANGUAGES_DIR" -maxdepth 1 -type f -name "$DOMAIN-$LOCALE-*.json" -delete
mv -- "$temporary_root/$(basename -- "$ADMIN_JSON")" "$ADMIN_JSON"
mv -- "$temporary_root/$(basename -- "$COUNTDOWN_JSON")" "$COUNTDOWN_JSON"

printf 'i18n:build: generated %s, %s and %s\n' "$MO_PATH" "$ADMIN_JSON" "$COUNTDOWN_JSON"
