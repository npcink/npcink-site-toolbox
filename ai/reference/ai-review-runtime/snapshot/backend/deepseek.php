<?php
/**
 * DeepSeek Provider
 *
 * 使用 DeepSeek API 进行评论审核。
 *
 * @since 2.3.0
 */
if (!class_exists('MaBox_Ai_Provider_DeepSeek')) {
    class MaBox_Ai_Provider_DeepSeek implements MaBox_Ai_Provider_Interface {

        public function get_name() {
            return 'DeepSeek';
        }

        public function is_available($config) {
            return !empty($config['deepseek_api_key']);
        }

        public function review($text, $config) {
            $api_key = $config['deepseek_api_key'];
            $api_url = !empty($config['deepseek_api_url']) ? $config['deepseek_api_url'] : 'https://api.deepseek.com/v1/chat/completions';
            $model   = !empty($config['deepseek_model']) ? $config['deepseek_model'] : 'deepseek-chat';
            $strict  = !empty($config['strict_mode']);

            $strict_desc = $strict ? '严格模式：对任何疑似违规内容都要标记为不安全' : '宽松模式：仅对明显违规内容标记为不安全';

            $prompt = sprintf(
                '你是一位内容审核助手。请审核以下评论内容，判断是否包含广告、灌水、敏感内容或违规信息。
请仅返回 JSON 格式，不要返回其他内容：
{"is_safe": true/false, "confidence": 0.0-1.0, "reason": "审核原因", "risk_level": "safe/medium/high"}

审核标准：%s。

待审核内容：%s',
                $strict_desc,
                $text
            );

            $body = json_encode(array(
                'model'       => $model,
                'messages'    => array(
                    array('role' => 'system', 'content' => '你是专业的内容审核助手，请严格审核。仅返回JSON。'),
                    array('role' => 'user', 'content' => $prompt),
                ),
                'temperature' => 0.1,
                'max_tokens'  => 200,
            ));

            $response = wp_remote_post($api_url, array(
                'timeout' => 15,
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $api_key,
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

            $body_data = json_decode(wp_remote_retrieve_body($response), true);
            $content   = !empty($body_data['choices'][0]['message']['content']) ? $body_data['choices'][0]['message']['content'] : '';

            $json_match = array();
            if (preg_match('/\{[^}]+\}/s', $content, $json_match)) {
                $parsed = json_decode($json_match[0], true);
                if ($parsed && isset($parsed['is_safe'])) {
                    return array(
                        'is_safe'    => (bool) $parsed['is_safe'],
                        'confidence' => floatval($parsed['confidence']),
                        'reason'     => sanitize_text_field($parsed['reason']),
                        'risk_level' => sanitize_text_field($parsed['risk_level']),
                    );
                }
            }

            return array(
                'is_safe'    => true,
                'confidence' => 0.3,
                'reason'     => 'AI 返回格式解析失败',
                'risk_level' => 'safe',
            );
        }
    }
}
