#!/usr/bin/env bash

set -Eeuo pipefail

PLUGIN_SLUG="magick-toolbox"

fail() {
  printf 'release:verify: %s\n' "$*" >&2
  exit 1
}

require_command() {
  command -v "$1" >/dev/null 2>&1 || fail "required command not found: $1"
}

hash_file() {
  if command -v sha256sum >/dev/null 2>&1; then
    sha256sum "$1" | awk '{print $1}'
  elif command -v shasum >/dev/null 2>&1; then
    shasum -a 256 "$1" | awk '{print $1}'
  else
    fail 'sha256sum or shasum is required'
  fi
}

[ "$#" -eq 1 ] || fail 'usage: bin/verify-release-zip.sh <archive.zip>'

require_command unzip
require_command zipinfo
require_command awk
require_command sed
require_command sort
require_command uniq

zip_path="$1"
[ -f "$zip_path" ] || fail "ZIP does not exist: $zip_path"
[ ! -L "$zip_path" ] || fail "ZIP must not be a symlink: $zip_path"

temporary_root=''
cleanup() {
  if [ -n "$temporary_root" ]; then
    rm -rf -- "$temporary_root"
  fi
}
trap cleanup EXIT HUP INT TERM

temporary_root="$(mktemp -d "${TMPDIR:-/tmp}/magick-toolbox-verify.XXXXXX")"
entries_file="$temporary_root/entries.txt"
extract_root="$temporary_root/extracted"
mkdir -p -- "$extract_root"

unzip -tqq "$zip_path" >/dev/null || fail 'ZIP integrity check failed'
zipinfo -1 "$zip_path" > "$entries_file" || fail 'could not list ZIP entries'
[ -s "$entries_file" ] || fail 'ZIP contains no entries'

if LC_ALL=C zipinfo -l "$zip_path" | awk '
  length($1) == 10 && $1 ~ /^[bclps]/ { found = 1 }
  END { exit(found ? 0 : 1) }
'; then
  fail 'ZIP contains a symlink or unsupported special entry'
fi

duplicate_entry="$(LC_ALL=C sort "$entries_file" | uniq -d | sed -n '1p')"
[ -z "$duplicate_entry" ] || fail "ZIP contains duplicate entry: $duplicate_entry"

while IFS= read -r entry || [ -n "$entry" ]; do
  [ -n "$entry" ] || fail 'ZIP contains an empty entry name'
  case "$entry" in
    /*|*\\*|*//*|*:*) fail "unsafe archive path: $entry" ;;
  esac

  normalized_entry="${entry%/}"
  case "/$normalized_entry/" in
    */./*|*/../*) fail "unsafe archive path: $entry" ;;
  esac
  case "$normalized_entry" in
    "$PLUGIN_SLUG") continue ;;
    "$PLUGIN_SLUG"/*) ;;
    *) fail "entry is outside the single $PLUGIN_SLUG/ root: $entry" ;;
  esac

  relative_entry="${normalized_entry#"$PLUGIN_SLUG"/}"
  relative_basename="${relative_entry##*/}"
  case "$relative_basename" in
    .env*|.phpunit.result.cache|.DS_Store)
      fail "forbidden release file: $entry"
      ;;
  esac
  case "$relative_entry" in
    vite/*/dist/.vite|vite/*/dist/.vite/*)
      fail "forbidden release path: $entry"
      ;;
    vite|vite/admin|vite/admin/dist|vite/admin/dist/*|vite/count|vite/count/dist|vite/count/dist/*) ;;
    vite/*) fail "vite release path is outside admin/count dist: $entry" ;;
  esac

  wrapped_entry="/$relative_entry/"
  case "$wrapped_entry" in
    */tests/*|*/docs/*|*/docs-site/*|*/vendor/*|*/node_modules/*|*/bin/*|*/stubs/*|*/ai/*|*/.git/*|*/.github/*|*/.vscode/*|*/.opencode/*|*/.sisyphus/*)
      fail "forbidden release path: $entry"
      ;;
  esac
  case "$relative_entry" in
    *.md|*.markdown|*.map|*.ts|*.tsx|*.jsx|*.vue|*.scss|*.sass|*.less|*.zip|*.sha256|.distignore|.editorconfig|.gitignore|.nvmrc|composer.json|composer.lock|docker-compose.yml|phpunit*|phpstan*|phpcs.xml|AGENTS.md)
      fail "forbidden release file: $entry"
      ;;
  esac
done < "$entries_file"

unzip -qq "$zip_path" -d "$extract_root" || fail 'ZIP extraction failed'
package_root="$extract_root/$PLUGIN_SLUG"
[ -d "$package_root" ] || fail "missing $PLUGIN_SLUG/ package root"

extracted_symlink="$(find "$package_root" -type l -print -quit)"
[ -z "$extracted_symlink" ] || fail "extracted package contains a symlink: $extracted_symlink"

required_files=(
  "magick-tool-box.php"
  "readme.txt"
  "LICENSE"
  "index.php"
  "uninstall.php"
  "admin/index.php"
  "includes/autoload.php"
  "includes/class-magick-mixture.php"
  "includes/class-magick-helpers.php"
  "includes/class-magick-rate-limiter.php"
  "includes/class-magick-audit-logger.php"
  "includes/class-magick-site-health.php"
  "includes/class-magick-mixture-tool.php"
  "includes/class-mabox-config-schema.php"
  "includes/class-magick-config-manager.php"
  "includes/class-mabox-rest-route-registry.php"
  "includes/interface-mabox-module.php"
  "admin/modules/loader.php"
  "admin/modules/metadata.php"
  "admin/modules/registry.php"
  "admin/modules/tiers.php"
  "admin/class-magick-mixture-admin.php"
  "admin/partials/optimize/site/category_link_simplify.php"
  "public/class-magick-mixture-public.php"
  "vite/admin/dist/index.js"
  "vite/admin/dist/index.css"
  "vite/count/dist/index.js"
  "vite/count/dist/index.css"
)
for required_file in "${required_files[@]}"; do
  [ -f "$package_root/$required_file" ] || fail "missing required release file: $required_file"
done

header_versions="$(sed -nE 's/^[[:space:]]*\*[[:space:]]*Version:[[:space:]]*([^[:space:]]+).*/\1/p' "$package_root/magick-tool-box.php")"
constant_versions="$(sed -nE "s/.*MAGICK_MIXTURE_VERSION[^,]*,[[:space:]]*['\"]([^'\"]+)['\"].*/\1/p" "$package_root/magick-tool-box.php")"
stable_versions="$(sed -nE 's/^[[:space:]]*Stable tag:[[:space:]]*([^[:space:]]+).*/\1/p' "$package_root/readme.txt")"

header_count="$(printf '%s\n' "$header_versions" | sed '/^[[:space:]]*$/d' | wc -l | tr -d '[:space:]')"
constant_count="$(printf '%s\n' "$constant_versions" | sed '/^[[:space:]]*$/d' | wc -l | tr -d '[:space:]')"
stable_count="$(printf '%s\n' "$stable_versions" | sed '/^[[:space:]]*$/d' | wc -l | tr -d '[:space:]')"
[ "$header_count" = '1' ] || fail "expected one plugin Version header, found $header_count"
[ "$constant_count" = '1' ] || fail "expected one MAGICK_MIXTURE_VERSION constant, found $constant_count"
[ "$stable_count" = '1' ] || fail "expected one readme Stable tag, found $stable_count"

header_version="$(printf '%s\n' "$header_versions" | sed -n '1p')"
constant_version="$(printf '%s\n' "$constant_versions" | sed -n '1p')"
stable_version="$(printf '%s\n' "$stable_versions" | sed -n '1p')"
if [ "$header_version" != "$constant_version" ] || [ "$header_version" != "$stable_version" ]; then
  fail "version mismatch: header=$header_version constant=$constant_version stable=$stable_version"
fi

release_sha256="$(hash_file "$zip_path")"
sidecar_path="$zip_path.sha256"
if [ -e "$sidecar_path" ]; then
  [ -f "$sidecar_path" ] || fail "checksum sidecar is not a file: $sidecar_path"
  [ ! -L "$sidecar_path" ] || fail "checksum sidecar must not be a symlink: $sidecar_path"
  sidecar_line_count="$(awk 'END { print NR + 0 }' "$sidecar_path")"
  [ "$sidecar_line_count" = '1' ] || fail 'checksum sidecar must contain exactly one line'
  sidecar_line=''
  IFS= read -r sidecar_line < "$sidecar_path" || [ -n "$sidecar_line" ] || fail 'could not read checksum sidecar'
  expected_sha256="${sidecar_line:0:64}"
  case "$expected_sha256" in
    *[!0-9A-Fa-f]*|'') fail 'checksum sidecar does not contain a SHA-256 value' ;;
  esac
  [ "${#expected_sha256}" -eq 64 ] || fail 'checksum sidecar does not contain a SHA-256 value'
  zip_basename="${zip_path##*/}"
  [ "${sidecar_line:64}" = "  $zip_basename" ] \
    || fail "checksum sidecar filename must match archive basename: $zip_basename"
  expected_sha256="$(printf '%s' "$expected_sha256" | tr '[:upper:]' '[:lower:]')"
  [ "$expected_sha256" = "$release_sha256" ] || fail "checksum mismatch: expected=$expected_sha256 actual=$release_sha256"
fi

entry_count="$(wc -l < "$entries_file" | tr -d '[:space:]')"
size_bytes="$(wc -c < "$zip_path" | tr -d '[:space:]')"
printf 'Verified release ZIP: entries=%s size=%s bytes sha256=%s version=%s\n' \
  "$entry_count" "$size_bytes" "$release_sha256" "$header_version"
