<?php
/**
 * 限制搜索频次
 * 限制未登录用户的搜索频率，防止恶意搜索
 */
if (!class_exists('MaBox_Page_Search_Limit')) {
    class MaBox_Page_Search_Limit
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_action('pre_get_posts', array(__CLASS__, 'check_search_limit'));
        }

        public static function check_search_limit($query)
        {
            if (!is_admin() && $query->is_search && $query->is_main_query()) {
                if (MaBox_Helpers::is_logged_in()) {
                    return;
                }

                $max_count = MaBox_Admin::get_config(self::$option, 'search_limit_count', 10);
                if (empty($max_count)) {
                    return;
                }

                $ip = MaBox_Helpers::get_real_ip();
                $transient_key = 'mabox_search_limit_' . md5($ip);
                $search_count = get_transient($transient_key);

                if ($search_count === false) {
                    $search_count = 0;
                }

                if ($search_count >= $max_count) {
                    wp_die(esc_html__('搜索过于频繁，请稍后再试。'));
                }

                set_transient($transient_key, $search_count + 1, MINUTE_IN_SECONDS);
            }
        }
    }
}
