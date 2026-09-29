<?php

defined('ABSPATH') || exit;

/**
 * 未登录隐藏指定分类下的文章
 */

if (!class_exists('Npcink_Toolbox_Page_Hide_Category')) {
    class Npcink_Toolbox_Page_Hide_Category implements Npcink_Toolbox_Module_Interface
    {
        private static $id_array; //分类数组
        private static $tip_content; //提示信息
        public static function run($config = array())
        {
            self::$id_array = Npcink_Toolbox_Admin::get_config($config, 'category_id', array());
            self::$tip_content = Npcink_Toolbox_Admin::get_config($config, 'tip_content', '');
            // 查询级排除：未登录时把受限分类从列表、搜索与订阅源中整体移除，
            // 避免仅替换正文导致标题/摘要在归档和 RSS 中泄漏
            add_action('pre_get_posts', array(__CLASS__, 'exclude_from_listings'));
            add_action('the_content', array(__CLASS__, 'restrict_content_for_specific_categories'));
        }

        public static function exclude_from_listings($query)
        {
            if (Npcink_Toolbox_Helpers::is_logged_in() || is_admin() || !$query->is_main_query()) {
                return;
            }
            if (!($query->is_home() || $query->is_archive() || $query->is_feed() || $query->is_search())) {
                return;
            }
            $restricted = array_map('absint', array_filter((array) self::$id_array));
            if (empty($restricted)) {
                return;
            }
            $query->set('category__not_in', array_merge((array) $query->get('category__not_in'), $restricted));
        }

        public static function restrict_content_for_specific_categories($content)
        {
            $restricted_category_ids = self::$id_array;

            if (in_category($restricted_category_ids)) {
                if (!Npcink_Toolbox_Helpers::is_logged_in()) {
                    $content = wp_kses_post(self::$tip_content);
                }
            }
            return $content;
        }
    }
}
