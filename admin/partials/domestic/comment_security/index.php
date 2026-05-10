<?php
if (!class_exists('MaBox_Domestic_Comment_Security')) {
    class MaBox_Domestic_Comment_Security {
        private static $config;
        public static function run($config) {
            self::$config = $config;
            if (!empty($config['blacklist_enabled'])) {
                add_filter('preprocess_comment', array(__CLASS__, 'check_blacklist'), 1);
            }
            if (!empty($config['link_limit_enabled'])) {
                add_filter('preprocess_comment', array(__CLASS__, 'check_link_limit'), 2);
            }
            if (!empty($config['nickname_filter_enabled'])) {
                add_filter('preprocess_comment', array(__CLASS__, 'check_nickname'), 3);
            }
            if (!empty($config['email_domain_enabled'])) {
                add_filter('preprocess_comment', array(__CLASS__, 'check_email_domain'), 4);
            }
            if (!empty($config['duplicate_enabled'])) {
                add_filter('preprocess_comment', array(__CLASS__, 'check_duplicate'), 5);
            }
            if (!empty($config['ip_rate_enabled'])) {
                add_filter('preprocess_comment', array(__CLASS__, 'check_ip_rate'), 6);
            }
            add_action('wp_set_comment_status', array(__CLASS__, 'log_spam_comment'), 10, 2);
        }
        public static function check_blacklist($commentdata) {
            $words = self::get_word_list('blacklist_words');
            if (empty($words)) return $commentdata;
            $text = $commentdata['comment_content'];
            foreach ($words as $word) {
                if (stripos($text, $word) !== false) {
                    $action = !empty(self::$config['blacklist_action']) ? self::$config['blacklist_action'] : 'block';
                    if ($action === 'block') {
                        wp_die('评论包含敏感词，已被拦截。', '评论拦截', array('response' => 403));
                    } else {
                        $commentdata['comment_approved'] = 0;
                        add_comment_meta($commentdata['comment_ID'] ?? 0, '_mabox_block_reason', '敏感词: ' . $word);
                    }
                    break;
                }
            }
            return $commentdata;
        }
        public static function check_link_limit($commentdata) {
            $limit = !empty(self::$config['link_limit_count']) ? intval(self::$config['link_limit_count']) : 2;
            preg_match_all('/<a\s/i', $commentdata['comment_content'], $matches);
            $count = count($matches[0]);
            if ($count > $limit) {
                $commentdata['comment_approved'] = 'spam';
                add_comment_meta($commentdata['comment_ID'] ?? 0, '_mabox_block_reason', '链接数量超限: ' . $count);
            }
            return $commentdata;
        }
        public static function check_nickname($commentdata) {
            $words = self::get_word_list('nickname_filter_words');
            if (empty($words)) return $commentdata;
            $name = $commentdata['comment_author'];
            foreach ($words as $word) {
                if (stripos($name, $word) !== false) {
                    wp_die('昵称包含不允许的词汇。', '昵称拦截', array('response' => 403));
                }
            }
            return $commentdata;
        }
        public static function check_email_domain($commentdata) {
            $domains = self::get_word_list('email_domain_blacklist');
            if (empty($domains)) return $commentdata;
            $email = $commentdata['comment_author_email'];
            $domain = substr(strrchr($email, '@'), 1);
            foreach ($domains as $d) {
                if (stripos($domain, trim($d)) !== false) {
                    wp_die('该邮箱域名不允许评论。', '邮箱拦截', array('response' => 403));
                }
            }
            return $commentdata;
        }
        public static function check_duplicate($commentdata) {
            global $wpdb;
            $content = $commentdata['comment_content'];
            $existing = $wpdb->get_var($wpdb->prepare(
                "SELECT comment_ID FROM $wpdb->comments WHERE comment_content = %s AND comment_approved != 'spam' LIMIT 1",
                $content
            ));
            if ($existing) {
                wp_die('请勿重复提交相同评论。', '重复拦截', array('response' => 403));
            }
            return $commentdata;
        }
        public static function check_ip_rate($commentdata) {
            $limit = !empty(self::$config['ip_rate_limit']) ? intval(self::$config['ip_rate_limit']) : 5;
            $window = !empty(self::$config['ip_rate_window']) ? intval(self::$config['ip_rate_window']) : 60;
            $ip = self::get_client_ip();
            $key = 'mabox_comment_rate_' . md5($ip);
            $count = get_transient($key);
            if ($count === false) $count = 0;
            $count++;
            if ($count > $limit) {
                wp_die('评论过于频繁，请 ' . $window . ' 秒后再试。', '频率限制', array('response' => 429));
            }
            set_transient($key, $count, $window);
            return $commentdata;
        }
        public static function log_spam_comment($comment_id, $status) {
            if (!empty(self::$config['log_enabled']) && $status === 'spam') {
                $comment = get_comment($comment_id);
                if ($comment) {
                    $log = get_option('mabox_spam_comment_log', array());
                    $log[] = array(
                        'time'    => current_time('mysql'),
                        'id'      => $comment_id,
                        'author'  => $comment->comment_author,
                        'email'   => $comment->comment_author_email,
                        'ip'      => $comment->comment_author_IP,
                        'content' => mb_substr($comment->comment_content, 0, 100),
                        'reason'  => get_comment_meta($comment_id, '_mabox_block_reason', true),
                    );
                    if (count($log) > 500) array_shift($log);
                    update_option('mabox_spam_comment_log', $log);
                }
            }
        }
        private static function get_word_list($key) {
            $text = !empty(self::$config[$key]) ? self::$config[$key] : '';
            if (empty($text)) return array();
            $words = array_map('trim', explode("\n", $text));
            return array_filter($words);
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