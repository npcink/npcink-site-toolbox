<?php

/**
 * 效果：流动彩带
 * 来源：https://blog.csdn.net/weixin_45511682/article/details/122825805
 */

if (!class_exists('Npcink_Page_Sliding_Ribbon')) {
    class Npcink_Page_Sliding_Ribbon
    {
        public static function run()
        {
            //加载jS
            add_action('wp_enqueue_scripts', array(__CLASS__, 'add_js'));
        }
        /**
         * 添加js
         */
        public static function add_js()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_sliding_ribbon',
                plugin_dir_url(__FILE__) . 'sliding_ribbon.js',
                array("jquery"),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
