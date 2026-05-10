<?php
if (!class_exists('MaBox_Domestic_Login_Security')) {
    class MaBox_Domestic_Login_Security {
        private static $config;
        public static function run($config) {
            self::$config = $config;
            if (!empty($config['fail_limit_enabled'])) {
                add_action('wp_login_failed', array(__CLASS__, 'record_failed_login'));
                add_filter('authenticate', array(__CLASS__, 'check_login_lock'), 40, 3);
            }
            if (!empty($config['ip_lock_enabled'])) {
                add_action('wp_login_failed', array(__CLASS__, 'record_ip_failure'));
                add_filter('authenticate', array(__CLASS__, 'check_ip_lock'), 41, 3);
            }
            if (!empty($config['custom_login_enabled']) && !empty($config['custom_login_slug'])) {
                add_action('init', array(__CLASS__, 'custom_login_redirect'));
                add_filter('site_url', array(__CLASS__, 'filter_login_url'), 10, 4);
            }
            if (!empty($config['ban_enumeration_enabled'])) {
                add_action('init', array(__CLASS__, 'ban_user_enumeration'));
            }
            if (!empty($config['login_notify_enabled'])) {
                add_action('wp_login', array(__CLASS__, 'notify_login'), 10, 2);
            }
            if (!empty($config['login_log_enabled'])) {
                add_action('wp_login', array(__CLASS__, 'log_success_login'), 10, 2);
                add_action('wp_login_failed', array(__CLASS__, 'log_failed_login'));
            }
            if (!empty($config['ip_whitelist_enabled']) && !empty($config['ip_whitelist'])) {
                add_action('init', array(__CLASS__, 'check_ip_whitelist'));
            }
        }
        public static function record_failed_login($username) {
            $key = 'mabox_login_fails_' . md5(strtolower($username));
            $count = get_transient($key);
            if ($count === false) $count = 0;
            $count++;
            $limit = !empty(self::$config['fail_limit_count']) ? intval(self::$config['fail_limit_count']) : 5;
            $duration = !empty(self::$config['fail_lock_duration']) ? intval(self::$config['fail_lock_duration']) : 30;
            set_transient($key, $count, $duration * MINUTE_IN_SECONDS);
        }
        public static function check_login_lock($user, $username, $password) {
            $key = 'mabox_login_fails_' . md5(strtolower($username));
            $count = get_transient($key);
            $limit = !empty(self::$config['fail_limit_count']) ? intval(self::$config['fail_limit_count']) : 5;
            if ($count && $count >= $limit) {
                return new WP_Error('locked', '该账号登录失败次数过多，已被临时锁定。');
            }
            return $user;
        }
        public static function record_ip_failure($username) {
            $ip = self::get_client_ip();
            $key = 'mabox_ip_fails_' . md5($ip);
            $count = get_transient($key);
            if ($count === false) $count = 0;
            $count++;
            $limit = !empty(self::$config['ip_lock_count']) ? intval(self::$config['ip_lock_count']) : 10;
            $duration = !empty(self::$config['ip_lock_duration']) ? intval(self::$config['ip_lock_duration']) : 60;
            set_transient($key, $count, $duration * MINUTE_IN_SECONDS);
        }
        public static function check_ip_lock($user, $username, $password) {
            $ip = self::get_client_ip();
            $key = 'mabox_ip_fails_' . md5($ip);
            $count = get_transient($key);
            $limit = !empty(self::$config['ip_lock_count']) ? intval(self::$config['ip_lock_count']) : 10;
            if ($count && $count >= $limit) {
                return new WP_Error('ip_locked', '该 IP 登录失败次数过多，已被临时封禁。');
            }
            return $user;
        }
        public static function custom_login_redirect() {
            $slug = !empty(self::$config['custom_login_slug']) ? sanitize_title(self::$config['custom_login_slug']) : 'my-login';
            $current = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
            if (strpos($current, 'wp-login.php') !== false && !is_admin()) {
                if (strpos($current, $slug) === false) {
                    wp_redirect(home_url('/' . $slug . '/'));
                    exit;
                }
            }
        }
        public static function filter_login_url($url, $path, $scheme, $blog_id) {
            $slug = !empty(self::$config['custom_login_slug']) ? sanitize_title(self::$config['custom_login_slug']) : 'my-login';
            if ($path === 'wp-login.php' || strpos($path, 'wp-login.php') !== false) {
                return home_url('/' . $slug . '/');
            }
            return $url;
        }
        public static function ban_user_enumeration() {
            if (is_admin()) return;
            if (isset($_REQUEST['author']) && is_numeric($_REQUEST['author'])) {
                wp_redirect(home_url());
                exit;
            }
            add_filter('rest_endpoints', function($endpoints) {
                if (isset($endpoints['/wp/v2/users'])) {
                    unset($endpoints['/wp/v2/users']);
                }
                if (isset($endpoints['/wp/v2/users/(?P<id>[\\d]+)'])) {
                    unset($endpoints['/wp/v2/users/(?P<id>[\\d]+)']);
                }
                return $endpoints;
            });
        }
        public static function notify_login($user_login, $user) {
            $ip = self::get_client_ip();
            $to = $user->user_email;
            $subject = '[' . get_bloginfo('name') . '] 新设备登录提醒';
            $message = "您的账号刚刚在以下环境登录：\n\n";
            $message .= "时间：" . current_time('mysql') . "\n";
            $message .= "IP：" . $ip . "\n";
            $message .= "设备：" . esc_html($_SERVER['HTTP_USER_AGENT'] ?? 'Unknown') . "\n\n";
            $message .= "如非本人操作，请立即修改密码。";
            wp_mail($to, $subject, $message);
        }
        public static function log_success_login($user_login, $user) {
            self::add_log($user_login, 'success');
        }
        public static function log_failed_login($username) {
            self::add_log($username, 'failed');
        }
        private static function add_log($username, $status) {
            $log = get_option('mabox_login_log', array());
            $log[] = array(
                'time'   => current_time('mysql'),
                'user'   => $username,
                'ip'     => self::get_client_ip(),
                'status' => $status,
                'ua'     => esc_html($_SERVER['HTTP_USER_AGENT'] ?? ''),
            );
            if (count($log) > 1000) array_shift($log);
            update_option('mabox_login_log', $log);
        }
        public static function check_ip_whitelist() {
            if (!is_admin() || current_user_can('manage_options')) return;
            $whitelist = !empty(self::$config['ip_whitelist']) ? self::$config['ip_whitelist'] : '';
            $allowed = array_map('trim', explode("\n", $whitelist));
            $allowed = array_filter($allowed);
            if (empty($allowed)) return;
            $current_ip = self::get_client_ip();
            foreach ($allowed as $ip) {
                if ($current_ip === $ip) return;
            }
            wp_die('您的 IP 不在允许访问后台的白名单中。', '访问被拒绝', array('response' => 403));
        }
        private static function get_client_ip() {
            $keys = array('HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR');
            foreach ($keys as $key) {
                if (!empty($_SERVER[$key])) return sanitize_text_field($_SERVER[$key]);
            }
            return '0.0.0.0';
        }
    }
}