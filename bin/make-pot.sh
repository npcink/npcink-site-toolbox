#!/usr/bin/env bash

set -Eeuo pipefail

SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd -P)"
POT_PATH="$PROJECT_ROOT/languages/npcink-site-toolbox.pot"

fail() {
  printf 'i18n:pot: %s\n' "$*" >&2
  exit 1
}

command -v php >/dev/null 2>&1 || fail 'required command not found: php'
command -v wp >/dev/null 2>&1 || fail 'required command not found: wp'
command -v xgettext >/dev/null 2>&1 || fail 'required command not found: xgettext (GNU gettext)'
command -v rg >/dev/null 2>&1 || fail 'required command not found: rg (ripgrep)'

mkdir -p -- "$(dirname -- "$POT_PATH")"

temporary_root="$(mktemp -d "${TMPDIR:-/tmp}/npcink-site-toolbox-i18n.XXXXXX")"
cleanup() {
  rm -rf -- "$temporary_root"
}
trap cleanup EXIT HUP INT TERM

javascript_pot="$temporary_root/admin-javascript.pot"
( 
  cd -- "$PROJECT_ROOT"
  rg --null --files-with-matches 'import \{[^}]*\b__\b[^}]*\} from "@/tool/i18n"' vite/admin/src -g '*.ts' -g '*.tsx' -g '!*.test.ts' -g '!*.test.tsx' \
    | xargs -0 xgettext \
      --language=JavaScript \
      --keyword=__ \
      --from-code=UTF-8 \
      --package-name='Npcink Site Toolbox' \
      --output="$javascript_pot"
)

php \
  -d memory_limit=1G \
  -d openssl.cafile=/etc/ssl/cert.pem \
  -d curl.cainfo=/etc/ssl/cert.pem \
  "$(command -v wp)" i18n make-pot "$PROJECT_ROOT" "$POT_PATH" \
  --slug=npcink-site-toolbox \
  --domain=npcink-site-toolbox \
  --merge="$javascript_pot" \
  --headers='{"POT-Creation-Date":""}' \
  --exclude=vendor,node_modules,vite/node_modules,tests,docs,docs-site,ai,bin \
  --skip-audit

printf 'i18n:pot: generated %s\n' "$POT_PATH"
