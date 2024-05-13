<?php

/**
 * 效果：点击特效
 * 来源：https://www.iowen.cn/canvas-click-effect-second-edition/
 */
if (!class_exists('Npcink_Page_Add_Click_Effect')) {
    class Npcink_Page_Add_Click_Effect
    {
        public static function run($config)
        {
            //爆炸烟花
            if ($config === "diffuse") {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'add_page_particle_js'));
                add_action('wp_footer', array(__CLASS__, 'add_page_particle'));
            }
            //文字
            if ($config === "text") {
                add_action('wp_footer', array(__CLASS__, 'text'));
            }
        }

        //添加四散粒子文件
        public static function add_page_particle()
        {
            echo '<div id="clickCanvas"  style=" position:fixed;left:0;top:0;z-index:999999999;pointer-events:none;"></div>';
        }
        //加载四散js
        public static function add_page_particle_js()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_particle',
                plugin_dir_url(__FILE__) . 'js/style-click-particle.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 文字
         */
        public static function text()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_text',
                plugin_dir_url(__FILE__) . 'js/click-text-style.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
