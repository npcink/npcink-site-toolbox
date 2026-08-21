<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;
/**
 * 频率限制器
 *
 * 用于限制公开 REST API 端点的访问频率，防止滥用。
 *
 * @since 2.4.0
 */
if (!class_exists('Npcink_Toolbox_Rate_Limiter')) {
    class Npcink_Toolbox_Rate_Limiter
    {
        /**
         * 默认限制配置
         */
        private static $defaults = array(
            'max_requests' => 60,      // 最大请求数
            'time_window'  => 60,      // 时间窗口（秒）
            'block_time'   => 300,     // 封禁时间（秒）
        );

        /**
         * 检查是否超过频率限制
         *
         * @param string $key        限制键（如 IP、接口名等）
         * @param array  $config     自定义配置
         * @return bool              是否允许请求（true=允许，false=限流）
         */
        public static function check($key, $config = array())
        {
            $config = array_merge(self::$defaults, $config);
            $transient_key = 'npcink_site_toolbox_rate_limit_' . md5($key);
            $data = get_transient($transient_key);

            if ($data === false) {
                // 首次请求，初始化计数
                set_transient($transient_key, array(
                    'count' => 1,
                    'start' => time(),
                    'blocked' => false,
                ), $config['time_window']);
                return true;
            }

            // 检查是否被封禁
            if (!empty($data['blocked'])) {
                return false;
            }

            // 检查时间窗口是否过期
            if (time() - $data['start'] > $config['time_window']) {
                // 重置计数
                set_transient($transient_key, array(
                    'count' => 1,
                    'start' => time(),
                    'blocked' => false,
                ), $config['time_window']);
                return true;
            }

            // 增加计数
            $data['count']++;

            // 检查是否超过限制
            if ($data['count'] > $config['max_requests']) {
                // 触发封禁
                $data['blocked'] = true;
                $data['blocked_at'] = time();
                set_transient($transient_key, $data, $config['block_time']);

                // 记录日志
                if (class_exists('Npcink_Toolbox_Audit_Logger')) {
                    Npcink_Toolbox_Audit_Logger::rate_limit('频率限制触发: ' . $key, array(
                        'count' => $data['count'],
                        'time_window' => $config['time_window'],
                    ));
                }

                return false;
            }

            // 更新计数
            set_transient($transient_key, $data, $config['time_window']);
            return true;
        }

        /**
         * 获取客户端标识（IP + User Agent 组合）
         *
         * @return string
         */
        public static function get_client_id()
        {
            $ip = Npcink_Toolbox_Helpers::get_real_ip();
            $ua = isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])
                ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))
                : '';
            return md5($ip . '|' . $ua);
        }

        /**
         * REST API 权限回调：仅检查频率限制
         *
         * @param string $endpoint   端点标识
         * @param array  $config     自定义配置
         * @return callable          权限回调函数
         */
        public static function permission_callback($endpoint, $config = array())
        {
            return function () use ($endpoint, $config) {
                $client_id = self::get_client_id();
                $key = $endpoint . ':' . $client_id;

                if (!self::check($key, $config)) {
                    return new \WP_Error(
                        'rate_limit_exceeded',
                        __('请求过于频繁，请稍后再试', 'npcink-site-toolbox'),
                        array('status' => 429)
                    );
                }

                return true;
            };
        }

        /**
         * REST API 权限回调：频率限制 + nonce 验证（组合使用）
         *
         * @param string $endpoint   端点标识
         * @param string $nonce_action Nonce 动作名称
         * @param array  $config     自定义配置
         * @return callable          权限回调函数
         */
        public static function permission_callback_with_nonce($endpoint, $nonce_action, $config = array())
        {
            return function ($request = null) use ($endpoint, $nonce_action, $config) {
                $client_id = self::get_client_id();
                $key = $endpoint . ':' . $client_id;

                if (!self::check($key, $config)) {
                    return new \WP_Error(
                        'rate_limit_exceeded',
                        __('请求过于频繁，请稍后再试', 'npcink-site-toolbox'),
                        array('status' => 429)
                    );
                }

                $nonce = '';
                if (is_object($request) && method_exists($request, 'get_header')) {
                    $nonce = $request->get_header('x-npcink-site-toolbox-nonce');
                }
                if (empty($nonce) && is_object($request) && method_exists($request, 'get_param')) {
                    $nonce = $request->get_param('nonce');
                }
                $nonce = is_string($nonce) ? sanitize_text_field($nonce) : '';

                if (empty($nonce) || wp_verify_nonce($nonce, $nonce_action) === false) {
                    return new \WP_Error(
                        'invalid_nonce',
                        __('安全验证失败，请刷新页面重试', 'npcink-site-toolbox'),
                        array('status' => 403)
                    );
                }

                return true;
            };
        }

        /**
         * 重置指定键的频率限制
         *
         * @param string $key
         * @return bool
         */
        public static function reset($key)
        {
            $transient_key = 'npcink_site_toolbox_rate_limit_' . md5($key);
            return delete_transient($transient_key);
        }

        /**
         * 获取当前频率限制状态
         *
         * @param string $key
         * @return array|null
         */
        public static function get_status($key)
        {
            $transient_key = 'npcink_site_toolbox_rate_limit_' . md5($key);
            return get_transient($transient_key);
        }
    }
}
