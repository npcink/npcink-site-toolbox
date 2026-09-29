<?php

defined('ABSPATH') || exit;

/**
 * 未登录隐藏指定标签下的文章
 */

if (!class_exists('Npcink_Toolbox_Page_Hide_Tag')) {
    class Npcink_Toolbox_Page_Hide_Tag implements Npcink_Toolbox_Module_Interface
    {
        private static $id_array; //标签数组
        private static $tip_content; //提示信息
        public static function run($config = array())
        {
            self::$id_array = Npcink_Toolbox_Admin::get_config($config, 'tag_id', array());
            self::$tip_content = Npcink_Toolbox_Admin::get_config($config, 'tip_content', '');
            // 查询级排除：未登录时把受限标签文章从列表、搜索与订阅源中整体移除
            add_action('pre_get_posts', array(__CLASS__, 'exclude_from_listings'));
            add_action('the_content', array(__CLASS__, 'restrict_content_for_specific_tags')); //隐藏标签下的文章
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
            $query->set('tag__not_in', array_merge((array) $query->get('tag__not_in'), $restricted));
        }

        //隐藏指定标签下的文章
        public static function restrict_content_for_specific_tags($content)
        {
            // 定义受限的标签ID数组
            $restricted_tag_ids = self::$id_array; // 将这里替换为你想要限制的标签ID数组

            // 获取当前文章的所有标签
            $post_tags = get_the_tags();

            // 检查文章的标签是否属于受限的标签
            if ($post_tags) {
                $post_tag_ids = array();
                foreach ($post_tags as $tag) {
                    $post_tag_ids[] = $tag->term_id;
                }
                if (array_intersect($post_tag_ids, $restricted_tag_ids)) {
                    if (!Npcink_Toolbox_Helpers::is_logged_in()) {
                        // 如果用户未登录，则将文章内容替换为登录提示
                        $content = wp_kses_post(self::$tip_content);
                    }
                }
            }
            return $content;
        }
    }
}
