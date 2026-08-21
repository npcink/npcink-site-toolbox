#!/usr/bin/env bash

set -euo pipefail

eval_dir="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
project_root="$(cd "$eval_dir/../.." && pwd)"
compose_file="$eval_dir/compose.yaml"
compose_project="${NPCINK_AI_EVAL_PROJECT:-npcink-site-toolbox-ai-eval}"
eval_port="${NPCINK_AI_EVAL_PORT:-8897}"
eval_token="${NPCINK_AI_EVAL_TOKEN:-npcink-ai-eval-local-only}"
provider_path="${AI_PROVIDER_FOR_DEEPSEEK_PATH:-${HOME:-}/Local Sites/magick-ai/app/public/wp-content/plugins/ai-provider-for-deepseek}"
eval_env_file="${NPCINK_EVAL_ENV_FILE:-$project_root/../npcink-eval-lab/.env.evaluation.local}"

die() {
  echo "ERROR: $*" >&2
  exit 1
}

load_deepseek_key() {
  if [[ -z "${DEEPSEEK_API_KEY:-}" && -f "$eval_env_file" ]]; then
    if [[ "$-" == *a* ]]; then
      eval_had_allexport=1
    else
      eval_had_allexport=0
      set -a
    fi
    # shellcheck disable=SC1090
    source "$eval_env_file"
    if [[ "$eval_had_allexport" == "0" ]]; then
      set +a
    fi
    unset eval_had_allexport
    DEEPSEEK_API_KEY="${REC_EVAL_DEEPSEEK_API_KEY:-}"
  fi
  export DEEPSEEK_API_KEY="${DEEPSEEK_API_KEY:-}"
}

prepare_environment() {
  [[ -d "$provider_path" ]] || die "DeepSeek Provider directory not found: $provider_path"
  [[ -f "$provider_path/ai-provider-for-deepseek.php" ]] || die "DeepSeek Provider entrypoint is missing."
  export AI_PROVIDER_FOR_DEEPSEEK_PATH="$provider_path"
  export NPCINK_AI_EVAL_PORT="$eval_port"
  export NPCINK_AI_EVAL_TOKEN="$eval_token"
  mkdir -p "$eval_dir/generated"
}

compose() {
  docker compose --project-name "$compose_project" --file "$compose_file" "$@"
}

run_cli() {
  compose run --rm --no-deps cli wp "$@"
}

wait_for_wordpress_files() {
  local attempt
  for attempt in $(seq 1 60); do
    if compose exec -T wordpress test -f /var/www/html/wp-config.php; then
      return 0
    fi
    sleep 2
  done
  die "WordPress files were not initialized in time."
}

wait_for_http() {
  local attempt
  for attempt in $(seq 1 60); do
    if curl --fail --silent "http://127.0.0.1:${eval_port}/wp-login.php" >/dev/null; then
      return 0
    fi
    sleep 2
  done
  die "WordPress HTTP endpoint did not become ready."
}

ensure_key() {
  [[ -n "${DEEPSEEK_API_KEY:-}" ]] || die "Set DEEPSEEK_API_KEY or provide REC_EVAL_DEEPSEEK_API_KEY in $eval_env_file."
}

ensure_stack() {
  export NPCINK_AI_EVAL_CASE="${NPCINK_AI_EVAL_CASE:-baseline}"
  compose config --quiet
  compose up --detach database wordpress
  wait_for_wordpress_files
  wait_for_http

  if ! run_cli core is-installed >/dev/null 2>&1; then
    run_cli core install \
      --url="http://127.0.0.1:${eval_port}" \
      --title="Npcink AI Diagnostics Eval" \
      --admin_user=admin \
      --admin_password=npcink-ai-eval-only \
      --admin_email=admin@example.test \
      --skip-email
  fi

  run_cli plugin activate ai-provider-for-deepseek npcink-site-toolbox >/dev/null

  local wp_version
  wp_version="$(run_cli core version)"
  [[ "$wp_version" == "7.0.2" ]] || die "Expected WordPress 7.0.2, got $wp_version."

  run_cli eval '
    $registry = \WordPress\AiClient\AiClient::defaultRegistry();
    if ( ! $registry->hasProvider( "deepseek" ) ) {
        fwrite( STDERR, "DeepSeek provider was not registered.\n" );
        exit( 1 );
    }
    echo "DeepSeek provider registered.\n";
  ' >/dev/null
}

switch_case() {
  local case_id="$1"
  export NPCINK_AI_EVAL_CASE="$case_id"
  compose up --detach --force-recreate wordpress >/dev/null || return 1
  wait_for_wordpress_files || return 1
  wait_for_http || return 1
}

result_path() {
  local case_id="$1"
  local sample_label="${2:-}"
  if [[ -n "$sample_label" ]]; then
    echo "$eval_dir/generated/${case_id}-${sample_label}.json"
  else
    echo "$eval_dir/generated/${case_id}.json"
  fi
}

result_passed() {
  local path="$1"
  RESULT_PATH="$path" php -r '
    $data = json_decode((string) file_get_contents(getenv("RESULT_PATH")), true);
    exit(!empty($data["assessment"]["passed"]) ? 0 : 1);
  '
}

assert_key_absent() {
  local path="$1"
  RESULT_PATH="$path" php -r '
    $path = getenv("RESULT_PATH");
    $key = getenv("DEEPSEEK_API_KEY");
    $contents = (string) file_get_contents($path);
    if ($key !== false && $key !== "" && strpos($contents, $key) !== false) {
        fwrite(STDERR, "DeepSeek credential leaked into result artifact.\n");
        exit(1);
    }
  '
}

print_summary() {
  local path="$1"
  RESULT_PATH="$path" php -r '
    $data = json_decode((string) file_get_contents(getenv("RESULT_PATH")), true);
    $assessment = $data["assessment"] ?? array();
    printf(
        "%s: %s | provider=%s | %.2fs\n",
        $data["case"]["id"] ?? "unknown",
        !empty($assessment["passed"]) ? "PASS" : "FAIL",
        $data["analysis"]["provider"]["id"] ?? "none",
        (float) ($data["elapsed_seconds"] ?? 0)
    );
    foreach (($assessment["checks"] ?? array()) as $check => $value) {
        printf("  - %s: %s\n", $check, $value ? "pass" : "fail");
    }
    foreach (($assessment["safety_flags"] ?? array()) as $flag) {
        printf("  - safety flag: %s\n", $flag);
    }
  '
}

execute_case() {
  local case_id="$1"
  local sample_label="${2:-}"
  local path
  local temp_path
  local container_path
  path="$(result_path "$case_id" "$sample_label")"
  temp_path="${path}.tmp"
  container_path="/var/www/html/wp-content/ai-eval-results/${case_id}.json"

  switch_case "$case_id" || return 1
  compose exec -T wordpress rm -f "$container_path" || return 1
  if ! curl \
    --fail \
    --silent \
    --show-error \
    --max-time 180 \
    --header "X-Npcink-Eval-Token: ${eval_token}" \
    "http://127.0.0.1:${eval_port}/?npcink_ai_eval_case=${case_id}" \
    >/dev/null; then
    echo "AI diagnostics request failed for case: $case_id" >&2
    return 1
  fi
  if ! compose exec -T wordpress cat "$container_path" >"$temp_path"; then
    rm -f "$temp_path"
    echo "Case result was not created: $container_path" >&2
    return 1
  fi
  mv "$temp_path" "$path"
  assert_key_absent "$path" || return 1
  if [[ -n "$sample_label" ]]; then
    echo "Sample: $sample_label"
  fi
  print_summary "$path" || return 1
  result_passed "$path"
}

run_all_cases() {
  local failures=0
  local case_id
  ensure_key
  ensure_stack

  for case_id in low-memory debug-display-mismatch insufficient-evidence; do
    if ! execute_case "$case_id"; then
      failures=$((failures + 1))
    fi
  done

  if [[ "$failures" -gt 0 ]]; then
    die "$failures AI diagnostics case(s) did not meet the expected evidence gate."
  fi
}

run_case_samples() {
  local case_id="$1"
  local sample_count="$2"
  local failures=0
  local sample_number

  [[ "$sample_count" =~ ^([1-9]|10)$ ]] || die "Sample count must be between 1 and 10."
  ensure_key
  ensure_stack

  for sample_number in $(seq 1 "$sample_count"); do
    if ! execute_case "$case_id" "sample-${sample_number}"; then
      failures=$((failures + 1))
    fi
  done

  if [[ "$failures" -gt 0 ]]; then
    die "$failures of $sample_count sample(s) did not meet the expected evidence gate."
  fi
}

usage() {
  echo "Usage: $0 {config|up|case <id>|samples <id> [count]|all|status|down|destroy}"
  echo "Cases: low-memory, debug-display-mismatch, insufficient-evidence"
}

command="${1:-}"
prepare_environment
load_deepseek_key

case "$command" in
  config)
    compose config --quiet
    echo "Compose configuration is valid."
    ;;
  up)
    ensure_key
    ensure_stack
    echo "WordPress eval site: http://127.0.0.1:${eval_port}"
    ;;
  case)
    case_id="${2:-}"
    case "$case_id" in
      low-memory|debug-display-mismatch|insufficient-evidence) ;;
      *) die "Unknown case: $case_id" ;;
    esac
    ensure_key
    ensure_stack
    execute_case "$case_id"
    ;;
  samples)
    case_id="${2:-}"
    case "$case_id" in
      low-memory|debug-display-mismatch|insufficient-evidence) ;;
      *) die "Unknown case: $case_id" ;;
    esac
    run_case_samples "$case_id" "${3:-3}"
    ;;
  all)
    run_all_cases
    ;;
  status)
    compose ps
    ;;
  down)
    compose down --remove-orphans
    ;;
  destroy)
    compose down --volumes --remove-orphans
    ;;
  *)
    usage
    exit 1
    ;;
esac
