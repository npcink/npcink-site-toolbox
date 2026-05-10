<?php

/**
 * 效果：随机彩带
 * 来源：https://github.com/hustcc/ribbon.js
 */

if (!class_exists('MaBox_Page_Random_Ribbon')) {
    class MaBox_Page_Random_Ribbon
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
                MAGICK_MIXTURE_NAME . '_random_ribbon',
                plugin_dir_url(__FILE__) . 'ribbon.min.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
