<?php

declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "This exporter must run from the command line.\n");
    exit(2);
}

$root = dirname(__DIR__, 2);

if (!defined('ABSPATH')) {
    define('ABSPATH', $root . '/');
}
if (!defined('NPCINK_SITE_TOOLBOX_OPTION_OPTIMIZE')) {
    define('NPCINK_SITE_TOOLBOX_OPTION_OPTIMIZE', 'npcink_site_toolbox_optimize');
}
if (!defined('NPCINK_SITE_TOOLBOX_OPTION_PAGE')) {
    define('NPCINK_SITE_TOOLBOX_OPTION_PAGE', 'npcink_site_toolbox_page');
}
if (!defined('NPCINK_SITE_TOOLBOX_OPTION_FUNCTION')) {
    define('NPCINK_SITE_TOOLBOX_OPTION_FUNCTION', 'npcink_site_toolbox_function');
}
if (!defined('NPCINK_SITE_TOOLBOX_OPTION_DOMESTIC')) {
    define('NPCINK_SITE_TOOLBOX_OPTION_DOMESTIC', 'npcink_site_toolbox_domestic');
}
if (!defined('NPCINK_SITE_TOOLBOX_OPTION_PERFORMANCE')) {
    define('NPCINK_SITE_TOOLBOX_OPTION_PERFORMANCE', 'npcink_site_toolbox_performance');
}

require_once $root . '/includes/class-npcink-toolbox-config-schema.php';

/**
 * Recursively sort JSON objects while preserving list order.
 *
 * @param mixed $value
 * @return mixed
 */
function npcink_site_toolbox_normalize_contract($value) {
    if (!is_array($value)) {
        return $value;
    }

    foreach ($value as $key => $item) {
        $value[$key] = npcink_site_toolbox_normalize_contract($item);
    }

    $is_list = empty($value) || array_keys($value) === range(0, count($value) - 1);
    if (!$is_list) {
        ksort($value, SORT_STRING);
    }

    return $value;
}

/**
 * Convert a Schema identifier to the stable exported TypeScript name.
 */
function npcink_site_toolbox_typescript_identifier($value) {
    $parts = preg_split('/[^A-Za-z0-9]+/', (string) $value);
    if (!is_array($parts)) {
        throw new RuntimeException("Unable to derive TypeScript identifier for {$value}");
    }

    $identifier = '';
    foreach ($parts as $part) {
        if ($part !== '') {
            $identifier .= ucfirst($part);
        }
    }
    if ($identifier === '' || preg_match('/^[0-9]/', $identifier)) {
        throw new RuntimeException("Invalid TypeScript identifier source: {$value}");
    }

    return $identifier;
}

/**
 * Derive a stable exported TypeScript name from the Schema path.
 */
function npcink_site_toolbox_setting_type_name($module_key, $sub_key = '') {
    $type_name = npcink_site_toolbox_typescript_identifier($module_key);
    if ($sub_key !== '') {
        $type_name .= npcink_site_toolbox_typescript_identifier($sub_key);
    }
    return $type_name;
}

/**
 * Render a property name safely without changing existing identifier output.
 */
function npcink_site_toolbox_typescript_property($name) {
    if (preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', (string) $name)) {
        return (string) $name;
    }

    $encoded = json_encode((string) $name, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    if (!is_string($encoded)) {
        throw new RuntimeException("Unable to encode TypeScript property: {$name}");
    }
    return $encoded;
}

/**
 * Map the supported Schema types exactly and reject unknown or incomplete types.
 */
function npcink_site_toolbox_typescript_type_for_contract($contract, $path) {
    if (!is_array($contract) || !isset($contract['type']) || !is_string($contract['type'])) {
        throw new RuntimeException("Missing Schema type at {$path}");
    }

    switch ($contract['type']) {
        case 'boolean':
            return 'boolean';
        case 'string':
            return 'string';
        case 'number':
            return 'number';
        case 'array':
            if (!isset($contract['items']) || !is_array($contract['items'])) {
                throw new RuntimeException("Missing array item Schema at {$path}");
            }
            $item_type = npcink_site_toolbox_typescript_type_for_contract($contract['items'], $path . '[]');
            return $item_type . '[]';
        default:
            throw new RuntimeException("Unsupported Schema type {$contract['type']} at {$path}");
    }
}

/**
 * Render one non-sensitive settings type from a Schema field collection.
 */
function npcink_site_toolbox_render_setting_type($type_name, $field_definitions, $path) {
    if (!is_array($field_definitions)) {
        throw new RuntimeException("Invalid field collection at {$path}");
    }

    $lines = array("export type {$type_name} = {");
    foreach ($field_definitions as $field_key => $field_def) {
        if ($field_key === '_option_key' || $field_key === '_flat') {
            continue;
        }
        if (!is_array($field_def)) {
            throw new RuntimeException("Invalid field Schema at {$path}.{$field_key}");
        }
        $field_type = npcink_site_toolbox_typescript_type_for_contract($field_def, $path . '.' . $field_key);
        if (!empty($field_def['sensitive'])) {
            continue;
        }

        $property = npcink_site_toolbox_typescript_property($field_key);
        $lines[] = "  {$property}: {$field_type};";
    }
    $lines[] = '};';

    return implode("\n", $lines);
}

/**
 * Collect sensitive paths without exporting their values or leaf types.
 */
function npcink_site_toolbox_collect_secret_paths($schema) {
    $paths = array();

    foreach ($schema as $module_key => $module_def) {
        if ($module_key === '_option_key' || $module_key === '_flat') {
            continue;
        }
        if (!is_array($module_def)) {
            throw new RuntimeException("Invalid module Schema at {$module_key}");
        }

        if (!empty($module_def['_flat'])) {
            foreach ($module_def as $field_key => $field_def) {
                if ($field_key === '_option_key' || $field_key === '_flat') {
                    continue;
                }
                if (!is_array($field_def)) {
                    throw new RuntimeException("Invalid field Schema at {$module_key}.{$field_key}");
                }
                if (!empty($field_def['sensitive'])) {
                    $paths[] = $module_key . '.' . $field_key;
                }
            }
            continue;
        }

        foreach ($module_def as $sub_key => $sub_def) {
            if ($sub_key === '_option_key' || $sub_key === '_flat') {
                continue;
            }
            if (!is_array($sub_def)) {
                throw new RuntimeException("Invalid submodule Schema at {$module_key}.{$sub_key}");
            }
            foreach ($sub_def as $field_key => $field_def) {
                if ($field_key === '_option_key' || $field_key === '_flat') {
                    continue;
                }
                if (!is_array($field_def)) {
                    throw new RuntimeException("Invalid field Schema at {$module_key}.{$sub_key}.{$field_key}");
                }
                if (!empty($field_def['sensitive'])) {
                    $paths[] = $module_key . '.' . $sub_key . '.' . $field_key;
                }
            }
        }
    }

    return $paths;
}

/**
 * Generate all established settings types and the browser-safe Option tree.
 */
function npcink_site_toolbox_render_admin_settings_types($schema) {
    if (!is_array($schema)) {
        throw new RuntimeException('Settings Schema must be an array');
    }

    $secret_paths = npcink_site_toolbox_collect_secret_paths($schema);
    $lines = array(
        '// This file is generated by tests/support/export-admin-settings-contract.php.',
        '// Do not edit it by hand.',
        '',
        'export const SECRET_PATHS = [',
    );
    foreach ($secret_paths as $path) {
        $encoded = json_encode($path, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($encoded)) {
            throw new RuntimeException("Unable to encode secret path: {$path}");
        }
        $lines[] = "  {$encoded},";
    }
    $lines[] = '] as const;';
    $lines[] = '';
    $lines[] = 'export type SecretPath = (typeof SECRET_PATHS)[number];';

    $module_type_references = array();
    foreach ($schema as $module_key => $module_def) {
        if ($module_key === '_option_key' || $module_key === '_flat') {
            continue;
        }
        if (!is_array($module_def)) {
            throw new RuntimeException("Invalid module Schema at {$module_key}");
        }

        if (!empty($module_def['_flat'])) {
            $type_name = npcink_site_toolbox_setting_type_name($module_key);
            $module_type_references[$module_key] = $type_name;
            $lines[] = '';
            $lines[] = npcink_site_toolbox_render_setting_type($type_name, $module_def, $module_key);
            continue;
        }

        $module_type_references[$module_key] = array();
        foreach ($module_def as $sub_key => $sub_def) {
            if ($sub_key === '_option_key' || $sub_key === '_flat') {
                continue;
            }
            if (!is_array($sub_def)) {
                throw new RuntimeException("Invalid submodule Schema at {$module_key}.{$sub_key}");
            }
            $type_name = npcink_site_toolbox_setting_type_name($module_key, $sub_key);
            $module_type_references[$module_key][$sub_key] = $type_name;
            $lines[] = '';
            $lines[] = npcink_site_toolbox_render_setting_type($type_name, $sub_def, $module_key . '.' . $sub_key);
        }
    }

    $lines[] = '';
    $lines[] = 'export type Option = {';
    $lines[] = '  [key: string]: unknown;';
    foreach ($module_type_references as $module_key => $module_reference) {
        $module_property = npcink_site_toolbox_typescript_property($module_key);
        if (is_string($module_reference)) {
            $lines[] = "  {$module_property}: {$module_reference};";
            continue;
        }

        $lines[] = "  {$module_property}: {";
        foreach ($module_reference as $sub_key => $type_name) {
            $sub_property = npcink_site_toolbox_typescript_property($sub_key);
            $lines[] = "    {$sub_property}: {$type_name};";
        }
        $lines[] = '  };';
    }
    $lines[] = '};';

    return implode("\n", $lines) . "\n";
}

/**
 * Atomically replace every target after all temporary files are ready.
 */
function npcink_site_toolbox_atomic_replace_generated_files($files) {
    $temporary_files = array();
    $backup_files = array();
    $installed_targets = array();
    $original_states = array();
    $orphaned_paths = array();

    try {
        foreach ($files as $target => $contents) {
            $directory = dirname($target);
            if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
                throw new RuntimeException("Unable to create generated directory: {$directory}");
            }
            $temporary = tempnam($directory, '.settings-generated-');
            if (!is_string($temporary)) {
                throw new RuntimeException("Unable to create temporary file for {$target}");
            }
            $temporary_files[$target] = $temporary;

            $written = file_put_contents($temporary, $contents);
            if ($written !== strlen($contents) || !chmod($temporary, 0644)) {
                throw new RuntimeException("Unable to prepare generated file: {$target}");
            }
        }

        foreach ($temporary_files as $target => $temporary) {
            if (is_file($target) && !is_link($target)) {
                $backup = tempnam(dirname($target), '.settings-backup-');
                if (!is_string($backup)) {
                    throw new RuntimeException("Unable to create backup path for {$target}");
                }
                $orphaned_paths[$backup] = true;
                if (!@unlink($backup)) {
                    throw new RuntimeException("Unable to prepare backup path for {$target}");
                }
                unset($orphaned_paths[$backup]);

                if (!@rename($target, $backup)) {
                    throw new RuntimeException("Unable to back up generated file: {$target}");
                }
                $backup_files[$target] = $backup;
                $original_states[$target] = 'backed-up';
                continue;
            }

            if (file_exists($target) || is_link($target)) {
                $original_states[$target] = 'abnormal';
            } else {
                $original_states[$target] = 'missing';
            }
        }

        foreach ($temporary_files as $target => $temporary) {
            if ($original_states[$target] === 'abnormal') {
                throw new RuntimeException("Unable to atomically replace non-file target: {$target}");
            }
            if (file_exists($target) || is_link($target)) {
                throw new RuntimeException("Generated target changed during replacement: {$target}");
            }
            if (!@rename($temporary, $target)) {
                throw new RuntimeException("Unable to atomically replace generated file: {$target}");
            }
            unset($temporary_files[$target]);
            $installed_targets[] = $target;
        }

    } catch (Throwable $error) {
        $rollback_errors = array();

        foreach (array_reverse($installed_targets) as $target) {
            if ($original_states[$target] === 'backed-up') {
                continue;
            }
            if (!file_exists($target) && !is_link($target)) {
                continue;
            }
            if (!is_file($target) || is_link($target) || !@unlink($target)) {
                $rollback_errors[] = "unable to remove newly installed target {$target}";
            }
        }

        foreach ($backup_files as $target => $backup) {
            if (file_exists($target) || is_link($target)) {
                if (!is_file($target) || is_link($target) || !@unlink($target)) {
                    $rollback_errors[] = "unable to clear target before restoring {$target}";
                    continue;
                }
            }
            if (!@rename($backup, $target)) {
                $rollback_errors[] = "unable to restore backup {$backup} to {$target}";
                continue;
            }
            unset($backup_files[$target]);
        }

        foreach ($temporary_files as $temporary) {
            if ((file_exists($temporary) || is_link($temporary)) && !@unlink($temporary)) {
                $rollback_errors[] = "unable to remove temporary file {$temporary}";
            }
        }
        foreach (array_keys($orphaned_paths) as $orphaned_path) {
            if ((file_exists($orphaned_path) || is_link($orphaned_path)) && !@unlink($orphaned_path)) {
                $rollback_errors[] = "unable to remove backup placeholder {$orphaned_path}";
            }
        }
        foreach ($backup_files as $target => $backup) {
            $rollback_errors[] = "backup remains at {$backup} for {$target}";
        }

        if (!empty($rollback_errors)) {
            throw new RuntimeException(
                'Atomic generated-file replacement failed: ' . $error->getMessage()
                . '; rollback failed: ' . implode('; ', array_unique($rollback_errors)),
                0,
                $error
            );
        }

        throw $error;
    }

    $cleanup_errors = array();
    foreach ($backup_files as $target => $backup) {
        if ((file_exists($backup) || is_link($backup)) && !@unlink($backup)) {
            $cleanup_errors[] = "unable to remove backup {$backup} for {$target}";
        }
    }
    foreach (array_keys($orphaned_paths) as $orphaned_path) {
        if ((file_exists($orphaned_path) || is_link($orphaned_path)) && !@unlink($orphaned_path)) {
            $cleanup_errors[] = "unable to remove backup placeholder {$orphaned_path}";
        }
    }
    if (!empty($cleanup_errors)) {
        throw new RuntimeException(
            'Generated files were installed, but cleanup failed: ' . implode('; ', $cleanup_errors)
        );
    }
}

/**
 * Execute the narrow two-artifact generator/check command.
 */
function npcink_site_toolbox_export_admin_settings_contract($root, $arguments) {
    $check = $arguments === array('--check');
    if ($arguments !== array() && !$check) {
        fwrite(STDERR, "Usage: php tests/support/export-admin-settings-contract.php [--check]\n");
        return 2;
    }

    try {
        $contract = npcink_site_toolbox_normalize_contract(Npcink_Toolbox_Config_Schema::get_admin_settings_contract());
        $json = json_encode($contract, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        if (!is_string($json)) {
            throw new RuntimeException('Unable to encode admin settings contract: ' . json_last_error_msg());
        }
        $json .= "\n";

        $typescript = npcink_site_toolbox_render_admin_settings_types(Npcink_Toolbox_Config_Schema::get_schema());
        $files = array(
            $root . '/vite/admin/src/generated/settings-contract.json' => $json,
            $root . '/vite/admin/src/generated/settings-types.ts' => $typescript,
        );

        if ($check) {
            $stale = array();
            foreach ($files as $target => $expected) {
                $current = is_file($target) ? file_get_contents($target) : false;
                if (!is_string($current) || !hash_equals($expected, $current)) {
                    $stale[] = str_replace($root . '/', '', $target);
                }
            }
            if (!empty($stale)) {
                fwrite(STDERR, 'Generated admin settings artifacts are stale: ' . implode(', ', $stale) . ".\n");
                fwrite(STDERR, "Run composer settings-contract:generate.\n");
                return 1;
            }
            fwrite(STDOUT, "Admin settings contract and types are current.\n");
            return 0;
        }

        npcink_site_toolbox_atomic_replace_generated_files($files);
        fwrite(STDOUT, "Generated vite/admin/src/generated/settings-contract.json.\n");
        fwrite(STDOUT, "Generated vite/admin/src/generated/settings-types.ts.\n");
        return 0;
    } catch (Throwable $error) {
        fwrite(STDERR, $error->getMessage() . "\n");
        return 1;
    }
}

if (isset($_SERVER['SCRIPT_FILENAME']) && realpath($_SERVER['SCRIPT_FILENAME']) === __FILE__) {
    exit(npcink_site_toolbox_export_admin_settings_contract($root, array_slice($argv, 1)));
}
