<?php

/**
 * 效果：谷歌统计
 * 来源：
 */
if (!class_exists('MaBox_Google_Tonji')) {
    class MaBox_Google_Tonji
    {

        private static $option;
        public static function run($config)
        {
            self::$option = $config;
            add_action('wp_head', array(__CLASS__, 'meta_tag'));
        }
        public static function meta_tag()
        {
            if (!empty(self::$option)) {
                $option = esc_attr(self::$option);
                echo '<meta name="google-site-verification" content="' . $option . '" />' . "\n";
            }
        }
    }
}
