<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/**
 * 分模块配置存储与敏感设置边界。
 *
 * @since 2.1.0
 */
if (!class_exists('MaBox_Config_Manager')) {
    class MaBox_Config_Manager {

        /**
         * 顶层配置模块与 WordPress Option 的唯一映射。
         */
        private static $module_map = array(
            'optimize'    => MAGICK_MIXTURE_OPTION_OPTIMIZE,
            'page'        => MAGICK_MIXTURE_OPTION_PAGE,
            'function'    => MAGICK_MIXTURE_OPTION_FUNCTION,
            'login'       => MAGICK_MIXTURE_OPTION_LOGIN,
            'domestic'    => MAGICK_MIXTURE_OPTION_DOMESTIC,
            'performance' => MAGICK_MIXTURE_OPTION_PERFORMANCE,
        );

        /**
         * 单次请求内的合并配置缓存。
         */
        private static $merged_cache = null;

        /**
         * 获取合并后的服务端完整配置。
         *
         * 此方法包含凭据，只能用于服务端运行时。
         */
        public static function get_merged_config() {
            if (self::$merged_cache !== null) {
                return self::$merged_cache;
            }

            $merged = array();
            foreach (self::$module_map as $top_key => $option_name) {
                $module_data = get_option($option_name, array());
                if (is_array($module_data) && !empty($module_data)) {
                    $merged[$top_key] = $module_data;
                }
            }

            self::$merged_cache = $merged;
            return $merged;
        }

        /**
         * 获取不含凭据的浏览器设置与凭据状态。
         *
         * @param array|null $config 仅供内部及测试传入的完整配置。
         * @return array{data: array, secretStatus: array<string, array{configured: bool}>}
         */
        public static function get_browser_config($config = null) {
            $config = is_array($config) ? $config : self::get_merged_config();
            // 新安装时 Option 尚不存在，但读取契约仍必须向前端提供
            // 完整且已清洗的配置结构，避免各设置组件自行猜测缺失分支。
            $validation = MaBox_Config_Schema::validate_full_config($config);
            $data = $validation['data'];
            $status = array();

            foreach (self::get_secret_paths() as $path) {
                $value = self::get_nested_value($config, $path, '');
                $status[$path] = array(
                    'configured' => is_string($value) && $value !== '',
                );
                self::remove_nested_value($data, $path);
            }

            return array(
                'data' => $data,
                'secretStatus' => $status,
            );
        }

        /**
         * 验证浏览器设置契约，并在服务端合并凭据。
         *
         * @param array      $settings       不含敏感字段的完整设置。
         * @param array      $secret_changes 以敏感路径为键的 replace/clear 操作。
         * @param array|null $current_config 当前服务端完整配置。
         * @return array{success: bool, data?: array, error?: string}
         */
        public static function merge_secret_changes($settings, $secret_changes, $current_config = null) {
            if (!is_array($settings) || !is_array($secret_changes)) {
                return array('success' => false, 'error' => '设置数据格式无效');
            }

            $structure = MaBox_Config_Schema::validate_browser_settings($settings);
            if (!$structure['valid']) {
                $message = !empty($structure['errors'][0]) ? $structure['errors'][0] : '设置结构无效';
                return array('success' => false, 'error' => $message);
            }

            $secret_paths = self::get_secret_paths();
            foreach ($secret_paths as $path) {
                if (self::has_nested_value($settings, $path)) {
                    return array('success' => false, 'error' => '敏感字段必须通过 secretChanges 更新');
                }
            }

            $current_config = is_array($current_config) ? $current_config : self::get_merged_config();
            $merged = $settings;

            foreach ($secret_paths as $path) {
                $current_value = self::get_nested_value($current_config, $path, '');
                self::set_nested_value($merged, $path, is_string($current_value) ? $current_value : '');
            }

            foreach ($secret_changes as $path => $change) {
                if (!is_string($path) || !in_array($path, $secret_paths, true)) {
                    return array('success' => false, 'error' => '包含未知的敏感字段路径');
                }
                if (!is_array($change) || !isset($change['operation']) || !is_string($change['operation'])) {
                    return array('success' => false, 'error' => '敏感字段操作格式无效');
                }

                $allowed_keys = $change['operation'] === 'replace'
                    ? array('operation', 'value')
                    : array('operation');
                if (!empty(array_diff(array_keys($change), $allowed_keys))) {
                    return array('success' => false, 'error' => '敏感字段操作包含未知参数');
                }

                if ($change['operation'] === 'clear') {
                    self::set_nested_value($merged, $path, '');
                    continue;
                }

                if ($change['operation'] !== 'replace') {
                    return array('success' => false, 'error' => '不支持的敏感字段操作');
                }
                if (!array_key_exists('value', $change) || !is_string($change['value']) || trim($change['value']) === '') {
                    return array('success' => false, 'error' => '替换凭据必须为非空字符串');
                }
                if (strlen($change['value']) > 4096) {
                    return array('success' => false, 'error' => '凭据长度超出限制');
                }
                if (preg_match('/[\x00-\x1F\x7F]/', $change['value'])) {
                    return array('success' => false, 'error' => '凭据不得包含控制字符');
                }

                self::set_nested_value($merged, $path, $change['value']);
            }

            return array('success' => true, 'data' => $merged);
        }

        /**
         * 获取单个模块的服务端配置。
         */
        public static function get_module_config($module) {
            if (!isset(self::$module_map[$module])) {
                return array();
            }
            $config = get_option(self::$module_map[$module], array());
            return is_array($config) ? $config : array();
        }

        /**
         * 跨模块原子式保存。
         *
         * WordPress 在新旧值相同时会让 update_option() 返回 false，这不是失败。
         * 只有在值确实变化且 update_option() 返回 false 时才回滚本次已写模块。
         */
        public static function save_full_config($full_config) {
            if (!is_array($full_config)) {
                return array('success' => false, 'saved_modules' => array(), 'failed_modules' => array());
            }

            $saved = array();
            $changed = array();
            $missing = new \stdClass();

            foreach (self::$module_map as $top_key => $option_name) {
                if (!array_key_exists($top_key, $full_config)) {
                    continue;
                }

                $previous = get_option($option_name, $missing);
                $next = $full_config[$top_key];

                if ($previous !== $missing && $previous === $next) {
                    $saved[] = $top_key;
                    continue;
                }

                if (update_option($option_name, $next) === false) {
                    foreach (array_reverse($changed, true) as $changed_option => $old_value) {
                        if ($old_value === $missing) {
                            delete_option($changed_option);
                        } else {
                            update_option($changed_option, $old_value);
                        }
                    }
                    self::$merged_cache = null;
                    return array(
                        'success' => false,
                        'saved_modules' => array(),
                        'failed_modules' => array($top_key),
                    );
                }

                $changed[$option_name] = $previous;
                $saved[] = $top_key;
            }

            self::$merged_cache = null;
            return array(
                'success' => true,
                'saved_modules' => $saved,
                'failed_modules' => array(),
            );
        }

        /**
         * 保存单个模块，同值写入视为成功。
         */
        public static function save_module_config($module, $data) {
            if (!isset(self::$module_map[$module])) {
                return false;
            }

            $option_name = self::$module_map[$module];
            $missing = new \stdClass();
            $previous = get_option($option_name, $missing);
            if ($previous !== $missing && $previous === $data) {
                return true;
            }

            $result = update_option($option_name, $data);
            self::$merged_cache = null;
            return $result !== false;
        }

        public static function clear_cache() {
            self::$merged_cache = null;
        }

        public static function get_module_map() {
            return self::$module_map;
        }

        public static function get_secret_paths() {
            $paths = array();
            $schema = MaBox_Config_Schema::get_schema();

            foreach ($schema as $module_key => $module_def) {
                if (!is_array($module_def) || $module_key === '_option_key' || $module_key === '_flat') {
                    continue;
                }
                foreach ($module_def as $sub_key => $sub_def) {
                    if (!is_array($sub_def) || $sub_key === '_option_key' || $sub_key === '_flat') {
                        continue;
                    }
                    foreach ($sub_def as $field_key => $field_def) {
                        if (is_array($field_def) && !empty($field_def['sensitive'])) {
                            $paths[] = $module_key . '.' . $sub_key . '.' . $field_key;
                        }
                    }
                }
            }

            return $paths;
        }

        private static function get_nested_value($data, $path, $default = null) {
            $current = $data;
            foreach (explode('.', $path) as $key) {
                if (!is_array($current) || !array_key_exists($key, $current)) {
                    return $default;
                }
                $current = $current[$key];
            }
            return $current;
        }

        private static function has_nested_value($data, $path) {
            $current = $data;
            foreach (explode('.', $path) as $key) {
                if (!is_array($current) || !array_key_exists($key, $current)) {
                    return false;
                }
                $current = $current[$key];
            }
            return true;
        }

        private static function set_nested_value(&$data, $path, $value) {
            $keys = explode('.', $path);
            $current =& $data;
            foreach ($keys as $key) {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    $current[$key] = array();
                }
                $current =& $current[$key];
            }
            $current = $value;
            unset($current);
        }

        private static function remove_nested_value(&$data, $path) {
            $keys = explode('.', $path);
            $last = array_pop($keys);
            $current =& $data;
            foreach ($keys as $key) {
                if (!isset($current[$key]) || !is_array($current[$key])) {
                    return;
                }
                $current =& $current[$key];
            }
            unset($current[$last]);
            unset($current);
        }
    }
}
