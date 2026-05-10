<?php

/**
 * 效果：滴墨水
 * 来源：https://blog.csdn.net/weixin_45511682/article/details/122825805
 */

if (!class_exists('MaBox_Page_Drip_Ink')) {
    class MaBox_Page_Drip_Ink
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
            echo '<canvas id="c" class="bgcover" width="1536"></canvas>
            
            <style>
                .bgcover {
                    display: block;
                    position: fixed;
                    margin: 0px;
                    padding: 0px;
                    border: 0px;
                    outline: 0px;
                    left: 0px;
                    top: 0px;
                    width: 100%;
                    height: 100%;
                    z-index: -1;
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
                MAGICK_MIXTURE_NAME . '_drip_ink',
                plugin_dir_url(__FILE__) . 'drip_ink.js',
                array("jquery"),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
