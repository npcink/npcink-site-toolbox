<?php

defined('ABSPATH') || exit;

/**
 * 限制搜索频次
 * 限制未登录用户的搜索频率，防止恶意搜索
 */
if (!class_exists('Npcink_Toolbox_Page_Search_Limit')) {
    class Npcink_Toolbox_Page_Search_Limit implements Npcink_Toolbox_Module_Interface
    {
        private static $option;

        public static function run($config = array())
        {
            self::$option = $config;
            add_action('pre_get_posts', array(__CLASS__, 'check_search_limit'));
        }

        public static function check_search_limit($query)
        {
            if (!is_admin() && $query->is_search && $query->is_main_query()) {
                if (Npcink_Toolbox_Helpers::is_logged_in()) {
                    return;
                }

                $max_count = Npcink_Toolbox_Admin::get_config(self::$option, 'search_limit_count', 10);
                if (empty($max_count)) {
                    return;
                }

                $ip            = Npcink_Toolbox_Helpers::get_rate_limit_ip();
                $transient_key = 'npcink_site_toolbox_search_limit_' . md5($ip);
                $search_count  = get_transient($transient_key);

                if ($search_count === false) {
                    $search_count = 0;
                }

                if ($search_count >= $max_count) {
                    // 明确返回 429，而不是 wp_die 默认的 500
                    wp_die(
                        esc_html__('搜索过于频繁，请稍后再试。', 'npcink-site-toolbox'),
                        '',
                        array('response' => 429)
                    );
                }

                set_transient($transient_key, $search_count + 1, MINUTE_IN_SECONDS);
            }
        }
    }
}
