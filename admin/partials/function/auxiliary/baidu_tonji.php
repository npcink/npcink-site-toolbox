<?php

if (!class_exists('MaBox_Baidu_Tonji')) {
    class MaBox_Baidu_Tonji
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_action('wp_footer', array(__CLASS__, 'render'), 999);
        }

        public static function render()
        {
            if (!empty(self::$option)) {
                $option = esc_js(self::$option);
                echo '<script>var _hmt=_hmt||[];(function(){var hm=document.createElement("script");hm.src="https://hm.baidu.com/hm.js?' . $option . '";var s=document.getElementsByTagName("script")[0];s.parentNode.insertBefore(hm,s)})()</script>' . "\n";
            }
        }
    }
}
