<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This exporter must run from the command line.\n");
    exit(2);
}

$root = dirname(__DIR__, 2);
$target = $root . '/vite/admin/src/generated/settings-contract.json';
$arguments = array_slice($argv, 1);
$check = $arguments === array('--check');

if ($arguments !== array() && !$check) {
    fwrite(STDERR, "Usage: php tests/support/export-admin-settings-contract.php [--check]\n");
    exit(2);
}

define('ABSPATH', $root . '/');
define('MAGICK_MIXTURE_OPTION_OPTIMIZE', 'Magick_ToolBox_Option_Optimize');
define('MAGICK_MIXTURE_OPTION_PAGE', 'Magick_ToolBox_Option_Page');
define('MAGICK_MIXTURE_OPTION_FUNCTION', 'Magick_ToolBox_Option_Function');
define('MAGICK_MIXTURE_OPTION_DOMESTIC', 'Magick_ToolBox_Option_Domestic');
define('MAGICK_MIXTURE_OPTION_PERFORMANCE', 'Magick_ToolBox_Option_Performance');

require_once $root . '/includes/class-mabox-config-schema.php';

/**
 * Recursively sort JSON objects while preserving list order.
 *
 * @param mixed $value
 * @return mixed
 */
function mabox_normalize_contract($value) {
    if (!is_array($value)) {
        return $value;
    }

    foreach ($value as $key => $item) {
        $value[$key] = mabox_normalize_contract($item);
    }

    $is_list = empty($value) || array_keys($value) === range(0, count($value) - 1);
    if (!$is_list) {
        ksort($value, SORT_STRING);
    }

    return $value;
}

$contract = mabox_normalize_contract(MaBox_Config_Schema::get_admin_settings_contract());
$json = json_encode($contract, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (!is_string($json)) {
    fwrite(STDERR, 'Unable to encode admin settings contract: ' . json_last_error_msg() . "\n");
    exit(1);
}
$json .= "\n";

if ($check) {
    $current = is_file($target) ? file_get_contents($target) : false;
    if (!is_string($current) || !hash_equals($json, $current)) {
        fwrite(STDERR, "Admin settings contract is stale. Run composer settings-contract:generate.\n");
        exit(1);
    }
    fwrite(STDOUT, "Admin settings contract is current.\n");
    exit(0);
}

$directory = dirname($target);
if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
    fwrite(STDERR, "Unable to create generated contract directory.\n");
    exit(1);
}
$temporary = tempnam($directory, '.settings-contract-');
if (!is_string($temporary)) {
    fwrite(STDERR, "Unable to create temporary contract file.\n");
    exit(1);
}

$written = file_put_contents($temporary, $json);
if ($written !== strlen($json) || !chmod($temporary, 0644) || !rename($temporary, $target)) {
    @unlink($temporary);
    fwrite(STDERR, "Unable to atomically replace admin settings contract.\n");
    exit(1);
}

fwrite(STDOUT, "Generated vite/admin/src/generated/settings-contract.json.\n");
