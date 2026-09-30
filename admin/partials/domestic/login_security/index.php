<?php
defined('ABSPATH') || exit;
if (!class_exists('Npcink_Toolbox_Domestic_Login_Security')) {
    class Npcink_Toolbox_Domestic_Login_Security implements Npcink_Toolbox_Module_Interface {
        const LOCK_ERROR_CODE = 'npcink_site_toolbox_login_attempt_locked';

        private static $config = array();

        public static function run($config = array()) {
            self::$config = self::normalize_config($config);

            if (
                self::$config['attempt_limit_enabled']
                && !self::is_protection_disabled()
            ) {
                // wp_authenticate_user 在 WordPress 校验密码哈希前运行。
                add_filter('wp_authenticate_user', array(__CLASS__, 'check_attempt_lock'), 5, 2);
                add_action('wp_login_failed', array(__CLASS__, 'record_failed_login'), 10, 2);
                add_action('wp_login', array(__CLASS__, 'clear_attempt_state'), 10, 2);
            }

            if (self::$config['anonymous_author_guard_enabled']) {
                add_action('template_redirect', array(__CLASS__, 'guard_anonymous_author_query'), 0);
                add_filter('rest_endpoints', array(__CLASS__, 'filter_anonymous_user_endpoints'));
            }
        }

        public static function check_attempt_lock($user, $password) {
            if (self::is_protection_disabled() || self::is_wp_error_instance($user)) {
                return $user;
            }

            $user_id   = self::get_user_id($user);
            $client_ip = self::resolve_client_ip();
            if ($user_id < 1 || $client_ip === null) {
                return $user;
            }

            if (!self::has_active_lock(self::lock_key($user_id, $client_ip))) {
                return $user;
            }

            return new WP_Error(
                self::LOCK_ERROR_CODE,
                __('登录尝试过多，请稍后再试。', 'npcink-site-toolbox')
            );
        }

        public static function record_failed_login($username, $error = null) {
            if (self::is_protection_disabled() || self::is_own_lock_error($error)) {
                return;
            }

            $user_id   = self::resolve_existing_user_id($username);
            $client_ip = self::resolve_client_ip();
            if ($user_id < 1 || $client_ip === null) {
                return;
            }

            $counter_key = self::counter_key($user_id, $client_ip);
            $lock_key    = self::lock_key($user_id, $client_ip);
            if (self::has_active_lock($lock_key)) {
                return;
            }

            $now            = time();
            $window_seconds = self::$config['attempt_window_minutes'] * MINUTE_IN_SECONDS;
            $state          = get_transient($counter_key);

            if (
                !is_array($state)
                || !isset($state['count'], $state['window_expires_at'])
                || !is_int($state['count'])
                || !is_int($state['window_expires_at'])
                || $state['window_expires_at'] <= $now
            ) {
                $state = array(
                    'count'             => 0,
                    'window_expires_at' => $now + $window_seconds,
                );
            }

            ++$state['count'];

            if ($state['count'] >= self::$config['attempt_limit_count']) {
                $lock_seconds = self::$config['lock_duration_minutes'] * MINUTE_IN_SECONDS;
                set_transient(
                    $lock_key,
                    array('expires_at' => $now + $lock_seconds),
                    $lock_seconds
                );
                delete_transient($counter_key);
                return;
            }

            $remaining = max(1, $state['window_expires_at'] - $now);
            set_transient($counter_key, $state, $remaining);
        }

        public static function clear_attempt_state($user_login, $user) {
            if (self::is_protection_disabled()) {
                return;
            }

            $user_id   = self::get_user_id($user);
            $client_ip = self::resolve_client_ip();
            if ($user_id < 1 || $client_ip === null) {
                return;
            }

            delete_transient(self::counter_key($user_id, $client_ip));
            delete_transient(self::lock_key($user_id, $client_ip));
        }

        public static function guard_anonymous_author_query() {
            if (!self::is_anonymous_numeric_author_request()) {
                return;
            }

            wp_safe_redirect(home_url('/'));
            exit;
        }

        public static function filter_anonymous_user_endpoints($endpoints) {
            if (
                is_user_logged_in()
                || !is_array($endpoints)
            ) {
                return $endpoints;
            }

            foreach (array_keys($endpoints) as $route) {
                if (
                    is_string($route)
                    && ($route === '/wp/v2/users' || strpos($route, '/wp/v2/users/') === 0)
                ) {
                    unset($endpoints[ $route ]);
                }
            }

            return $endpoints;
        }

        private static function normalize_config($config) {
            $config = is_array($config) ? $config : array();

            return array(
                'attempt_limit_enabled'          => self::to_boolean(
                    isset($config['attempt_limit_enabled']) ? $config['attempt_limit_enabled'] : false
                ),
                'attempt_limit_count'            => self::bounded_integer($config, 'attempt_limit_count', 5, 2, 20),
                'attempt_window_minutes'         => self::bounded_integer($config, 'attempt_window_minutes', 15, 1, 1440),
                'lock_duration_minutes'          => self::bounded_integer($config, 'lock_duration_minutes', 30, 1, 1440),
                'trusted_proxies'                => isset($config['trusted_proxies']) && is_string($config['trusted_proxies'])
                    ? $config['trusted_proxies']
                    : '',
                'anonymous_author_guard_enabled' => self::to_boolean(
                    isset($config['anonymous_author_guard_enabled'])
                        ? $config['anonymous_author_guard_enabled']
                        : false
                ),
            );
        }

        // phpcs:ignore Universal.NamingConventions.NoReservedKeywordParameterNames.defaultFound -- 参数名沿用既有公共签名
        private static function bounded_integer($config, $key, $default, $minimum, $maximum) {
            if (!isset($config[ $key ]) || !is_int($config[ $key ])) {
                return $default;
            }

            return max($minimum, min($maximum, $config[ $key ]));
        }

        private static function to_boolean($value) {
            return filter_var($value, FILTER_VALIDATE_BOOLEAN) === true;
        }

        private static function is_protection_disabled() {
            return defined('NPCINK_SITE_TOOLBOX_DISABLE_LOGIN_PROTECTION')
                && (bool) constant('NPCINK_SITE_TOOLBOX_DISABLE_LOGIN_PROTECTION');
        }

        private static function is_wp_error_instance($value) {
            return class_exists('WP_Error') && $value instanceof WP_Error;
        }

        private static function is_own_lock_error($error) {
            if (!self::is_wp_error_instance($error) || !is_callable(array($error, 'get_error_codes'))) {
                return false;
            }

            return in_array(self::LOCK_ERROR_CODE, $error->get_error_codes(), true);
        }

        private static function resolve_existing_user_id($username) {
            if (!is_string($username) && !is_numeric($username)) {
                return 0;
            }

            $identifier = trim( (string) $username);
            if ($identifier === '') {
                return 0;
            }

            $user = get_user_by('login', $identifier);
            if (!$user && is_email($identifier)) {
                $user = get_user_by('email', $identifier);
            }

            return self::get_user_id($user);
        }

        private static function get_user_id($user) {
            if (!is_object($user) || !isset($user->ID)) {
                return 0;
            }

            return max(0, intval($user->ID));
        }

        private static function counter_key($user_id, $client_ip) {
            return 'npcink_site_toolbox_login_attempt_' . self::state_hash($user_id, $client_ip);
        }

        private static function lock_key($user_id, $client_ip) {
            return 'npcink_site_toolbox_login_lock_' . self::state_hash($user_id, $client_ip);
        }

        private static function state_hash($user_id, $client_ip) {
            $salt = function_exists('wp_salt') ? wp_salt('auth') : 'npcink-site-toolbox-login-protection';
            return hash_hmac('sha256', intval($user_id) . '|' . $client_ip, $salt);
        }

        private static function has_active_lock($key) {
            $lock = get_transient($key);
            if ($lock === false) {
                return false;
            }

            if (
                !is_array($lock)
                || !isset($lock['expires_at'])
                || !is_int($lock['expires_at'])
                || $lock['expires_at'] <= time()
            ) {
                delete_transient($key);
                return false;
            }

            return true;
        }

        private static function resolve_client_ip() {
            if (!isset($_SERVER['REMOTE_ADDR']) || !is_string($_SERVER['REMOTE_ADDR'])) {
                return null;
            }

            $remote_addr = self::normalize_ip(
                sanitize_text_field(wp_unslash($_SERVER['REMOTE_ADDR']))
            );
            if ($remote_addr === null) {
                return null;
            }

            $trusted_proxies = self::get_trusted_proxies();
            if (!in_array($remote_addr, $trusted_proxies, true)) {
                return $remote_addr;
            }

            if (
                !isset($_SERVER['HTTP_X_FORWARDED_FOR'])
                || !is_string($_SERVER['HTTP_X_FORWARDED_FOR'])
            ) {
                return null;
            }

            $forwarded_for = sanitize_text_field(
                wp_unslash($_SERVER['HTTP_X_FORWARDED_FOR'])
            );
            if (trim($forwarded_for) === '') {
                return null;
            }

            $forwarded_chain = explode(',', $forwarded_for);
            $chain           = array();
            foreach ($forwarded_chain as $forwarded_ip) {
                $normalized = self::normalize_ip($forwarded_ip);
                if ($normalized === null) {
                    return null;
                }
                $chain[] = $normalized;
            }
            $chain[] = $remote_addr;

            for ($index = count($chain) - 1; $index >= 0; $index--) {
                if (in_array($chain[ $index ], $trusted_proxies, true)) {
                    continue;
                }

                return $chain[ $index ];
            }

            return null;
        }

        private static function get_trusted_proxies() {
            if (self::$config['trusted_proxies'] === '') {
                return array();
            }

            $trusted = array();
            $lines   = preg_split('/\r\n|\r|\n/', self::$config['trusted_proxies']);
            if (!is_array($lines)) {
                return array();
            }
            foreach ($lines as $line) {
                if (trim($line) === '') {
                    continue;
                }
                $normalized = self::normalize_ip($line);
                if ($normalized === null) {
                    return array();
                }
                $trusted[ $normalized ] = true;
            }

            return array_keys($trusted);
        }

        private static function normalize_ip($value) {
            if (!is_string($value)) {
                return null;
            }

            $value = trim($value);
            if ($value === '' || filter_var($value, FILTER_VALIDATE_IP) === false) {
                return null;
            }

            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- 失败为预期分支，靠返回值判断
            $packed = @inet_pton($value);
            if ($packed === false) {
                return null;
            }

            // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- 失败为预期分支，靠返回值判断
            $normalized = @inet_ntop($packed);
            return is_string($normalized) ? strtolower($normalized) : null;
        }

        private static function is_anonymous_numeric_author_request() {
            if (is_user_logged_in()) {
                return false;
            }

            // This only recognizes a read-only routing query; it does not mutate state.
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anonymous author routing inspection cannot require a nonce.
            if (!isset($_GET['author']) || !is_string($_GET['author'])) {
                return false;
            }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Anonymous author routing inspection cannot require a nonce.
            $author = trim(sanitize_text_field(wp_unslash($_GET['author'])));
            if ($author === '') {
                return false;
            }

            // 与 WP_Query::parse_query() 对 author 的清洗语义保持一致，
            // 防止 x1、+1 等原始值在此处漏过、随后又被核心归一为作者 1。
            $normalized = preg_replace('|[^0-9,-]|', '', $author);
            if (!is_string($normalized) || $normalized === '') {
                return false;
            }

            $authors = preg_split('/[\s,]+/', $normalized);
            if (!is_array($authors)) {
                return false;
            }

            foreach ($authors as $author_id) {
                if (intval($author_id) > 0) {
                    return true;
                }
            }

            return false;
        }
    }
}
