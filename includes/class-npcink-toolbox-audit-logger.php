<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;
/**
 * 统一审计日志中心
 *
 * 发布高风险操作、接口错误、API 调用失败等结构化审计事件。
 * 默认通过动作钩子交给调用方处理；数据库存储需显式启用。
 *
 * @since 2.4.0
 */
if (!class_exists('Npcink_Toolbox_Audit_Logger')) {
    class Npcink_Toolbox_Audit_Logger
    {
        /**
         * 日志级别
         */
        const LEVEL_INFO     = 'info';
        const LEVEL_WARNING  = 'warning';
        const LEVEL_ERROR    = 'error';
        const LEVEL_CRITICAL = 'critical';

        /**
         * 日志类别
         */
        const CATEGORY_SECURITY   = 'security';
        const CATEGORY_DATABASE   = 'database';
        const CATEGORY_CONFIG     = 'config';
        const CATEGORY_API        = 'api';
        const CATEGORY_FILE       = 'file';
        const CATEGORY_AUTH       = 'auth';
        const CATEGORY_RATE_LIMIT = 'rate_limit';

        /**
         * 日志存储选项名
         */
        const OPTION_NAME = 'npcink_site_toolbox_audit_log';

        /**
         * 最大保留日志条数
         */
        const MAX_LOG_ENTRIES = 500;

        /**
         * 记录一条审计日志
         *
         * @param string $level     日志级别
         * @param string $category  日志类别
         * @param string $message   日志消息
         * @param array  $context   附加上下文数据
         * @return bool
         */
        public static function log($level, $category, $message, $context = array())
        {
            $entry = array(
                'timestamp'   => current_time('mysql'),
                'level'       => $level,
                'category'    => $category,
                'message'     => $message,
                'context'     => $context,
                'user_id'     => get_current_user_id(),
                'user_login'  => function_exists('wp_get_current_user') ? wp_get_current_user()->user_login : 'cli',
                'ip'          => Npcink_Toolbox_Helpers::get_real_ip(),
                'request_uri' => isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])
                    ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI']))
                    : '',
            );

            // 数据库存储保持显式启用，避免高频事件造成默认写放大。
            self::store_entry($entry);

            /**
             * 发布默认可用的审计事件，允许第三方监听并选择落点。
             *
             * @since 2.4.0
             */
            do_action('npcink_site_toolbox_audit_log', $entry);

            return true;
        }

        /**
         * 便捷方法：记录安全事件
         */
        public static function security($message, $context = array())
        {
            return self::log(self::LEVEL_WARNING, self::CATEGORY_SECURITY, $message, $context);
        }

        /**
         * 便捷方法：记录数据库操作
         */
        public static function database($message, $context = array())
        {
            return self::log(self::LEVEL_INFO, self::CATEGORY_DATABASE, $message, $context);
        }

        /**
         * 便捷方法：记录配置变更
         */
        public static function config($message, $context = array())
        {
            return self::log(self::LEVEL_INFO, self::CATEGORY_CONFIG, $message, $context);
        }

        /**
         * 便捷方法：记录 API 错误
         */
        public static function api_error($message, $context = array())
        {
            return self::log(self::LEVEL_ERROR, self::CATEGORY_API, $message, $context);
        }

        /**
         * 便捷方法：记录文件操作
         */
        public static function file($message, $context = array())
        {
            return self::log(self::LEVEL_INFO, self::CATEGORY_FILE, $message, $context);
        }

        /**
         * 便捷方法：记录限流事件
         */
        public static function rate_limit($message, $context = array())
        {
            return self::log(self::LEVEL_WARNING, self::CATEGORY_RATE_LIMIT, $message, $context);
        }

        /**
         * 获取最近的审计日志
         *
         * @param int    $limit    返回条数
         * @param string $level    按级别过滤
         * @param string $category 按类别过滤
         * @return array
         */
        public static function get_recent($limit = 50, $level = null, $category = null)
        {
            $logs = get_option(self::OPTION_NAME, array());

            if (empty($logs)) {
                return array();
            }

            // 按时间倒序（最新的在前）
            $logs = array_reverse($logs);

            // 过滤
            if ($level) {
                $logs = array_filter($logs, function ($entry) use ($level) {
                    return $entry['level'] === $level;
                });
            }
            if ($category) {
                $logs = array_filter($logs, function ($entry) use ($category) {
                    return $entry['category'] === $category;
                });
            }

            return array_slice($logs, 0, $limit);
        }

        /**
         * 清空审计日志
         *
         * @return bool
         */
        public static function clear()
        {
            return delete_option(self::OPTION_NAME);
        }

        /**
         * 存储日志条目到数据库
         *
         * @param array $entry
         */
        private static function store_entry($entry)
        {
            // 仅在明确启用时存储到数据库
            if (!defined('NPCINK_SITE_TOOLBOX_ENABLE_AUDIT_LOG_STORAGE') || !NPCINK_SITE_TOOLBOX_ENABLE_AUDIT_LOG_STORAGE) {
                return;
            }

            $logs   = get_option(self::OPTION_NAME, array());
            $logs[] = $entry;

            // 限制日志数量
            if (count($logs) > self::MAX_LOG_ENTRIES) {
                $logs = array_slice($logs, -self::MAX_LOG_ENTRIES);
            }

            // 使用 update_option 而非 add_option，避免竞态条件
            update_option(self::OPTION_NAME, $logs, false);
        }
    }
}
