<?php

defined('ABSPATH') || exit;

/**
 * 未登录隐藏指定页面
 */

if (!class_exists('Npcink_Toolbox_Page_Hide_Page')) {
    class Npcink_Toolbox_Page_Hide_Page implements Npcink_Toolbox_Module_Interface
    {
        private static $id_array; //分类数组
        private static $tip_content; //提示信息
        public static function run($config = array())
        {
            self::$id_array    = Npcink_Toolbox_Admin::get_config($config, 'page_id', array());
            self::$tip_content = Npcink_Toolbox_Admin::get_config($config, 'tip_content', '');
            // 查询级排除：未登录时把受限页面从搜索结果中移除，正文替换继续兜底
            add_action('pre_get_posts', array(__CLASS__, 'exclude_from_search'));
            add_action('the_content', array(__CLASS__, 'restrict_content_for_specific_categories'));
        }

        public static function exclude_from_search($query)
        {
            if (Npcink_Toolbox_Helpers::is_logged_in() || is_admin() || !$query->is_main_query()) {
                return;
            }
            if (!$query->is_search) {
                return;
            }
            $page_ids = array_map('absint', array_filter( (array) self::$id_array));
            if (empty($page_ids)) {
                return;
            }
            $query->set('post__not_in', array_merge( (array) $query->get('post__not_in'), $page_ids));
        }

        public static function restrict_content_for_specific_categories($content)
        {
            // 定义受限的分类ID数组
            $page_ids = array_map('absint', (array) self::$id_array);

            //当前是页面类型，且当前页面ID在指定数组中
            if (is_page() && in_array(absint(get_the_ID()), $page_ids, true)) {
                // 如果用户未登录，则将文章内容替换为登录提示
                if (!Npcink_Toolbox_Helpers::is_logged_in()) {
                    $content = wp_kses_post(self::$tip_content);
                }
            }
            return $content;
        }
    }
}
