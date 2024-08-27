<?php

/**
 * 未登录隐藏指定标签下的文章
 */

if (!class_exists('Npcink_Page_Hide_Tag')) {
    class Npcink_Page_Hide_Tag
    {
        private static $id_array; //标签数组
        private static $tip_content; //提示信息
        public static function run($array, $id_tip_content)
        {
            self::$id_array = $array;
            self::$tip_content = $id_tip_content;
            add_action('the_content', array(__CLASS__, 'restrict_content_for_specific_tags')); //隐藏标签下的文章
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
                    if (!is_user_logged_in()) {
                        // 如果用户未登录，则将文章内容替换为登录提示
                        $content = self::$tip_content;
                    }
                }
            }
            return $content;
        }
    }
}
