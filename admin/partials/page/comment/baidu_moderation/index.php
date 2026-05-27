<?php

if (!class_exists('MaBox_Comment_Baidu_Moderation')) {
    class MaBox_Comment_Baidu_Moderation
    {
        private static $option;
        private static $access_token;

        public static function run($config)
        {
            self::$option = $config;
            add_filter('preprocess_comment', array(__CLASS__, 'moderate_comment'));
        }

        public static function moderate_comment($commentdata)
        {
            $content = isset($commentdata['comment_content']) ? $commentdata['comment_content'] : '';
            if (empty($content)) {
                return $commentdata;
            }

            $api_key = MaBox_Admin::get_config(self::$option, 'baidu_moderation_api_key', '');
            $secret_key = MaBox_Admin::get_config(self::$option, 'baidu_moderation_secret_key', '');

            if (empty($api_key) || empty($secret_key)) {
                return $commentdata;
            }

            $token = self::get_access_token($api_key, $secret_key);
            if (!$token) {
                // API 鉴权失败，记录日志并降级到本地敏感词过滤
                error_log('[MaBox] Baidu moderation token acquisition failed.');
                return self::fallback_filter($commentdata);
            }

            $result = self::check_content($content, $token);
            $action = MaBox_Admin::get_config(self::$option, 'baidu_moderation_action', 'mark');

            if ($result === 'non_compliant') {
                if ($action === 'block') {
                    wp_die(
                        esc_html__('您的评论未通过内容审核，请修改后重新提交。', 'magick-toolbox'),
                        esc_html__('评论被拦截', 'magick-toolbox'),
                        array('back_link' => true)
                    );
                }
                $commentdata['comment_approved'] = 0;
            } elseif ($result === 'unknown') {
                // API 调用异常，记录日志并降级到本地敏感词过滤
                error_log('[MaBox] Baidu moderation API returned unknown result, falling back to local filter.');
                return self::fallback_filter($commentdata);
            }

            return $commentdata;
        }

        private static function get_access_token($api_key, $secret_key)
        {
            if (!empty(self::$access_token)) {
                return self::$access_token;
            }

            $cached = get_transient('mabox_baidu_moderation_token');
            if ($cached) {
                self::$access_token = $cached;
                return $cached;
            }

            $url = 'https://aip.baidubce.com/oauth/2.0/token';
            $response = wp_remote_post($url, array(
                'body' => array(
                    'grant_type' => 'client_credentials',
                    'client_id' => $api_key,
                    'client_secret' => $secret_key,
                ),
                'timeout' => 10,
            ));

            if (is_wp_error($response)) {
                return '';
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['access_token'])) {
                $expires = isset($body['expires_in']) ? intval($body['expires_in']) - 300 : 2592000;
                set_transient('mabox_baidu_moderation_token', $body['access_token'], $expires);
                self::$access_token = $body['access_token'];
                return $body['access_token'];
            }

            return '';
        }

        private static function check_content($content, $token)
        {
            $url = 'https://aip.baidubce.com/rest/2.0/solution/v1/text_censor/v2/user_defined?access_token=' . $token;
            $response = wp_remote_post($url, array(
                'body' => array('text' => $content),
                'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
                'timeout' => 10,
            ));

            if (is_wp_error($response)) {
                return 'unknown';
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['conclusionType'])) {
                switch (intval($body['conclusionType'])) {
                    case 1:
                        return 'compliant';
                    case 2:
                        return 'non_compliant';
                    case 3:
                        return 'suspected_non_compliant';
                    case 4:
                        return 'non_compliant';
                    default:
                        return 'unknown';
                }
            }

            return 'unknown';
        }

        /**
         * 降级方案：当百度 API 不可用时，使用本地敏感词过滤
         */
        private static function fallback_filter($commentdata)
        {
            $content = isset($commentdata['comment_content']) ? $commentdata['comment_content'] : '';
            if (empty($content)) {
                return $commentdata;
            }

            // 尝试加载本地敏感词（如果敏感词功能已启用）
            $raw_words = MaBox_Admin::get_config(self::$option, 'sensitive_words', '');
            if (empty($raw_words)) {
                return $commentdata;
            }

            $words = array_filter(array_map('trim', explode("\n", trim($raw_words))));
            if (empty($words)) {
                return $commentdata;
            }

            $action = MaBox_Admin::get_config(self::$option, 'sensitive_words_action', 'replace');
            $replace_char = MaBox_Admin::get_config(self::$option, 'sensitive_words_replace_char', '***');

            foreach ($words as $word) {
                if ($word === '') {
                    continue;
                }
                if (mb_stripos($content, $word) !== false) {
                    if ($action === 'block') {
                        wp_die(
                            esc_html__('您的评论包含敏感词，请修改后重新提交。', 'magick-toolbox'),
                            esc_html__('评论被拦截', 'magick-toolbox'),
                            array('back_link' => true)
                        );
                    }
                    $content = str_ireplace($word, $replace_char, $content);
                }
            }

            $commentdata['comment_content'] = $content;
            return $commentdata;
        }
    }
}
