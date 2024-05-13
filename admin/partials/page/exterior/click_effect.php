<?php

/**
 * 效果：也面点击特效
 * 来源：https://blog.csdn.net/weixin_42077074/article/details/121031327
 */

if (!class_exists('Npcink_Page_Add_Particle')) {
    class Npcink_Page_Add_Particle
    {
        public static function run()
        {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'coupling'));
        }
        /**
         * 细线联结
         */
        public static function coupling()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_canvas-nest',
                plugin_dir_url(__FILE__) . 'js/canvas-nest.min.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
