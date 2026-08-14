<?php
defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Module_Metadata')) {
    class Npcink_Toolbox_Module_Metadata {

        private static $registry = null;

        public static function get_registry() {
            if (self::$registry === null) {
                self::$registry = require plugin_dir_path(__FILE__) . 'registry.php';
            }
            return self::$registry;
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
                    'label'     => isset($meta['label']) ? self::translate($meta['label']) : '',
                    'group'     => isset($meta['group']) ? self::translate($meta['group']) : '',
                    'feature_id' => isset($meta['feature_id']) ? $meta['feature_id'] : '',
                    'risk_tags' => isset($meta['risk_tags']) ? self::translate_list($meta['risk_tags']) : array(),
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

        private static function translate($value) {
            return is_string($value) && $value !== '' ? __($value, 'npcink-site-toolbox') : $value;
        }

        private static function translate_list($values) {
            if (!is_array($values)) {
                return array();
            }
            return array_map(array(__CLASS__, 'translate'), $values);
        }

        public static function reset_cache() {
            self::$registry = null;
        }
    }
}
