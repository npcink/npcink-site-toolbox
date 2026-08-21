<?php

defined('ABSPATH') || exit;

/**
 * 隐藏邮件中的 IP 地址
 *
 * 在 WordPress 发送的邮件中（如评论通知、新用户注册等），
 * 将 IP 地址替换为 [已隐藏]，保护用户隐私。
 */
if (!class_exists('Npcink_Toolbox_Hide_Email_IP')) {
    class Npcink_Toolbox_Hide_Email_IP implements Npcink_Toolbox_Module_Interface {

        private static $option;

        public static function run($config = array()) {
            self::$option = $config;
            add_filter('wp_mail', array(__CLASS__, 'hide_ip_in_email'));
        }

        public static function hide_ip_in_email($args) {
            if (!empty($args['message'])) {
                $args['message'] = self::replace_ips($args['message']);
            }
            if (!empty($args['headers'])) {
                if (is_array($args['headers'])) {
                    $args['headers'] = array_map(array(__CLASS__, 'replace_ips'), $args['headers']);
                } else {
                    $args['headers'] = self::replace_ips($args['headers']);
                }
            }
            return $args;
        }

        private static function replace_ips($text) {
            $patterns = array(
                '/\b(?:\d{1,3}\.){3}\d{1,3}\b/',
                '/\b(?:[0-9a-fA-F]{1,4}:){7}[0-9a-fA-F]{1,4}\b/',
                '/\b(?:[0-9a-fA-F]{1,4}:){1,7}:\b/',
            );
            return preg_replace_callback(
                $patterns,
                static function () {
                    return __('[IP 已隐藏]', 'npcink-site-toolbox');
                },
                $text
            );
        }
    }
}
