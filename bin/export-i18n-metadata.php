<?php

declare(strict_types=1);

/**
 * Emit gettext calls for metadata that is intentionally stored as plain
 * strings in the runtime/config contracts. The generated stream is consumed
 * by bin/make-pot.sh; it is not shipped or executed by WordPress.
 */

$root = dirname(__DIR__);
if (!defined('ABSPATH')) {
    define('ABSPATH', $root . '/');
}
if (!function_exists('__')) {
    function __($text, $domain = 'default') {
        return $text;
    }
}

$messages = array();
$add = static function ($value) use (&$messages): void {
    if (
        is_string($value)
        && $value !== ''
        && preg_match('/[\x{4e00}-\x{9fff}]/u', $value)
        && !preg_match('~https?://~i', $value)
    ) {
        $messages[$value] = true;
    }
};
$walk = static function ($value) use (&$walk, $add): void {
    if (is_array($value)) {
        foreach ($value as $item) {
            $walk($item);
        }
        return;
    }
    $add($value);
};

$registry = require $root . '/admin/modules/registry.php';
foreach ($registry as $meta) {
    foreach (array('label', 'group', 'risk_tags', 'risk') as $key) {
        if (isset($meta[$key])) {
            $walk($meta[$key]);
        }
    }
}

require_once $root . '/admin/partials/privacy/index.php';
if (class_exists('Npcink_Toolbox_Privacy')) {
    $walk(Npcink_Toolbox_Privacy::get_privacy_data());
}

$contract_path = $root . '/vite/admin/src/generated/settings-contract.json';
if (is_file($contract_path)) {
    $contract = json_decode((string) file_get_contents($contract_path), true);
    if (is_array($contract)) {
        foreach (array('searchIndex', 'uiSchema') as $key) {
            if (isset($contract[$key])) {
                $walk($contract[$key]);
            }
        }
    }
}

$source_root = $root . '/vite/admin/src';
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($source_root));
foreach ($iterator as $file) {
    if (!$file->isFile() || !preg_match('/\.(?:ts|tsx)$/', $file->getFilename())) {
        continue;
    }
    if (preg_match('/\.test\.tsx?$/', $file->getFilename())) {
        continue;
    }
    $source = (string) file_get_contents($file->getPathname());
    foreach (array('/"([^"\r\n]*[\x{4e00}-\x{9fff}][^"\r\n]*)"/u', "/'([^'\r\n]*[\x{4e00}-\x{9fff}][^'\r\n]*)'/u", '/`([^`]*[\x{4e00}-\x{9fff}][^`]*)`/u') as $pattern) {
        if (preg_match_all($pattern, $source, $matches)) {
            foreach ($matches[1] as $message) {
                $add(str_replace(array('\\"', "\\'", '\\`'), array('"', "'", '`'), $message));
            }
        }
    }
}

foreach (array_keys($messages) as $message) {
    echo '__(' . json_encode($message, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ', "npcink-site-toolbox");' . PHP_EOL;
}
