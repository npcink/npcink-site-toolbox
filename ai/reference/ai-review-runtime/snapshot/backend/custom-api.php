<?php
/**
 * 自定义 API Provider
 *
 * 用户自行配置 endpoint、method、headers、body template。
 *
 * @since 2.3.0
 */
if (!class_exists('MaBox_Ai_Provider_Custom_Api')) {
    class MaBox_Ai_Provider_Custom_Api implements MaBox_Ai_Provider_Interface {

        public function get_name() {
            return '自定义 API';
        }

        public function is_available($config) {
            return !empty($config['custom_api_url']);
        }

        public function review($text, $config) {
            $url     = $config['custom_api_url'];
            $method  = !empty($config['custom_api_method']) ? $config['custom_api_method'] : 'POST';
            $headers = !empty($config['custom_api_headers']) ? json_decode($config['custom_api_headers'], true) : array();
            $body_template = !empty($config['custom_api_body_template']) ? $config['custom_api_body_template'] : '{"text": "{{text}}"}';

            if (!is_array($headers)) {
                $headers = array('Content-Type' => 'application/json');
            }

            $body = str_replace('{{text}}', json_encode($text, JSON_UNESCAPED_UNICODE), $body_template);

            $response = wp_remote_request($url, array(
                'method'  => $method,
                'timeout' => 15,
                'headers' => $headers,
                'body'    => $body,
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
            if (!$data) {
                return array(
                    'is_safe'    => true,
                    'confidence' => 0.3,
                    'reason'     => 'API 返回格式非 JSON',
                    'risk_level' => 'safe',
                );
            }

            return array(
                'is_safe'    => !empty($data['is_safe']) ? (bool) $data['is_safe'] : true,
                'confidence' => !empty($data['confidence']) ? floatval($data['confidence']) : 0.5,
                'reason'     => !empty($data['reason']) ? sanitize_text_field($data['reason']) : '',
                'risk_level' => !empty($data['risk_level']) ? sanitize_text_field($data['risk_level']) : 'safe',
            );
        }
    }
}
