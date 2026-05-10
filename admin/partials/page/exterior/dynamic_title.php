<?php

if (!class_exists('MaBox_Page_Dynamic_Title')) {
    class MaBox_Page_Dynamic_Title
    {
        public static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_action('wp_footer', array(__CLASS__, 'render'), 999);
        }

        public static function render()
        {
            $title_front = esc_js(MaBox_Admin::get_config(self::$option, 'title_front', "(/≧▽≦/)你又回来啦！"));
            $title_after = esc_js(MaBox_Admin::get_config(self::$option, 'title_after', "你别走吖 Σ(っ °Д °;)っ"));
            echo '<script>(function(){var o=document.title;document.addEventListener("visibilitychange",function(){document.hidden?(document.title="' . $title_after . '",clearTimeout(window._tt)):(document.title="' . $title_front . '",window._tt=setTimeout(function(){document.title=o},2000))})})();</script>' . "\n";
        }
    }
}
