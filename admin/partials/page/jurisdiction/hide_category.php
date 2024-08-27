<?php

/**
 * 未登录隐藏指定分类下的文章
 */

if (!class_exists('Npcink_Page_Hide_Category')) {
    class Npcink_Page_Hide_Category
    {
        private static $id_array; //分类数组
        private static $tip_content; //提示信息
        public static function run($array, $id_tip_content)
        {
            self::$id_array = $array;
            self::$tip_content = $id_tip_content;
            add_action('the_content', array(__CLASS__, 'restrict_content_for_specific_categories'));
        }

        public static function restrict_content_for_specific_categories($content)
        {
            // 定义受限的分类ID数组
            $restricted_category_ids = self::$id_array; // 将这里替换为你想要限制的分类ID数组

            // 检查文章是否属于受限的分类
            if (in_category($restricted_category_ids)) {
                if (!is_user_logged_in()) {
                    // 如果用户未登录，则将文章内容替换为登录提示
                    // 转义

                    $content = self::$tip_content;
                }
            }
            return $content;
        }
    }
}
