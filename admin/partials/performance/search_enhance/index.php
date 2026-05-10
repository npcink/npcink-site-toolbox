<?php
if (!class_exists('MaBox_Performance_Search_Enhance')) {
    class MaBox_Performance_Search_Enhance {
        private static $config;
        public static function run($config) {
            self::$config = $config;
            if (!empty($config['highlight_enabled'])) {
                add_filter('the_excerpt', array(__CLASS__, 'highlight_search'));
                add_filter('the_title', array(__CLASS__, 'highlight_search'));
            }
            if (!empty($config['recommend_enabled'])) {
                add_action('loop_no_results', array(__CLASS__, 'show_recommendations'));
            }
            if (!empty($config['hotwords_enabled'])) {
                add_action('wp_ajax_mabox_search_log', array(__CLASS__, 'ajax_log_search_deprecated'));
                add_action('wp_ajax_nopriv_mabox_search_log', array(__CLASS__, 'ajax_log_search_deprecated'));
            }
        }
        public static function ajax_log_search_deprecated() {
            _deprecated_function('wp_ajax_mabox_search_log', '2.1.0', 'REST API POST /mabox/v1/public/search-log');
            self::ajax_log_search();
        }
        }
        public static function highlight_search($text) {
            if (!is_search()) return $text;
            $query = get_search_query();
            if (empty($query)) return $text;
            $highlight = '<mark style="background:#ffeb3b;padding:0 2px;">$1</mark>';
            return preg_replace('/(' . preg_quote($query, '/') . ')/iu', $highlight, $text);
        }
        public static function show_recommendations() {
            $tags = get_tags(array('orderby' => 'count', 'order' => 'DESC', 'number' => 5));
            if (empty($tags)) return;
            echo '<div class="mabox-search-recommend" style="margin:30px 0;text-align:center;">';
            echo '<h3 style="margin-bottom:15px;">未找到相关内容，试试这些热门标签：</h3>';
            echo '<div style="display:flex;flex-wrap:wrap;gap:10px;justify-content:center;">';
            foreach ($tags as $tag) {
                echo '<a href="' . esc_url(get_tag_link($tag->term_id)) . '" style="display:inline-block;padding:8px 16px;background:#f0f0f0;border-radius:20px;text-decoration:none;color:#333;">' . esc_html($tag->name) . '</a>';
            }
            echo '</div></div>';
        }
        public static function frontend_log_search($query) {
            if (!is_admin() && $query->is_main_query() && $query->is_search()) {
                $search_term = $query->get('s');
                if (!empty($search_term)) {
                    self::log_search_term($search_term);
                }
            }
            return $query;
        }
        public static function ajax_log_search() {
            check_ajax_referer('mabox_public_nonce', 'nonce');
            $term = isset($_POST['term']) ? sanitize_text_field($_POST['term']) : '';
            if (!empty($term)) {
                self::log_search_term($term);
            }
            wp_send_json_success();
        }
        private static function log_search_term($term) {
            $log = get_option('mabox_search_log', array());
            $today = current_time('Y-m-d');
            if (!isset($log[$today])) $log[$today] = array();
            if (!isset($log[$today][$term])) $log[$today][$term] = 0;
            $log[$today][$term]++;
            $keys = array_keys($log);
            if (count($keys) > 30) {
                unset($log[array_shift($keys)]);
            }
            update_option('mabox_search_log', $log);
        }
    }
}