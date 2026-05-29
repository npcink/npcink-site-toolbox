<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;
/**
 * 配置存储拆分管理类
 *
 * 负责：
 * 1. 旧配置到新配置的自动迁移
 * 2. 多键读取合并
 * 3. 按模块保存
 * 4. 向后兼容（老用户升级无数据丢失）
 *
 * @since 2.1.0
 */

if (!class_exists('MaBox_Config_Manager')) {
    class MaBox_Config_Manager {

        /**
         * 模块键映射表
         * 定义顶层键名与 WordPress Option 键名的对应关系
         */
        private static $module_map = array(
            'optimize'    => MAGICK_MIXTURE_OPTION_OPTIMIZE,
            'page'        => MAGICK_MIXTURE_OPTION_PAGE,
            'function'    => MAGICK_MIXTURE_OPTION_FUNCTION,
            'login'       => MAGICK_MIXTURE_OPTION_LOGIN,
            'domestic'    => MAGICK_MIXTURE_OPTION_DOMESTIC,
            'performance' => MAGICK_MIXTURE_OPTION_PERFORMANCE,
            'ai_review'   => MAGICK_MIXTURE_OPTION_AI_REVIEW,

        );

        /**
         * 配置缓存（单次请求内缓存）
         */
        private static $merged_cache = null;

        /**
         * 检查是否需要迁移
         *
         * @return bool
         */
        public static function needs_migration() {
            // 已标记迁移完成
            if (get_option(MAGICK_MIXTURE_CONFIG_VERSION) === '2.1.0') {
                return false;
            }

            // 新安装：旧配置不存在，无需迁移
            $old_option = get_option(MAGICK_MIXTURE_OPTION);
            if (empty($old_option)) {
                // 标记为已迁移（新安装）
                update_option(MAGICK_MIXTURE_CONFIG_VERSION, '2.1.0');
                return false;
            }

            // 旧配置存在但版本标记不存在 → 需要迁移
            return true;
        }

        /**
         * 执行配置迁移
         *
         * @return bool 迁移是否成功
         */
        public static function migrate() {
            if (!self::needs_migration()) {
                return true;
            }

            $old_option = get_option(MAGICK_MIXTURE_OPTION);
            if (empty($old_option)) {
                // 旧配置为空，标记迁移完成
                update_option(MAGICK_MIXTURE_CONFIG_VERSION, '2.1.0');
                return true;
            }

            // 备份旧配置
            update_option(MAGICK_MIXTURE_CONFIG_BACKUP, $old_option);

            // 按模块拆分保存
            foreach (self::$module_map as $top_key => $option_name) {
                if (isset($old_option[$top_key]) && is_array($old_option[$top_key])) {
                    update_option($option_name, $old_option[$top_key]);
                } else {
                    // 确保每个模块键都有默认值
                    update_option($option_name, array());
                }
            }

            // 标记迁移完成
            update_option(MAGICK_MIXTURE_CONFIG_VERSION, '2.1.0');

            // 清除旧配置缓存
            wp_cache_delete(MAGICK_MIXTURE_OPTION, 'options');

            return true;
        }

        /**
         * 回滚迁移（从备份恢复旧配置）
         *
         * @return bool
         */
        public static function rollback() {
            $backup = get_option(MAGICK_MIXTURE_CONFIG_BACKUP);
            if (empty($backup)) {
                return false;
            }

            // 恢复旧配置
            update_option(MAGICK_MIXTURE_OPTION, $backup);

            // 删除新配置
            foreach (self::$module_map as $option_name) {
                delete_option($option_name);
            }

            // 清除迁移标记
            delete_option(MAGICK_MIXTURE_CONFIG_VERSION);

            return true;
        }

        /**
         * 获取合并后的完整配置（读取所有模块键并合并）
         *
         * @return array 完整配置
         */
        public static function get_merged_config() {
            if (self::$merged_cache !== null) {
                return self::$merged_cache;
            }

            $merged = array();

            foreach (self::$module_map as $top_key => $option_name) {
                $module_data = get_option($option_name, array());
                if (!empty($module_data)) {
                    $merged[$top_key] = $module_data;
                }
            }

            self::$merged_cache = $merged;
            return $merged;
        }

        /**
         * 获取单个模块的配置
         *
         * @param string $module 模块名 (optimize, page, function, etc.)
         * @return array
         */
        public static function get_module_config($module) {
            if (!isset(self::$module_map[$module])) {
                return array();
            }
            return get_option(self::$module_map[$module], array());
        }

        /**
         * 保存完整配置（按模块拆分保存到不同 Option）
         *
         * @param array $full_config 完整配置对象
         * @return array 保存结果 ['success' => bool, 'saved_modules' => array, 'failed_modules' => array]
         */
        public static function save_full_config($full_config) {
            $saved = array();
            $failed = array();

            foreach (self::$module_map as $top_key => $option_name) {
                if (isset($full_config[$top_key])) {
                    $result = update_option($option_name, $full_config[$top_key]);
                    if ($result !== false) {
                        $saved[] = $top_key;
                    } else {
                        $failed[] = $top_key;
                    }
                }
            }

            // 清除合并缓存
            self::$merged_cache = null;

            return array(
                'success' => empty($failed),
                'saved_modules' => $saved,
                'failed_modules' => $failed,
            );
        }

        /**
         * 保存单个模块配置
         *
         * @param string $module 模块名
         * @param array $data 模块数据
         * @return bool
         */
        public static function save_module_config($module, $data) {
            if (!isset(self::$module_map[$module])) {
                return false;
            }

            $result = update_option(self::$module_map[$module], $data);

            // 清除合并缓存
            self::$merged_cache = null;

            return $result !== false;
        }

        /**
         * 导出完整配置（合并所有模块为单一 JSON 结构，保持向后兼容）
         *
         * @return array
         */
        public static function export_config() {
            return self::get_merged_config();
        }

        /**
         * 导入配置（解析后按模块拆分保存）
         *
         * @param array $config 导入的配置数据
         * @return array 导入结果
         */
        public static function import_config($config) {
            if (!is_array($config) || empty($config)) {
                return array('success' => false, 'error' => '配置数据格式无效');
            }

            // 导入前备份当前配置
            $backup = self::get_merged_config();

            // 尝试保存
            $result = self::save_full_config($config);

            if (!$result['success']) {
                // 保存失败，恢复备份
                self::save_full_config($backup);
                return array(
                    'success' => false,
                    'error' => '导入失败，已恢复之前配置',
                    'failed_modules' => $result['failed_modules'],
                );
            }

            return array('success' => true, 'saved_modules' => $result['saved_modules']);
        }

        /**
         * 清除配置缓存
         */
        public static function clear_cache() {
            self::$merged_cache = null;
        }

        /**
         * 获取模块映射表（供外部使用）
         *
         * @return array
         */
        public static function get_module_map() {
            return self::$module_map;
        }
    }
}
