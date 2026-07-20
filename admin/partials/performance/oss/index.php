<?php
defined('ABSPATH') || exit;
if (!class_exists('Npcink_Toolbox_Performance_Oss')) {
    class Npcink_Toolbox_Performance_Oss implements Npcink_Toolbox_Module_Interface {
        private const OFFLOADED_META = '_npcink_site_toolbox_oss_offloaded';
        private const CONNECTION_TEST_OBJECT = 'npcink-site-toolbox/connection-test.txt';
        private const CONNECTION_TEST_CONTENT = "Npcink Site Toolbox object storage connection test.\n";

        private static $config;

        public static function run($config = array()) {
            self::$config = $config;
            if (empty($config['enabled'])) return;
            add_filter('wp_generate_attachment_metadata', array(__CLASS__, 'sync_attachment_to_oss'), 20, 3);
            add_filter('wp_get_attachment_url', array(__CLASS__, 'replace_attachment_url'), 10, 2);
            add_filter('wp_calculate_image_srcset', array(__CLASS__, 'replace_srcset_urls'), 10, 5);
        }

        public static function sync_attachment_to_oss($metadata, $attachment_id, $context = '') {
            delete_post_meta($attachment_id, self::OFFLOADED_META);

            $files = self::collect_attachment_files($attachment_id, $metadata);
            if (empty($files)) {
                return $metadata;
            }

            $provider = !empty(self::$config['provider']) ? self::$config['provider'] : 'aliyun';
            foreach ($files as $file) {
                $result = self::do_upload($file, $provider);
                if (!$result || is_wp_error($result)) {
                    return $metadata;
                }
            }

            update_post_meta($attachment_id, self::OFFLOADED_META, self::target_fingerprint());
            return $metadata;
        }

        public static function replace_attachment_url($url, $post_id) {
            $domain = !empty(self::$config['domain']) ? self::$config['domain'] : '';
            if (empty($domain) || empty($post_id) || !self::is_offloaded_to_current_target($post_id)) {
                return $url;
            }

            $upload_dir = wp_upload_dir();
            $baseurl = $upload_dir['baseurl'];
            if (strpos($url, $baseurl) === 0) {
                $path = substr($url, strlen($baseurl));
                return rtrim($domain, '/') . $path;
            }
            return $url;
        }

        public static function replace_srcset_urls($sources, $size_array, $image_src, $image_meta, $attachment_id) {
            $domain = !empty(self::$config['domain']) ? self::$config['domain'] : '';
            if (empty($domain) || !is_array($sources) || !self::is_offloaded_to_current_target($attachment_id)) {
                return $sources;
            }

            foreach ($sources as &$source) {
                if (!empty($source['url'])) {
                    $source['url'] = self::replace_attachment_url($source['url'], $attachment_id);
                }
            }
            unset($source);

            return $sources;
        }

        /**
         * 使用当前设置草稿执行一次真实写入测试，不保存配置或改变模块启用状态。
         *
         * @param object $request WP_REST_Request 兼容对象。
         * @return array|WP_Error|WP_REST_Response
         */
        public static function rest_test_connection($request) {
            if (!current_user_can('manage_options')) {
                return new WP_Error(
                    'rest_forbidden',
                    __('权限不足', 'npcink-site-toolbox'),
                    array('status' => 403)
                );
            }

            $body = $request->get_json_params();
            $allowed_keys = array('settings', 'secretChanges');
            if (!is_array($body)
                || !empty(array_diff(array_keys($body), $allowed_keys))
                || !array_key_exists('settings', $body)
                || !is_array($body['settings'])
                || (isset($body['secretChanges']) && !is_array($body['secretChanges']))) {
                return new WP_Error(
                    'npcink_oss_invalid_request',
                    __('请求仅允许 settings 和 secretChanges', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }

            $secret_changes = isset($body['secretChanges']) ? $body['secretChanges'] : array();
            $merge = Npcink_Toolbox_Config_Manager::merge_secret_changes($body['settings'], $secret_changes);
            if (!$merge['success']) {
                return new WP_Error(
                    'npcink_oss_invalid_request',
                    $merge['error'],
                    array('status' => 400)
                );
            }

            $config = isset($merge['data']['performance']['oss'])
                && is_array($merge['data']['performance']['oss'])
                ? $merge['data']['performance']['oss']
                : array();
            $validated = self::validate_connection_config($config, false);
            if (is_wp_error($validated)) {
                return $validated;
            }

            $object_key = self::prefix_object_key(
                self::CONNECTION_TEST_OBJECT,
                $validated['path']
            );
            $started_at = microtime(true);
            $result = self::upload_content(
                self::CONNECTION_TEST_CONTENT,
                $object_key,
                $validated
            );
            $latency_ms = (int) round((microtime(true) - $started_at) * 1000);

            if (!$result || is_wp_error($result)) {
                return new WP_Error(
                    'npcink_oss_connection_failed',
                    __('无法写入测试对象，请检查凭据、Bucket、地域节点和写入权限。', 'npcink-site-toolbox'),
                    array('status' => 502)
                );
            }

            return rest_ensure_response(array(
                'success' => true,
                'message' => __('连接成功，已写入并覆盖测试对象。', 'npcink-site-toolbox'),
                'data' => array(
                    'provider' => $validated['provider'],
                    'objectKey' => $object_key,
                    'latencyMs' => max(0, $latency_ms),
                ),
            ));
        }

        private static function is_offloaded_to_current_target($attachment_id) {
            $stored_fingerprint = get_post_meta($attachment_id, self::OFFLOADED_META, true);
            return is_string($stored_fingerprint)
                && $stored_fingerprint !== ''
                && hash_equals(self::target_fingerprint(), $stored_fingerprint);
        }

        private static function target_fingerprint() {
            $provider = !empty(self::$config['provider']) ? self::$config['provider'] : 'aliyun';
            $bucket = !empty(self::$config['bucket']) ? self::$config['bucket'] : '';
            $path = isset(self::$config['path']) && is_string(self::$config['path'])
                ? self::$config['path']
                : '';
            $normalized_path = self::normalize_object_prefix($path);
            if (!is_wp_error($normalized_path)) {
                $path = $normalized_path;
            }
            $endpoint = '';
            if ($provider === 'aliyun') {
                $endpoint_value = isset(self::$config['endpoint']) && is_string(self::$config['endpoint'])
                    ? self::$config['endpoint']
                    : '';
                $normalized_endpoint = self::normalize_aliyun_endpoint($endpoint_value);
                if (!is_wp_error($normalized_endpoint)) {
                    $endpoint = $normalized_endpoint;
                } else {
                    $endpoint = $endpoint_value;
                }
            }
            $region = $provider === 'tencent' && !empty(self::$config['region'])
                ? self::$config['region']
                : '';
            $domain = !empty(self::$config['domain']) ? rtrim(self::$config['domain'], '/') : '';

            return hash('sha256', implode("\n", array(
                $provider,
                $bucket,
                $path,
                $endpoint,
                $region,
                $domain,
            )));
        }

        private static function collect_attachment_files($attachment_id, $metadata) {
            $main_file = get_attached_file($attachment_id);
            if (!is_string($main_file) || $main_file === '') {
                return array();
            }

            $files = array($main_file);
            $attachment_dir = dirname($main_file);

            if (is_array($metadata)) {
                if (!empty($metadata['original_image']) && is_string($metadata['original_image'])) {
                    $files[] = $attachment_dir . '/' . $metadata['original_image'];
                }

                if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
                    foreach ($metadata['sizes'] as $size) {
                        if (is_array($size) && !empty($size['file']) && is_string($size['file'])) {
                            $files[] = $attachment_dir . '/' . $size['file'];
                        }
                    }
                }
            }

            $upload_dir = wp_upload_dir();
            $upload_root = realpath($upload_dir['basedir']);
            if ($upload_root === false) {
                return array();
            }

            $upload_root = trailingslashit(wp_normalize_path($upload_root));
            $validated_files = array();
            foreach (array_unique($files) as $file) {
                $real_file = realpath($file);
                if ($real_file === false || !is_file($real_file) || !is_readable($real_file)) {
                    return array();
                }

                $normalized_file = wp_normalize_path($real_file);
                if (strpos($normalized_file, $upload_root) !== 0) {
                    return array();
                }

                $validated_files[] = $real_file;
            }

            return $validated_files;
        }

        private static function do_upload($file, $provider) {
            $upload_dir = wp_upload_dir();
            $upload_root = trailingslashit(wp_normalize_path($upload_dir['basedir']));
            $normalized_file = wp_normalize_path($file);
            if (strpos($normalized_file, $upload_root) !== 0) {
                return false;
            }

            $object_key = ltrim(substr($normalized_file, strlen($upload_root)), '/');
            if ($object_key === '') {
                return false;
            }

            $file_content = file_get_contents($file);
            if ($file_content === false) return false;

            $config = is_array(self::$config) ? self::$config : array();
            $config['provider'] = $provider;
            $validated = self::validate_connection_config($config);
            if (is_wp_error($validated)) {
                return false;
            }

            $object_key = self::prefix_object_key($object_key, $validated['path']);
            return self::upload_content($file_content, $object_key, $validated);
        }

        /**
         * @param array $config 对象存储完整配置。
         * @param bool  $require_public_url 是否要求完整公开访问地址。
         * @return array|WP_Error
         */
        private static function validate_connection_config($config, $require_public_url = true) {
            $provider = isset($config['provider']) && is_string($config['provider'])
                ? trim($config['provider'])
                : '';
            if (!in_array($provider, array('aliyun', 'tencent', 'qiniu'), true)) {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('请选择支持的对象存储服务商。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }

            $bucket = isset($config['bucket']) && is_string($config['bucket'])
                ? trim($config['bucket'])
                : '';
            if (!preg_match('/^[a-z0-9][a-z0-9-]{1,61}[a-z0-9]$/', $bucket)) {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('Bucket 格式无效，请填写 3 至 63 位小写字母、数字或连字符。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }
            if ($provider === 'tencent' && !preg_match('/-[0-9]+$/', $bucket)) {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('腾讯云 Bucket 需要包含 APPID 后缀，例如 npcink-media-1250000000。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }

            $path_value = isset($config['path']) && is_string($config['path'])
                ? $config['path']
                : '';
            $path = self::normalize_object_prefix($path_value);
            if (is_wp_error($path)) {
                return $path;
            }

            $endpoint = '';
            if ($provider === 'aliyun') {
                $endpoint_value = isset($config['endpoint']) && is_string($config['endpoint'])
                    ? $config['endpoint']
                    : '';
                $endpoint = self::normalize_aliyun_endpoint($endpoint_value);
                if (is_wp_error($endpoint)) {
                    return $endpoint;
                }
            }

            $region = isset($config['region']) && is_string($config['region'])
                ? trim($config['region'])
                : '';
            if ($provider === 'tencent' && $region === '') {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('腾讯云需要填写 Region。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }
            if ($provider === 'tencent'
                && !preg_match('/^[a-z0-9][a-z0-9-]{0,62}[a-z0-9]$/', $region)) {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('腾讯云 Region 格式无效，请填写 ap-beijing 这样的地域 ID。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }
            if ($provider !== 'tencent') {
                $region = '';
            }
            if ($provider === 'tencent'
                && strlen($bucket . '.cos.' . $region . '.myqcloud.com') > 60) {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('腾讯云 Bucket 与 Region 组成的请求域名不能超过 60 个字符。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }

            $domain = isset($config['domain']) && is_string($config['domain'])
                ? rtrim(trim($config['domain']), '/')
                : '';
            $domain_parts = $domain !== '' ? parse_url($domain) : false;
            if (($require_public_url && $domain === '')
                || ($domain !== '' && (
                    !is_array($domain_parts)
                    || empty($domain_parts['scheme'])
                    || empty($domain_parts['host'])
                    || !in_array(strtolower($domain_parts['scheme']), array('http', 'https'), true)
                    || isset($domain_parts['user'])
                    || isset($domain_parts['pass'])
                    || isset($domain_parts['query'])
                    || isset($domain_parts['fragment'])
                ))) {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('公开访问地址格式无效，请填写包含 http:// 或 https:// 的地址前缀。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }

            $access_key = isset($config['access_key']) && is_string($config['access_key'])
                ? trim($config['access_key'])
                : '';
            $secret_key = isset($config['secret_key']) && is_string($config['secret_key'])
                ? trim($config['secret_key'])
                : '';
            if ($access_key === '' || $secret_key === '') {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('请先完整填写 Access Key 和 Secret Key。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }

            return array(
                'provider' => $provider,
                'bucket' => $bucket,
                'path' => $path,
                'endpoint' => $endpoint,
                'region' => $region,
                'domain' => $domain,
                'access_key' => $access_key,
                'secret_key' => $secret_key,
            );
        }

        private static function upload_content($content, $object_key, $config) {
            if ($config['provider'] === 'aliyun') {
                return self::upload_aliyun(
                    $content,
                    $object_key,
                    $config['access_key'],
                    $config['secret_key'],
                    $config['bucket'],
                    $config['endpoint']
                );
            } elseif ($config['provider'] === 'tencent') {
                return self::upload_tencent(
                    $content,
                    $object_key,
                    $config['access_key'],
                    $config['secret_key'],
                    $config['bucket'],
                    $config['region']
                );
            } elseif ($config['provider'] === 'qiniu') {
                return self::upload_qiniu(
                    $content,
                    $object_key,
                    $config['access_key'],
                    $config['secret_key'],
                    $config['bucket']
                );
            }
            return false;
        }
        private static function upload_aliyun($content, $key, $ak, $sk, $bucket, $endpoint) {
            $host = $bucket . '.' . $endpoint;
            $request_path = self::encode_object_key($key);
            $date = gmdate('D, d M Y H:i:s T');
            $sign_str = "PUT\n\napplication/octet-stream\n" . $date . "\n/" . $bucket . "/" . $key;
            $signature = base64_encode(hash_hmac('sha1', $sign_str, $sk, true));
            $auth = 'OSS ' . $ak . ':' . $signature;
            $response = wp_remote_request('https://' . $host . $request_path, array(
                'method'  => 'PUT',
                'body'    => $content,
                'headers' => array(
                    'Host'           => $host,
                    'Date'           => $date,
                    'Authorization'  => $auth,
                    'Content-Type'   => 'application/octet-stream',
                ),
                'timeout' => 60,
            ));
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                return true;
            }
            return false;
        }
        private static function upload_tencent($content, $key, $ak, $sk, $bucket, $region) {
            $host = $bucket . '.cos.' . $region . '.myqcloud.com';
            $request_path = self::encode_object_key($key);
            $start_time = time();
            $end_time = $start_time + 3600;
            $auth = self::build_tencent_authorization(
                'PUT',
                $request_path,
                $host,
                $ak,
                $sk,
                $start_time,
                $end_time
            );
            $response = wp_remote_request('https://' . $host . $request_path, array(
                'method'  => 'PUT',
                'body'    => $content,
                'headers' => array(
                    'Host'           => $host,
                    'Authorization'  => $auth,
                    'Content-Type'   => 'application/octet-stream',
                ),
                'timeout' => 60,
            ));
            if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
                return true;
            }
            return false;
        }

        private static function build_tencent_authorization($method, $path, $host, $ak, $sk, $start_time, $end_time) {
            $key_time = $start_time . ';' . $end_time;
            $http_headers = 'host=' . rawurlencode(strtolower($host));
            $http_string = strtolower($method) . "\n" . urldecode($path) . "\n\n" . $http_headers . "\n";
            $sign_key = hash_hmac('sha1', $key_time, $sk);
            $string_to_sign = "sha1\n" . $key_time . "\n" . sha1($http_string) . "\n";
            $signature = hash_hmac('sha1', $string_to_sign, $sign_key);

            return 'q-sign-algorithm=sha1'
                . '&q-ak=' . rawurlencode($ak)
                . '&q-sign-time=' . $key_time
                . '&q-key-time=' . $key_time
                . '&q-header-list=host'
                . '&q-url-param-list='
                . '&q-signature=' . $signature;
        }

        private static function encode_object_key($key) {
            $segments = explode('/', ltrim($key, '/'));
            return '/' . implode('/', array_map('rawurlencode', $segments));
        }

        private static function upload_qiniu($content, $key, $ak, $sk, $bucket) {
            $upload_url = 'https://up.qiniup.com/';
            $token = self::qiniu_token($bucket, $ak, $sk);
            $boundary = wp_generate_password(24, false);
            $body = "--" . $boundary . "\r\n";
            $body .= "Content-Disposition: form-data; name=\"token\"\r\n\r\n" . $token . "\r\n";
            $body .= "--" . $boundary . "\r\n";
            $body .= "Content-Disposition: form-data; name=\"key\"\r\n\r\n" . $key . "\r\n";
            $body .= "--" . $boundary . "\r\n";
            $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"" . basename($key) . "\"\r\n";
            $body .= "Content-Type: application/octet-stream\r\n\r\n" . $content . "\r\n";
            $body .= "--" . $boundary . "--";
            $response = wp_remote_post($upload_url, array(
                'body'    => $body,
                'headers' => array('Content-Type' => 'multipart/form-data; boundary=' . $boundary),
                'timeout' => 60,
            ));
            if (!is_wp_error($response)) {
                $code = wp_remote_retrieve_response_code($response);
                if ($code === 200) {
                    return true;
                }
            }
            return false;
        }

        /**
         * 将可选目录前缀安全地添加到对象键。
         */
        private static function prefix_object_key($object_key, $path) {
            $object_key = ltrim((string) $object_key, '/');
            return $path === '' ? $object_key : $path . '/' . $object_key;
        }

        /**
         * 规范化用户填写的对象目录，不允许目录穿越或异常分隔符。
         *
         * @return string|WP_Error
         */
        private static function normalize_object_prefix($value) {
            $path = trim((string) $value);
            if ($path === '') {
                return '';
            }

            if (strlen($path) > 512 || preg_match('/[\\x00-\\x1F\\x7F\\\\?#]/', $path)) {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('上传目录格式无效，请使用字母、数字、中文、点、下划线、连字符和正斜杠。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }

            $path = trim($path, '/');
            $segments = explode('/', $path);
            foreach ($segments as $segment) {
                if ($segment === ''
                    || $segment === '.'
                    || $segment === '..'
                    || !preg_match('/^[\\p{L}\\p{N}._-]+$/u', $segment)) {
                    return new WP_Error(
                        'npcink_oss_invalid_config',
                        __('上传目录格式无效，请填写 www 或 uploads/site-a 这样的相对目录。', 'npcink-site-toolbox'),
                        array('status' => 400)
                    );
                }
            }

            return $path;
        }

        /**
         * 接受阿里云控制台 Endpoint 或常用 Region 快捷写法，并限制到官方域名形态。
         *
         * @return string|WP_Error
         */
        private static function normalize_aliyun_endpoint($value) {
            $endpoint = strtolower(trim((string) $value));
            if ($endpoint === '') {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('阿里云需要填写 Endpoint（地域节点）。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }

            if (strpos($endpoint, '://') === false && strpos($endpoint, '.') === false) {
                if (strpos($endpoint, 'oss-') !== 0) {
                    $endpoint = 'oss-' . $endpoint;
                }
                $endpoint .= '.aliyuncs.com';
            }

            $candidate = strpos($endpoint, '://') === false
                ? 'https://' . $endpoint
                : $endpoint;
            $parts = parse_url($candidate);
            if (!is_array($parts)
                || empty($parts['scheme'])
                || empty($parts['host'])
                || !in_array(strtolower($parts['scheme']), array('http', 'https'), true)
                || isset($parts['user'])
                || isset($parts['pass'])
                || isset($parts['port'])
                || isset($parts['query'])
                || isset($parts['fragment'])
                || (isset($parts['path']) && $parts['path'] !== '' && $parts['path'] !== '/')) {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('阿里云 Endpoint 格式无效，请粘贴地域节点，不要包含 Bucket、路径或查询参数。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }

            $host = strtolower(rtrim($parts['host'], '.'));
            $is_standard = preg_match('/^oss-[a-z0-9][a-z0-9-]*\\.aliyuncs\\.com$/', $host);
            $is_dual_stack = preg_match('/^[a-z0-9][a-z0-9-]*\\.oss\\.aliyuncs\\.com$/', $host);
            if (!$is_standard && !$is_dual_stack) {
                return new WP_Error(
                    'npcink_oss_invalid_config',
                    __('阿里云 Endpoint 必须是官方地域节点，不要填写 Bucket 域名或其他服务器地址。', 'npcink-site-toolbox'),
                    array('status' => 400)
                );
            }

            return $host;
        }

        private static function qiniu_token($bucket, $ak, $sk) {
            $policy = json_encode(array('scope' => $bucket, 'deadline' => time() + 3600));
            $encoded_policy = self::qiniu_base64_url_safe($policy);
            $sign = hash_hmac('sha1', $encoded_policy, $sk, true);
            $encoded_sign = self::qiniu_base64_url_safe($sign);
            return $ak . ':' . $encoded_sign . ':' . $encoded_policy;
        }
        private static function qiniu_base64_url_safe($data) {
            $encoded = base64_encode($data);
            return str_replace(array('+', '/'), array('-', '_'), $encoded);
        }
    }
}
