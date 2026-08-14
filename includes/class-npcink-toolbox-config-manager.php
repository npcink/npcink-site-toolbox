<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/**
 * 分模块配置存储与敏感设置边界。
 *
 * @since 2.1.0
 */
if (!class_exists('Npcink_Toolbox_Config_Manager')) {
    class Npcink_Toolbox_Config_Manager {

        /**
         * 顶层配置模块与 WordPress Option 的唯一映射。
         */
        private static $module_map = array(
            'optimize'    => NPCINK_SITE_TOOLBOX_OPTION_OPTIMIZE,
            'page'        => NPCINK_SITE_TOOLBOX_OPTION_PAGE,
            'function'    => NPCINK_SITE_TOOLBOX_OPTION_FUNCTION,
            'domestic'    => NPCINK_SITE_TOOLBOX_OPTION_DOMESTIC,
            'performance' => NPCINK_SITE_TOOLBOX_OPTION_PERFORMANCE,
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
            $validation = Npcink_Toolbox_Config_Schema::validate_full_config($config);
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
         * 获取当前完整配置的不透明版本指纹。
         *
         * 指纹使用站点盐值计算 HMAC，浏览器只能用于并发比较，不能据此还原
         * 普通设置或凭据内容。
         *
         * @param array|null $config 仅供内部及测试传入的完整配置。
         * @return string
         */
        public static function get_config_revision($config = null) {
            $config = is_array($config) ? $config : self::get_merged_config();
            $serialized = serialize($config);

            if (function_exists('wp_salt')) {
                $key = wp_salt('auth');
            } elseif (defined('AUTH_SALT') && AUTH_SALT !== '') {
                $key = AUTH_SALT;
            } else {
                $key = 'npcink-site-toolbox-settings-revision';
            }

            return hash_hmac('sha256', $serialized, $key);
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
                return array('success' => false, 'error' => __('设置数据格式无效', 'npcink-site-toolbox'));
            }

            $structure = Npcink_Toolbox_Config_Schema::validate_browser_settings($settings);
            if (!$structure['valid']) {
                $message = !empty($structure['errors'][0]) ? $structure['errors'][0] : __('设置结构无效', 'npcink-site-toolbox');
                return array('success' => false, 'error' => $message);
            }

            $secret_paths = self::get_secret_paths();
            foreach ($secret_paths as $path) {
                if (self::has_nested_value($settings, $path)) {
                    return array('success' => false, 'error' => __('敏感字段必须通过 secretChanges 更新', 'npcink-site-toolbox'));
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
                    return array('success' => false, 'error' => __('包含未知的敏感字段路径', 'npcink-site-toolbox'));
                }
                if (!is_array($change) || !isset($change['operation']) || !is_string($change['operation'])) {
                    return array('success' => false, 'error' => __('敏感字段操作格式无效', 'npcink-site-toolbox'));
                }

                $allowed_keys = $change['operation'] === 'replace'
                    ? array('operation', 'value')
                    : array('operation');
                if (!empty(array_diff(array_keys($change), $allowed_keys))) {
                    return array('success' => false, 'error' => __('敏感字段操作包含未知参数', 'npcink-site-toolbox'));
                }

                if ($change['operation'] === 'clear') {
                    self::set_nested_value($merged, $path, '');
                    continue;
                }

                if ($change['operation'] !== 'replace') {
                    return array('success' => false, 'error' => __('不支持的敏感字段操作', 'npcink-site-toolbox'));
                }
                if (!array_key_exists('value', $change) || !is_string($change['value']) || trim($change['value']) === '') {
                    return array('success' => false, 'error' => __('替换凭据必须为非空字符串', 'npcink-site-toolbox'));
                }
                if (strlen($change['value']) > 4096) {
                    return array('success' => false, 'error' => __('凭据长度超出限制', 'npcink-site-toolbox'));
                }
                if (preg_match('/[\x00-\x1F\x7F]/', $change['value'])) {
                    return array('success' => false, 'error' => __('凭据不得包含控制字符', 'npcink-site-toolbox'));
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
         * 回滚结果以 get_option() 回读为准，避免把 update_option() 的同值 false
         * 误判为失败，也避免在数据库写入失败时错误宣称已经完整恢复。
         *
         * @return array{
         *     success: bool,
         *     saved_modules: array,
         *     failed_modules: array,
         *     rollback_complete?: bool,
         *     rollback_failed_modules?: array,
         *     error?: string
         * }
         */
        public static function save_full_config($full_config) {
            if (!is_array($full_config)) {
                return array(
                    'success' => false,
                    'saved_modules' => array(),
                    'failed_modules' => array(),
                    'rollback_complete' => true,
                    'rollback_failed_modules' => array(),
                    'error' => __('配置格式无效，未写入任何设置', 'npcink-site-toolbox'),
                );
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
                    return self::rollback_failed_save($top_key, $changed, $missing);
                }

                $changed[$option_name] = array(
                    'module' => $top_key,
                    'previous' => $previous,
                );
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
         * 回滚本次保存已经改动的模块，并回读确认每个旧值。
         *
         * @param string    $failed_module 写入失败的模块。
         * @param array     $changed       已写入 Option 及其旧值。
         * @param \stdClass $missing       Option 原本不存在时使用的哨兵对象。
         * @return array
         */
        private static function rollback_failed_save($failed_module, $changed, $missing) {
            $rollback_failed_modules = array();

            foreach (array_reverse($changed, true) as $changed_option => $state) {
                $previous = $state['previous'];

                if ($previous === $missing) {
                    delete_option($changed_option);
                    $restored = get_option($changed_option, $missing) === $missing;
                } else {
                    update_option($changed_option, $previous);
                    $restored = get_option($changed_option, $missing) === $previous;
                }

                if (!$restored) {
                    $rollback_failed_modules[] = $state['module'];
                }
            }

            self::$merged_cache = null;
            $rollback_complete = empty($rollback_failed_modules);
            $error = $rollback_complete
                ? __('保存失败，已恢复为之前的设置', 'npcink-site-toolbox')
                : sprintf(
                    __('保存失败，以下模块未能确认恢复：%s。请重新读取并核对设置后再保存', 'npcink-site-toolbox'),
                    implode('、', $rollback_failed_modules)
                );

            return array(
                'success' => false,
                'saved_modules' => array(),
                'failed_modules' => array($failed_module),
                'rollback_complete' => $rollback_complete,
                'rollback_failed_modules' => $rollback_failed_modules,
                'error' => $error,
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
            $schema = Npcink_Toolbox_Config_Schema::get_schema();

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
