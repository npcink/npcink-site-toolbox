#!/usr/bin/env bash

set -Eeuo pipefail

PLUGIN_SLUG="magick-toolbox"
SCRIPT_DIR="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd -P)"
PROJECT_ROOT="$(cd -- "$SCRIPT_DIR/.." && pwd -P)"
DISTIGNORE="$PROJECT_ROOT/.distignore"
VERIFY_SCRIPT="$SCRIPT_DIR/verify-release-zip.sh"
DEFAULT_OUTPUT="$PROJECT_ROOT/magick-toolbox.zip"

fail() {
  printf 'release:build: %s\n' "$*" >&2
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

if [ "$#" -gt 1 ]; then
  fail 'usage: bin/build-release-zip.sh [output.zip]'
fi

require_command rsync
require_command zip
require_command unzip
require_command zipinfo

[ -f "$DISTIGNORE" ] || fail "missing $DISTIGNORE"
[ -f "$VERIFY_SCRIPT" ] || fail "missing $VERIFY_SCRIPT"

requested_output="${1:-$DEFAULT_OUTPUT}"
case "$requested_output" in
  /*) ;;
  *) requested_output="$(pwd -P)/$requested_output" ;;
esac

output_dir="$(dirname -- "$requested_output")"
output_name="$(basename -- "$requested_output")"
mkdir -p -- "$output_dir"
output_dir="$(cd -- "$output_dir" && pwd -P)"
output_path="$output_dir/$output_name"
sidecar_path="$output_path.sha256"

case "$output_name" in
  ''|.|..) fail 'output must be a ZIP file path' ;;
esac
case "$output_name" in
  *.zip) ;;
  *) fail 'output filename must end in .zip' ;;
esac
[ ! -d "$output_path" ] || fail "output path is a directory: $output_path"
[ ! -L "$output_path" ] || fail "output path must not be a symlink: $output_path"
[ ! -d "$sidecar_path" ] || fail "sidecar path is a directory: $sidecar_path"
[ ! -L "$sidecar_path" ] || fail "sidecar path must not be a symlink: $sidecar_path"

required_assets=(
  "vite/admin/dist/index.js"
  "vite/admin/dist/index.css"
  "vite/count/dist/index.js"
  "vite/count/dist/index.css"
)
for asset in "${required_assets[@]}"; do
  [ -f "$PROJECT_ROOT/$asset" ] || fail "missing built frontend asset: $asset"
done

temporary_root=''
output_temporary_dir=''
transaction_active=0
transaction_committed=0
had_zip=0
had_sidecar=0
installed_zip=0
installed_sidecar=0
preserve_output_temporary_dir=0

rollback_transaction() {
  local rollback_failed=0

  if [ "$installed_sidecar" -eq 1 ]; then
    rm -f -- "$sidecar_path" || rollback_failed=1
    installed_sidecar=0
  fi
  if [ "$installed_zip" -eq 1 ]; then
    rm -f -- "$output_path" || rollback_failed=1
    installed_zip=0
  fi
  if [ "$had_zip" -eq 1 ] && [ -e "$backup_zip" ]; then
    mv -- "$backup_zip" "$output_path" || rollback_failed=1
  fi
  if [ "$had_sidecar" -eq 1 ] && [ -e "$backup_sidecar" ]; then
    mv -- "$backup_sidecar" "$sidecar_path" || rollback_failed=1
  fi

  if [ "$rollback_failed" -eq 0 ]; then
    transaction_active=0
    return 0
  fi

  preserve_output_temporary_dir=1
  return 1
}

cleanup() {
  local status=$?
  set +e
  trap '' HUP INT TERM
  if [ "$transaction_active" -eq 1 ] && [ "$transaction_committed" -eq 0 ]; then
    if ! rollback_transaction; then
      printf 'release:build: rollback incomplete; recovery files preserved in %s\n' \
        "$output_temporary_dir" >&2
    fi
  fi
  if [ -n "$temporary_root" ]; then
    rm -rf -- "$temporary_root"
  fi
  if [ -n "$output_temporary_dir" ] && [ "$preserve_output_temporary_dir" -eq 0 ]; then
    rm -rf -- "$output_temporary_dir"
  fi
  return "$status"
}
trap cleanup EXIT
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM

temporary_root="$(mktemp -d "${TMPDIR:-/tmp}/magick-toolbox-release.XXXXXX")"
staging_root="$temporary_root/$PLUGIN_SLUG"
mkdir -p -- "$staging_root"

rsync -a --exclude-from="$DISTIGNORE" "$PROJECT_ROOT/" "$staging_root/"

for asset in "${required_assets[@]}"; do
  [ -f "$staging_root/$asset" ] || fail ".distignore excluded required asset: $asset"
done

included_symlink="$(find "$staging_root" -type l -print -quit)"
[ -z "$included_symlink" ] || fail "release staging contains a symlink: $included_symlink"

output_temporary_dir="$(mktemp -d "$output_dir/.magick-toolbox-release.XXXXXX")"
new_release_dir="$output_temporary_dir/new"
mkdir -p -- "$new_release_dir"
temporary_zip="$new_release_dir/$output_name"
temporary_sidecar="$temporary_zip.sha256"

(
  cd -- "$temporary_root"
  zip -q -r -X "$temporary_zip" "$PLUGIN_SLUG"
)

release_sha256="$(hash_file "$temporary_zip")"
printf '%s  %s\n' "$release_sha256" "$output_name" > "$temporary_sidecar"

"$VERIFY_SCRIPT" "$temporary_zip"

backup_zip="$output_temporary_dir/previous.zip"
backup_sidecar="$output_temporary_dir/previous.zip.sha256"

# A release is a ZIP/checksum pair. Ignore catchable termination signals only
# during the short rename transaction so they cannot leave a persistent
# half-committed pair. Synchronous failures still roll back the previous pair.
trap '' HUP INT TERM
transaction_active=1

if [ -e "$output_path" ]; then
  if ! mv -- "$output_path" "$backup_zip"; then
    rollback_transaction || true
    fail "could not back up existing ZIP: $output_path"
  fi
  had_zip=1
fi
if [ -e "$sidecar_path" ]; then
  if ! mv -- "$sidecar_path" "$backup_sidecar"; then
    rollback_transaction || true
    fail "could not back up existing sidecar: $sidecar_path"
  fi
  had_sidecar=1
fi

if ! mv -- "$temporary_zip" "$output_path"; then
  rollback_transaction || true
  fail "could not install release ZIP: $output_path"
fi
installed_zip=1

if ! mv -- "$temporary_sidecar" "$sidecar_path"; then
  rollback_transaction || true
  fail "could not install release checksum: $sidecar_path"
fi
installed_sidecar=1

transaction_committed=1
transaction_active=0
trap 'exit 129' HUP
trap 'exit 130' INT
trap 'exit 143' TERM

printf 'Release ZIP: %s\n' "$output_path"
printf 'Release SHA-256: %s\n' "$sidecar_path"
