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
require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'includes/interface-mabox-module.php';

// 加载元数据聚合层
require_once plugin_dir_path(__FILE__) . 'metadata.php';

if (!class_exists('MaBox_Module_Loader')) {
    class MaBox_Module_Loader {

        private static $registry = null;

        public static function get_registry() {
            if (self::$registry === null) {
                self::$registry = MaBox_Module_Metadata::get_registry();
            }
            return self::$registry;
        }

        public static function get_active_modules($config) {
            $registry = self::get_registry();
            $active = array();

            foreach ($registry as $module_id => $meta) {
                if (!empty($meta['always_load'])) {
                    $active[] = $module_id;
                    continue;
                }

                if (!empty($meta['mobile_only']) && !wp_is_mobile()) {
                    continue;
                }

                if (!empty($meta['theme_requirement'])) {
                    if (!MaBox_Tool::theme_active($meta['theme_requirement'])) {
                        continue;
                    }
                }

                $option_key = $meta['option_key'];
                $value = self::get_nested_value($config, $option_key);

                if (empty($value)) {
                    continue;
                }

                if (is_string($value) && $value === 'false') {
                    continue;
                }

                $active[] = $module_id;
            }

            return $active;
        }

        public static function load_module($module_id, $config) {
            $registry = self::get_registry();

            if (!isset($registry[$module_id])) {
                return;
            }

            $meta = $registry[$module_id];

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

            $file = plugin_dir_path(__FILE__) . 'partials/' . $meta['file'];

            if (!file_exists($file)) {
                return;
            }

            require_once $file;

            if (!class_exists($meta['class'])) {
                return;
            }

            // 验证模块是否实现接口契约
            $class = $meta['class'];
            if (!is_subclass_of($class, 'MaBox_Module_Interface')) {
                if (class_exists('MaBox_Audit_Logger')) {
                    MaBox_Audit_Logger::log('warning', 'config', "Module {$class} does not implement MaBox_Module_Interface");
                } else {
                    error_log("[MaBox] Module {$class} does not implement MaBox_Module_Interface");
                }
            }

            // 统一调用 run() 方法，兼容非标准方法名（如 runs）
            $method = 'run';
            if (!method_exists($class, 'run')) {
                if (method_exists($class, 'runs')) {
                    $method = 'runs';
                } else {
                    if (class_exists('MaBox_Audit_Logger')) {
                        MaBox_Audit_Logger::log('error', 'config', "Module {$class} has no run() or runs() method");
                    } else {
                        error_log("[MaBox] Module {$class} has no run() or runs() method");
                    }
                    return;
                }
            }

            if (!empty($meta['config_path'])) {
                $module_config = self::get_nested_value($config, $meta['config_path']);
                call_user_func(array($meta['class'], $method), $module_config);
            } else {
                call_user_func(array($meta['class'], $method));
            }
        }

        public static function get_module_meta($module_id) {
            $registry = self::get_registry();
            return isset($registry[$module_id]) ? $registry[$module_id] : null;
        }

        public static function get_modules_by_category($category) {
            $registry = self::get_registry();
            $modules = array();

            foreach ($registry as $module_id => $meta) {
                if ($meta['category'] === $category) {
                    $modules[$module_id] = $meta;
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

        private static function get_nested_value($data, $path) {
            $keys = explode('.', $path);
            $current = $data;

            foreach ($keys as $key) {
                if (is_array($current) && isset($current[$key])) {
                    $current = $current[$key];
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
