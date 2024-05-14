<?php

/**
 * 效果：点击特效
 * 来源1：https://www.iowen.cn/canvas-click-effect-second-edition/
 * 来源2：https://blog.csdn.net/m0_58849641/article/details/126126951
 * 来源3：https://www.npc.ink/14512.html
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
                add_action('wp_enqueue_scripts', array(__CLASS__, 'text'));
            }
            //数字
            if ($config === "number") {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'number'));
            }

            //七彩爱心
            if ($config === "love") {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'love'));
            }
            //四散烟花
            if ($config === "scattered_fireworks") {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'scattered_fireworks'));
            }

            //星星拖尾
            if ($config === "star_trail") {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'star_trail'));
                add_action('wp_footer', array(__CLASS__, 'add_page_star_trail'));
            }

            //圆圈烟花
            if ($config === "circle_fireworks") {
                add_action('wp_footer', array(__CLASS__, 'add_page_circle_fireworks'));
                add_action('wp_enqueue_scripts', array(__CLASS__, 'circle_fireworks'));
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
                plugin_dir_url(__FILE__) . 'js/click_style_particle.js',
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
                plugin_dir_url(__FILE__) . 'js/click_style_text.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 数字
         */
        public static function number()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_style_number.js',
                plugin_dir_url(__FILE__) . 'js/click_style_number.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 爱心
         */
        public static function love()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_style_love.js',
                plugin_dir_url(__FILE__) . 'js/click_style_love.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 圆圈烟花
         */
        public static function scattered_fireworks()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_style_scattered_fireworks.js',
                plugin_dir_url(__FILE__) . 'js/click_style_scattered_fireworks.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 星星拖尾
         */
        public static function add_page_star_trail()
        {
            echo '<span class="js-cursor-container"></span>';
        }
        public static function star_trail()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_style_star_trail.js',
                plugin_dir_url(__FILE__) . 'js/click_style_star_trail.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 圆圈烟花
         */
        public static function add_page_circle_fireworks()
        {
            echo '<canvas class="fireworks" style="position:fixed;left:0;top:0;z-index:99999999;pointer-events:none;"></canvas>
            <style>
              </style>
              ';
        }
        public static function circle_fireworks()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_style_circle_fireworks.js',
                plugin_dir_url(__FILE__) . 'js/click_style_circle_fireworks.js',
                array('jquery'),
                'MAGICK_MIXTURE_VERSION',
                true
            );
        }
    }
}
