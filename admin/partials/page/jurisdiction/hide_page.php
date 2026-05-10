<?php

/**
 * 未登录隐藏指定页面
 */

if (!class_exists('MaBox_Page_Hide_Page')) {
    class MaBox_Page_Hide_Page
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
                if (!MaBox_Helpers::is_logged_in()) {
                    $content = self::$tip_content;
                    self::enqueue_assets();

                }
            }
            return $content;
        }
        public static function enqueue_assets()
        {
            wp_enqueue_script(MAGICK_MIXTURE_NAME . '_hide_page', '', array(), MAGICK_MIXTURE_VERSION, true);
            $tip_content = wp_kses_post(self::$tip_content);
            $js = "const entryContent = document.querySelector('.entry-content'); if (entryContent) { entryContent.innerHTML = '" . $tip_content . "'; }";
            wp_add_inline_script(MAGICK_MIXTURE_NAME . '_hide_page', $js);
        }
    }
}
