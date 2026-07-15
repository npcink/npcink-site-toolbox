<?php
/**
 * 阿里云内容安全 Provider
 *
 * 使用阿里云内容安全 API 进行审核。
 *
 * @since 2.3.0
 */
if (!class_exists('MaBox_Ai_Provider_Aliyun')) {
    class MaBox_Ai_Provider_Aliyun implements MaBox_Ai_Provider_Interface {

        public function get_name() {
            return '阿里云内容安全';
        }

        public function is_available($config) {
            return !empty($config['aliyun_access_key']) && !empty($config['aliyun_secret_key']);
        }

        public function review($text, $config) {
            $access_key = $config['aliyun_access_key'];
            $secret_key = $config['aliyun_secret_key'];
            $region     = !empty($config['aliyun_region']) ? $config['aliyun_region'] : 'cn-shanghai';

            $host = 'green.cn-' . $region . '.aliyuncs.com';
            $uri  = '/green/text/scan/v1';
            $method = 'POST';

            $biz_data = array(
                'scenes' => array('antispam'),
                'tasks'  => array(
                    array('content' => $text),
                ),
            );

            $body     = json_encode($biz_data);
            $date     = gmdate('D, d M Y H:i:s \G\M\T');
            $content_md5 = base64_encode(md5($body, true));
            $content_type = 'application/json';

            $string_to_sign = sprintf("%s\n%s\n%s\n%s\n%s", $method, $content_md5, $content_type, $date, $uri);
            $signature = base64_encode(hash_hmac('sha1', $string_to_sign, $secret_key, true));

            $url = 'https://' . $host . $uri;
            $response = wp_remote_post($url, array(
                'timeout' => 15,
                'headers' => array(
                    'Content-Type'    => $content_type,
                    'Content-MD5'     => $content_md5,
                    'Date'            => $date,
                    'Authorization'   => 'ACS ' . $access_key . ':' . $signature,
                    'x-acs-signature-method' => 'HMAC-SHA1',
                    'x-acs-signature-version' => '1.0',
                ),
                'body' => $body,
            ));

            if (is_wp_error($response)) {
                return array(
                    'is_safe'    => true,
                    'confidence' => 0.3,
                    'reason'     => 'API 请求失败：' . $response->get_error_message(),
                    'risk_level' => 'safe',
                );
            }

            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                return array(
                    'is_safe'    => true,
                    'confidence' => 0.3,
                    'reason'     => 'API 返回错误（HTTP ' . $status_code . '）',
                    'risk_level' => 'safe',
                );
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (empty($data['data']['results'][0]['scenes'][0]['suggestion'])) {
                return array(
                    'is_safe'    => true,
                    'confidence' => 0.3,
                    'reason'     => 'API 返回格式异常',
                    'risk_level' => 'safe',
                );
            }

            $scene    = $data['data']['results'][0]['scenes'][0];
            $suggestion = $scene['suggestion'];
            $label      = !empty($scene['label']) ? $scene['label'] : '';
            $rate       = !empty($scene['rate']) ? $scene['rate'] / 100 : 0.5;

            return array(
                'is_safe'    => $suggestion === 'pass',
                'confidence' => $rate,
                'reason'     => $suggestion === 'pass' ? '审核通过' : '命中标签：' . $label,
                'risk_level' => $suggestion === 'pass' ? 'safe' : ($suggestion === 'review' ? 'medium' : 'high'),
            );
        }
    }
}
