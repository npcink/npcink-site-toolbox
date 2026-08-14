<?php

defined('ABSPATH') || exit;

/**
 * 仅登录可搜索
 * 未登录用户无法使用搜索功能
 */
if (!class_exists('Npcink_Toolbox_Page_Login_Search')) {
    class Npcink_Toolbox_Page_Login_Search implements Npcink_Toolbox_Module_Interface
    {
        public static function run($config = array())
        {
            add_action('pre_get_posts', array(__CLASS__, 'check_login_search'));
        }

        public static function check_login_search($query)
        {
            if (!is_admin() && $query->is_search && $query->is_main_query()) {
                if (!Npcink_Toolbox_Helpers::is_logged_in()) {
                    wp_die(
                        esc_html__('请先登录后再使用搜索功能。', 'npcink-site-toolbox'),
                        esc_html__('需要登录', 'npcink-site-toolbox'),
                        array('response' => 403)
                    );
                }
            }
        }
    }
}
