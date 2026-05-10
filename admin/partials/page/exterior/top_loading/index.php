<?php

/**
 * 效果：顶部加载进度条
 * 来源：https://www.bber.cn/92.html
 */
if (!class_exists('MaBox_Page_Top_Loading')) {
    class MaBox_Page_Top_Loading
    {
        public static function run()
        {
            //加载前端资源
            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_js'));

            //添加节点
            add_action('wp_footer', array(__CLASS__, 'add_code'));
        }
        public static function add_code()
        {
            echo '';
        }

        public static function load_js()
        {
            //判断下，是否在前端页中
            if (is_admin()) {
                return;
            }

            //准备数据
            $build_js =  plugin_dir_url(__DIR__) . 'top_loading/banner-top.js';
            $build_css =  plugin_dir_url(__DIR__) . 'top_loading/banner-top.css';

           wp_enqueue_script(
               MAGICK_MIXTURE_NAME . '_public_top_loading_js',
               $build_js,
               array(),
               MAGICK_MIXTURE_VERSION,
               true
           );

            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_public_top_loading_css',
                $build_css,
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );
        }
    }
}
