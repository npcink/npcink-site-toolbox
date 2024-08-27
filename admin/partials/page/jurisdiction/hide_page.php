<?php

/**
 * 未登录隐藏指定页面
 */

if (!class_exists('Npcink_Page_Hide_Page')) {
    class Npcink_Page_Hide_Page
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
            $page_ids = self::$id_array; // 将这里替换为你想要限制的分类ID数组

            //当前是页面类型，且当前页面ID在指定数组中
            if (is_page() && in_array(get_the_ID(), $page_ids)) {
                // 如果用户未登录，则将文章内容替换为登录提示
                if (!is_user_logged_in()) {
                    $content = self::$tip_content;
                }
            }
            return $content;
        }
    }
}
