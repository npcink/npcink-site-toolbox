<?php

/**
 * 效果：流动线条
 * 来源：http://www.cs.cmu.edu/~hanfeis/
 */

if (!class_exists('MaBox_Page_Flowing_Lines')) {
    class MaBox_Page_Flowing_Lines
    {
        public static function run()
        {
            //加载jS
            add_action('wp_enqueue_scripts', array(__CLASS__, 'add_js'));
            //加载节点
            add_action('wp_footer', array(__CLASS__, 'add_node'));
        }
        public static function add_node()
        {
            echo '<canvas width="2880" height="1530"></canvas>
            
            <style>
                canvas {
                        position: absolute;
                        /*position: fixed;//全屏
                         z-index: -1;
                        */
                        top: 0;
                        left: 0;
                        z-index: 0;
                        width: 100%;
                        height: 100%;
                        pointer-events: none;
                         }
            </style>
            ';
        }
        /**
         * 添加js
         */
        public static function add_js()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_flowing_lines',
                plugin_dir_url(__FILE__) . 'flowing_lines.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
