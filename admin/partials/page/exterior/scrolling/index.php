<?php

/**
 * 页面平滑滚动 page_scrolling
 * 来源：https://7b2.com/circle/64661.html
 */
if (!class_exists('MaBox_Page_Scrolling')) {
    class MaBox_Page_Scrolling
    {
        public static function run()
        {
            //加载jS
            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_js'));
        }
        public static function load_js()
        {
            //加载jS
            //判断下，是否在前端页中
            if (is_admin()) {
                return;
            }

            //准备数据
            $build_js =  plugin_dir_url(__DIR__) . 'scrolling/sihua.js';
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_public_scrolling_js',
                $build_js,
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
