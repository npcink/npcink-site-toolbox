<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;
/**
 * 模块加载器
 *
 * 基于注册表动态加载模块，替代硬编码的 build_active_modules_map + activate_module。
 *
 * @since 2.1.0
 */

// 加载模块接口契约
require_once plugin_dir_path(dirname(__DIR__)) . 'includes/interface-npcink-toolbox-module.php';

// 加载元数据聚合层
require_once plugin_dir_path(__FILE__) . 'metadata.php';

if (!class_exists('Npcink_Toolbox_Module_Loader')) {
    class Npcink_Toolbox_Module_Loader {

        private static $registry = null;

        public static function get_registry() {
            if (self::$registry === null) {
                self::$registry = Npcink_Toolbox_Module_Metadata::get_registry();
            }
            return self::$registry;
        }

        public static function get_active_modules($config) {
            $registry = self::get_registry();
            $active   = array();

            foreach ($registry as $module_id => $meta) {
                if (!empty($meta['always_load'])) {
                    $active[] = $module_id;
                    continue;
                }

                if (!empty($meta['mobile_only']) && !wp_is_mobile()) {
                    continue;
                }

                if (!empty($meta['theme_requirement'])) {
                    if (!Npcink_Toolbox_Tool::theme_active($meta['theme_requirement'])) {
                        continue;
                    }
                }

                $activation_paths = self::get_activation_paths($meta);
                $is_active        = false;

                foreach ($activation_paths as $activation_path) {
                    $value = self::get_nested_value($config, $activation_path);
                    if (self::is_activation_value_enabled($value)) {
                        $is_active = true;
                        break;
                    }
                }

                if ($is_active) {
                    $active[] = $module_id;
                }
            }

            return $active;
        }

        public static function load_module($module_id, $config) {
            $registry = self::get_registry();

            if (!isset($registry[ $module_id ])) {
                return;
            }

            $meta = $registry[ $module_id ];

            // Scope 过滤：根据当前请求上下文跳过不相关的模块
            if (!empty($meta['scope'])) {
                $is_admin = is_admin();
                if ($meta['scope'] === 'admin' && !$is_admin) {
                    return; // 仅后台模块，当前是前台
                }
                if ($meta['scope'] === 'frontend' && $is_admin) {
                    return; // 仅前台模块，当前是后台
                }
                // 'both' 或不设置：前后都加载
            }

            $file = plugin_dir_path(__FILE__) . '../partials/' . $meta['file'];

            if (!file_exists($file)) {
                return;
            }

            require_once $file;

            if (!class_exists($meta['class'])) {
                return;
            }

            // 验证模块是否实现接口契约。不合规模块必须停止加载。
            $class = $meta['class'];
            if (!is_subclass_of($class, 'Npcink_Toolbox_Module_Interface')) {
                if (class_exists('Npcink_Toolbox_Audit_Logger')) {
                    Npcink_Toolbox_Audit_Logger::log('error', 'config', "Module {$class} does not implement Npcink_Toolbox_Module_Interface");
                }
                return;
            }

            if (!is_callable(array($class, 'run'))) {
                if (class_exists('Npcink_Toolbox_Audit_Logger')) {
                    Npcink_Toolbox_Audit_Logger::log('error', 'config', "Module {$class} has no callable run() method");
                }
                return;
            }

            $module_config = array();
            if (!empty($meta['config_path'])) {
                $resolved_config = self::get_nested_value($config, $meta['config_path']);
                if (is_array($resolved_config)) {
                    $module_config = $resolved_config;
                }
            }

            call_user_func(array($class, 'run'), $module_config);
        }

        public static function get_module_meta($module_id) {
            $registry = self::get_registry();
            return isset($registry[ $module_id ]) ? $registry[ $module_id ] : null;
        }

        public static function get_modules_by_category($category) {
            $registry = self::get_registry();
            $modules  = array();

            foreach ($registry as $module_id => $meta) {
                if ($meta['category'] === $category) {
                    $modules[ $module_id ] = $meta;
                }
            }

            return $modules;
        }

        public static function get_all_module_ids() {
            return array_keys(self::get_registry());
        }

        /**
         * 获取模块分层信息
         *
         * @return array tier => module_ids 映射
         * @since 2.4.0
         */
        public static function get_tiers() {
            static $tiers = null;
            if ($tiers === null) {
                $tiers = require plugin_dir_path(__FILE__) . 'tiers.php';
            }
            return $tiers;
        }

        /**
         * 获取指定模块的层级
         *
         * @param string $module_id
         * @return string
         * @since 2.4.0
         */
        public static function get_module_tier($module_id) {
            $tiers = self::get_tiers();
            foreach ($tiers as $tier => $modules) {
                if (in_array($module_id, $modules, true)) {
                    return $tier;
                }
            }
            return 'advanced'; // 默认层级
        }

        /**
         * 将单路径和复合模块激活契约归一化为 ANY-OF 路径列表。
         *
         * activation_paths 一旦声明即为权威契约。空列表、关联数组、
         * 重复或非法路径均失败关闭，不回退到 option_key。
         */
        private static function get_activation_paths($meta) {
            if (array_key_exists('activation_paths', $meta)) {
                $paths = $meta['activation_paths'];
                if (!is_array($paths) || empty($paths)) {
                    return array();
                }

                if (array_keys($paths) !== range(0, count($paths) - 1)) {
                    return array();
                }

                foreach ($paths as $path) {
                    if (!self::is_valid_activation_path($path)) {
                        return array();
                    }
                }

                if (count(array_unique($paths, SORT_STRING)) !== count($paths)) {
                    return array();
                }

                if (
                    !isset($meta['option_key'])
                    || !is_string($meta['option_key'])
                    || $paths[0] !== $meta['option_key']
                ) {
                    return array();
                }

                return $paths;
            }

            if (
                !isset($meta['option_key'])
                || !self::is_valid_activation_path($meta['option_key'])
            ) {
                return array();
            }

            return array($meta['option_key']);
        }

        private static function is_valid_activation_path($path) {
            return is_string($path)
                && preg_match('/^[A-Za-z0-9_-]+(?:\.[A-Za-z0-9_-]+)*$/D', $path) === 1;
        }

        private static function is_activation_value_enabled($value) {
            if (empty($value)) {
                return false;
            }

            return !is_string($value) || $value !== 'false';
        }

        private static function get_nested_value($data, $path) {
            $keys    = explode('.', $path);
            $current = $data;

            foreach ($keys as $key) {
                if (is_array($current) && isset($current[ $key ])) {
                    $current = $current[ $key ];
                } elseif (is_object($current) && isset($current->$key)) {
                    $current = $current->$key;
                } else {
                    return null;
                }
            }

            return $current;
        }
    }
}
