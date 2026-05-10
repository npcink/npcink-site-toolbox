<?php

/**
 * 效果：细线联结
 * 来源：https://blog.csdn.net/weixin_42077074/article/details/121031327
 */

if (!class_exists('MaBox_Page_Add_Convergence_Line')) {
    class MaBox_Page_Add_Convergence_Line
    {
        public static function run()
        {
            //细线连接
            add_action('wp_enqueue_scripts', array(__CLASS__, 'coupling'));
        }
        /**
         * 细线联结
         */
        public static function coupling()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_canvas-nest',
                plugin_dir_url(__FILE__) . 'canvas-nest.min.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
