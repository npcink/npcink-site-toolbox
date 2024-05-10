<?php

/**
 * 效果：页面添加烟花粒子
 * 来源：https://www.iowen.cn/canvas-click-effect-second-edition/
 */

if (!class_exists('Npcink_Page_Add_Particle')) {
    class Npcink_Page_Add_Particle
    {
        public static function run()
        {
            //手机端不加载
            if (!wp_is_mobile()) {
                //四散
                add_action('wp_enqueue_scripts', array(__CLASS__, 'add_page_particle_js'));
                add_action('wp_footer', array(__CLASS__, 'add_page_particle'));
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
    }
}
