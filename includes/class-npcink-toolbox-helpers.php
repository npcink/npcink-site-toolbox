<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;
/**
 * 公共工具类
 *
 * 所有功能模块共享的公共逻辑。
 * 此类在插件初始化时必然加载，模块可安全调用。
 */

if (!class_exists('Npcink_Toolbox_Helpers')) {
    class Npcink_Toolbox_Helpers
    {
        /**
         * 获取用户真实 IP
         */
        public static function get_real_ip()
        {
            // Forwarded headers are client-controlled unless a trusted proxy boundary exists.
            if (!isset($_SERVER['REMOTE_ADDR']) || !is_string($_SERVER['REMOTE_ADDR'])) {
                return '';
            }

            $ip = sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']));
            return filter_var($ip, FILTER_VALIDATE_IP) !== false ? $ip : '';
        }

        /**
         * 获取限流用的客户端 IP。
         *
         * 复用登录安全模块的「信任代理」配置：REMOTE_ADDR 命中信任清单时，
         * 从 X-Forwarded-For 自右向左取第一个非信任 IP，否则直接使用 REMOTE_ADDR。
         * 未配置信任代理时行为与 get_real_ip() 一致，保证 CDN/反代站点
         * 不会让全体访客共享同一个限流桶。
         */
        public static function get_rate_limit_ip()
        {
            $remote_addr = self::get_real_ip();
            if ($remote_addr === '') {
                return '';
            }

            $trusted_proxies = self::get_trusted_proxies();
            if (empty($trusted_proxies) || !in_array($remote_addr, $trusted_proxies, true)) {
                return $remote_addr;
            }

            if (!isset($_SERVER['HTTP_X_FORWARDED_FOR']) || !is_string($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                return $remote_addr;
            }

            $forwarded_for = sanitize_text_field(wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR']));
            if (trim($forwarded_for) === '') {
                return $remote_addr;
            }

            $chain = array($remote_addr);
            foreach (array_reverse(explode(',', $forwarded_for)) as $forwarded_ip) {
                $normalized = filter_var(trim($forwarded_ip), FILTER_VALIDATE_IP);
                if ($normalized === false) {
                    return $remote_addr;
                }
                $chain[] = $normalized;
            }

            foreach ($chain as $ip) {
                if (!in_array($ip, $trusted_proxies, true)) {
                    return $ip;
                }
            }

            return $remote_addr;
        }

        /**
         * 读取登录安全模块配置的信任代理 IP 清单
         */
        private static function get_trusted_proxies()
        {
            $domestic = self::get_config('domestic', 'login_security', array());
            $raw      = is_array($domestic) && isset($domestic['trusted_proxies']) && is_string($domestic['trusted_proxies'])
                ? $domestic['trusted_proxies']
                : '';
            if ($raw === '') {
                return array();
            }

            $trusted = array();
            $lines   = preg_split('/\r\n|\r|\n/', $raw);
            if (!is_array($lines)) {
                return array();
            }
            foreach ($lines as $line) {
                $ip = filter_var(trim($line), FILTER_VALIDATE_IP);
                if ($ip === false) {
                    return array();
                }
                $trusted[ $ip ] = true;
            }

            return array_keys($trusted);
        }

        /**
         * 判断是否为移动端
         */
        public static function is_mobile()
        {
            return wp_is_mobile();
        }

        /**
         * 判断当前用户是否已登录
         */
        public static function is_logged_in()
        {
            return is_user_logged_in();
        }

        /**
         * 获取当前文章 ID
         */
        public static function get_current_post_id()
        {
            return get_the_ID();
        }

        /**
         * 安全获取配置值（直接读取 Config_Manager，不依赖 Npcink_Toolbox_Admin）
         */
        // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- 参数名沿用既有公共签名
        public static function get_config($module, $key, $default = false)
        {
            $module_config = Npcink_Toolbox_Config_Manager::get_module_config($module);
            if (is_array($module_config) && array_key_exists($key, $module_config)) {
                return $module_config[ $key ];
            }
            return $default;
        }

        /**
         * 获取完整合并配置
         */
        public static function get_merged_config()
        {
            return Npcink_Toolbox_Config_Manager::get_merged_config();
        }
    }
}
