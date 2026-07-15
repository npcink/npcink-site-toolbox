<?php
if (!class_exists('MaBox_Performance_Oss')) {
    class MaBox_Performance_Oss implements MaBox_Module_Interface {
        private static $config;
        public static function run($config = array()) {
            self::$config = $config;
            if (empty($config['enabled'])) return;
            add_filter('wp_handle_upload', array(__CLASS__, 'upload_to_oss'));
            add_filter('wp_get_attachment_url', array(__CLASS__, 'replace_attachment_url'), 10, 2);
            add_filter('wp_calculate_image_srcset', array(__CLASS__, 'replace_srcset_urls'), 10, 5);
        }
        public static function upload_to_oss($upload) {
            $file = $upload['file'];
            $url = $upload['url'];
            $provider = !empty(self::$config['provider']) ? self::$config['provider'] : 'aliyun';
            $result = self::do_upload($file, $provider);
            if ($result && !is_wp_error($result)) {
                $upload['url'] = $result;
                if (!empty(self::$config['delete_local'])) {
                    @unlink($file);
                }
            }
            return $upload;
        }
        public static function replace_attachment_url($url, $post_id) {
            $domain = !empty(self::$config['domain']) ? self::$config['domain'] : '';
            if (empty($domain)) return $url;
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
            if (empty($domain)) return $sources;
            foreach ($sources as &$source) {
                if (!empty($source['url'])) {
                    $source['url'] = self::replace_attachment_url($source['url'], $attachment_id);
                }
            }
            return $sources;
        }
        private static function do_upload($file, $provider) {
            $domain = !empty(self::$config['domain']) ? self::$config['domain'] : '';
            $bucket = !empty(self::$config['bucket']) ? self::$config['bucket'] : '';
            $region = !empty(self::$config['region']) ? self::$config['region'] : '';
            $access_key = !empty(self::$config['access_key']) ? self::$config['access_key'] : '';
            $secret_key = !empty(self::$config['secret_key']) ? self::$config['secret_key'] : '';
            if (empty($domain) || empty($bucket) || empty($access_key) || empty($secret_key)) {
                return false;
            }
            $upload_dir = wp_upload_dir();
            $object_key = str_replace(trailingslashit($upload_dir['basedir']), '', $file);
            $object_key = ltrim($object_key, '/');
            $file_content = file_get_contents($file);
            if ($file_content === false) return false;
            if ($provider === 'aliyun') {
                return self::upload_aliyun($file_content, $object_key, $access_key, $secret_key, $bucket, $region, $domain);
            } elseif ($provider === 'tencent') {
                return self::upload_tencent($file_content, $object_key, $access_key, $secret_key, $bucket, $region, $domain);
            } elseif ($provider === 'qiniu') {
                return self::upload_qiniu($file_content, $object_key, $access_key, $secret_key, $bucket, $domain);
            }
            return false;
        }
        private static function upload_aliyun($content, $key, $ak, $sk, $bucket, $region, $domain) {
            $host = $bucket . '.oss-' . $region . '.aliyuncs.com';
            $date = gmdate('D, d M Y H:i:s T');
            $sign_str = "PUT\n\napplication/octet-stream\n" . $date . "\n/" . $bucket . "/" . $key;
            $signature = base64_encode(hash_hmac('sha1', $sign_str, $sk, true));
            $auth = 'OSS ' . $ak . ':' . $signature;
            $response = wp_remote_request('https://' . $host . '/' . $key, array(
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
                return rtrim($domain, '/') . '/' . $key;
            }
            return false;
        }
        private static function upload_tencent($content, $key, $ak, $sk, $bucket, $region, $domain) {
            $host = $bucket . '.cos.' . $region . '.myqcloud.com';
            $date = gmdate('D, d M Y H:i:s T');
            $sign_str = "put\n\n\n" . $date . "\n/" . $bucket . "/" . $key;
            $signature = base64_encode(hash_hmac('sha1', $sign_str, $sk, true));
            $auth = 'q-sign-algorithm=sha1&q-ak=' . $ak . '&q-sign-time=' . time() . ';' . (time() + 3600) . '&q-key-time=' . time() . ';' . (time() + 3600) . '&q-header-list=host&q-url-param-list=&q-signature=' . rawurlencode($signature);
            $response = wp_remote_request('https://' . $host . '/' . $key, array(
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
                return rtrim($domain, '/') . '/' . $key;
            }
            return false;
        }
        private static function upload_qiniu($content, $key, $ak, $sk, $bucket, $domain) {
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
                    return rtrim($domain, '/') . '/' . $key;
                }
            }
            return false;
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
