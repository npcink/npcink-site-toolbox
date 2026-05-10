<?php

/**
 * 效果：必应统计
 * 来源：
 */
if (!class_exists('MaBox_Biying_Tonji')) {
    class MaBox_Biying_Tonji
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
                echo '<meta name="msvalidate.01" content="' . $option . '" />' . "\n";
            }
        }
    }
}
