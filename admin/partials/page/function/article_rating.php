<?php
/**
 * 文章评分功能
 * 用户可以对文章进行评分，支持匿名用户（IP + Cookie 追踪）
 */
if (!class_exists('MaBox_Page_Article_Rating')) {
    class MaBox_Page_Article_Rating
    {
        public static function run()
        {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_assets'));
add_action('wp_ajax_submit_rating', array(__CLASS__, 'handle_rating_deprecated'));
add_action('wp_ajax_nopriv_submit_rating', array(__CLASS__, 'handle_rating_deprecated'));
            add_filter('the_content', array(__CLASS__, 'append_rating_widget'));
        }

        public static function load_assets()
        {
            if (is_single()) {
                wp_enqueue_script('mabox-rating', plugin_dir_url(__FILE__) . 'article_rating.js', array('jquery'), MAGICK_MIXTURE_VERSION, true);
                wp_localize_script('mabox-rating', 'maboxRating', array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('mabox_rating_nonce'),
                    'post_id' => get_the_ID(),
                ));
            }
        }

        public static function append_rating_widget($content)
        {
            if (!is_single()) {
                return $content;
            }
            $post_id = get_the_ID();
            $rating_count = get_post_meta($post_id, 'mabox_rating_count', true) ?: 0;
            $rating_total = get_post_meta($post_id, 'mabox_rating_total', true) ?: 0;
            $average = $rating_count > 0 ? round($rating_total / $rating_count, 1) : 0;

            $widget = '<div class="mabox-rating" data-post-id="' . $post_id . '">';
            $widget .= '<span class="rating-stars">';
            for ($i = 1; $i <= 5; $i++) {
                $widget .= '<span class="star" data-value="' . $i . '">' . ($i <= $average ? '★' : '☆') . '</span>';
            }
            $widget .= '</span>';
            $widget .= '<span class="rating-text">' . $average . ' 分 (' . $rating_count . ' 人评分)</span>';
            $widget .= '</div>';

            return $content . $widget;
        }

        public static function handle_rating_deprecated() {
            _deprecated_function('wp_ajax_submit_rating', '2.1.0', 'REST API POST /mabox/v1/public/rating');
            self::handle_rating();
        }

        public static function handle_rating()
        {
            check_ajax_referer('mabox_rating_nonce', 'nonce');

            $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
            $rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;

            if (!$post_id || $rating < 1 || $rating > 5) {
                wp_send_json_error('无效的评分');
            }

            $ip = $_SERVER['REMOTE_ADDR'];
            $cookie_key = 'mabox_rated_' . $post_id;

            if (isset($_COOKIE[$cookie_key])) {
                wp_send_json_error('您已经评分过了');
            }

            $rated_ips = get_post_meta($post_id, 'mabox_rated_ips', true) ?: array();
            if (in_array($ip, $rated_ips)) {
                wp_send_json_error('您已经评分过了');
            }

            $rating_count = get_post_meta($post_id, 'mabox_rating_count', true) ?: 0;
            $rating_total = get_post_meta($post_id, 'mabox_rating_total', true) ?: 0;

            update_post_meta($post_id, 'mabox_rating_count', $rating_count + 1);
            update_post_meta($post_id, 'mabox_rating_total', $rating_total + $rating);

            $rated_ips[] = $ip;
            update_post_meta($post_id, 'mabox_rated_ips', $rated_ips);

            setcookie($cookie_key, '1', time() + YEAR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN);

            $new_average = round(($rating_total + $rating) / ($rating_count + 1), 1);
            wp_send_json_success(array('average' => $new_average, 'count' => $rating_count + 1));
        }
    }
}
