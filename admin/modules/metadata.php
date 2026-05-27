<?php
defined('ABSPATH') || exit;

if (!class_exists('MaBox_Module_Metadata')) {
    class MaBox_Module_Metadata {

        private static $merged = null;

        private static $required_keys = array('class', 'file', 'option_key', 'category', 'scope');

        public static function get_registry() {
            if (self::$merged === null) {
                self::$merged = self::build_merged_registry();
            }
            return self::$merged;
        }

        public static function get_module($id) {
            $registry = self::get_registry();
            return isset($registry[$id]) ? $registry[$id] : null;
        }

        public static function get_ui_metadata() {
            $registry = self::get_registry();
            $ui = array();

            foreach ($registry as $module_id => $meta) {
                $entry = array(
                    'id'        => $module_id,
                    'category'  => $meta['category'],
                    'scope'     => $meta['scope'],
                    'label'     => isset($meta['label']) ? $meta['label'] : '',
                    'group'     => isset($meta['group']) ? $meta['group'] : '',
                    'feature_id' => isset($meta['feature_id']) ? $meta['feature_id'] : '',
                    'risk_tags' => isset($meta['risk_tags']) ? $meta['risk_tags'] : array(),
                    'risk'      => isset($meta['risk']) ? $meta['risk'] : array('level' => 'none'),
                    'depends_on' => isset($meta['depends_on']) ? $meta['depends_on'] : array(),
                    'preset_tags' => isset($meta['preset_tags']) ? $meta['preset_tags'] : array(),
                );

                if (!empty($meta['always_load'])) {
                    $entry['always_load'] = true;
                }
                if (!empty($meta['mobile_only'])) {
                    $entry['mobile_only'] = true;
                }

                $ui[$module_id] = $entry;
            }

            return $ui;
        }

        public static function reset_cache() {
            self::$merged = null;
        }

        private static function build_merged_registry() {
            $legacy = self::load_legacy();
            $manifests = self::scan_meta_files();

            if (empty($manifests)) {
                return $legacy;
            }

            return self::merge($legacy, $manifests);
        }

        private static function load_legacy() {
            return require plugin_dir_path(__FILE__) . 'registry.php';
        }

        private static function scan_meta_files() {
            $partials_dir = dirname(dirname(__FILE__)) . '/partials/';
            $manifests = array();

            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($partials_dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );

            foreach ($iterator as $fileInfo) {
                if ($fileInfo->isFile() && $fileInfo->getExtension() === 'php') {
                    $basename = $fileInfo->getBasename('.php');
                    if (substr($basename, -5) === '.meta') {
                        $module_slug = substr($basename, 0, -5);
                        $meta = require $fileInfo->getPathname();

                        if (!is_array($meta)) {
                            error_log("[MaBox] module.meta.php at {$fileInfo->getPathname()} did not return an array");
                            continue;
                        }

                        $missing = array_diff(self::$required_keys, array_keys($meta));
                        if (!empty($missing)) {
                            error_log("[MaBox] module.meta.php for '{$module_slug}' missing required keys: " . implode(', ', $missing));
                            continue;
                        }

                        $manifests[$module_slug] = $meta;
                    }
                }
            }

            return $manifests;
        }

        private static function merge($legacy, $manifests) {
            $result = $legacy;

            foreach ($manifests as $module_slug => $meta) {
                $module_id = self::find_module_id_by_file($legacy, $meta['file']);

                if ($module_id !== null) {
                    $result[$module_id] = array_merge($legacy[$module_id], $meta);
                } else {
                    $result[$module_slug] = $meta;
                }
            }

            return $result;
        }

        private static function find_module_id_by_file($legacy, $file) {
            foreach ($legacy as $module_id => $meta) {
                if (isset($meta['file']) && $meta['file'] === $file) {
                    return $module_id;
                }
            }
            return null;
        }
    }
}