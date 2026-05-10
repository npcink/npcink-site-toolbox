<?php
if (!class_exists('MaBox_Domestic_Baidu_Push')) {
    class MaBox_Domestic_Baidu_Push {
        private static $config;
        public static function run($config) {
            self::$config = $config;
            if (!empty($config['active_push_enabled']) && !empty($config['site']) && !empty($config['token'])) {
                add_action('publish_post', array(__CLASS__, 'active_push'), 10, 2);
            }
            if (!empty($config['auto_push_enabled'])) {
                add_action('wp_footer', array(__CLASS__, 'auto_push_js'), 999);
            }
            if (!empty($config['batch_push_enabled']) && !empty($config['site']) && !empty($config['token'])) {
                add_action('wp_ajax_mabox_baidu_batch_push', array(__CLASS__, 'ajax_batch_push_deprecated'));
            }
        }
        public static function ajax_batch_push_deprecated() {
            _deprecated_function('wp_ajax_mabox_baidu_batch_push', '2.1.0', 'REST API POST /mabox/v1/domestic/baidu/push');
            self::ajax_batch_push();
        }
        }
        public static function active_push($post_id, $post) {
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
            if (wp_is_post_revision($post_id)) return;
            $url = get_permalink($post_id);
            if (!$url) return;
            $api = 'http://data.zz.baidu.com/urls?site=' . urlencode(self::$config['site']) . '&token=' . urlencode(self::$config['token']);
            $response = wp_remote_post($api, array(
                'body'    => $url,
                'headers' => array('Content-Type' => 'text/plain'),
                'timeout' => 30,
            ));
            if (!is_wp_error($response)) {
                $body = json_decode(wp_remote_retrieve_body($response), true);
                if (!empty($body['success'])) {
                    update_post_meta($post_id, '_mabox_baidu_pushed', current_time('mysql'));
                }
            }
        }
        public static function auto_push_js() {
            echo '<script>(function(){var bp=document.createElement("script");var curProtocol=window.location.protocol.split(":")[0];if(curProtocol==="https"){bp.src="https://zz.bdstatic.com/linksubmit/push.js";}else{bp.src="http://push.zhanzhang.baidu.com/push.js";}var s=document.getElementsByTagName("script")[0];s.parentNode.insertBefore(bp,s);})();</script>' . "\n";
        }
        public static function ajax_batch_push() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('权限不足', 403);
            }
            check_ajax_referer('mabox_save_nonce', 'nonce');
            $batch_size = 100;
            $offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
            $posts = get_posts(array(
                'posts_per_page' => $batch_size,
                'offset'         => $offset,
                'post_type'      => 'post',
                'post_status'    => 'publish',
                'fields'         => 'ids',
            ));
            if (empty($posts)) {
                wp_send_json_success(array('done' => true, 'message' => '批量推送完成'));
            }
            $urls = array();
            foreach ($posts as $post_id) {
                $url = get_permalink($post_id);
                if ($url) $urls[] = $url;
            }
            $api = 'http://data.zz.baidu.com/urls?site=' . urlencode(self::$config['site']) . '&token=' . urlencode(self::$config['token']);
            $response = wp_remote_post($api, array(
                'body'    => implode("\n", $urls),
                'headers' => array('Content-Type' => 'text/plain'),
                'timeout' => 60,
            ));
            if (is_wp_error($response)) {
                wp_send_json_error($response->get_error_message());
            }
            $body = json_decode(wp_remote_retrieve_body($response), true);
            wp_send_json_success(array(
                'done'    => false,
                'offset'  => $offset + count($posts),
                'result'  => $body,
                'count'   => count($urls),
            ));
        }
    }
}