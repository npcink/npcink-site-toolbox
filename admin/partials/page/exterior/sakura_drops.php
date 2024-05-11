<?php

/**
 * 效果：樱花飘落
 * 来源：https://www.cnblogs.com/quaint/p/12291936.html
 */
if (!class_exists('Npcink_Page_Sakura_Drops')) {
    class Npcink_Page_Sakura_Drops
    {
        public static function run()
        {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'sakura'));
        }
        /**
         * 添加樱花
         */
        public static function sakura()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_sakura',
                plugin_dir_url(__FILE__) . 'js/sakuraPlus.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
