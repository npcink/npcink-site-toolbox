<?php

if (!class_exists('MaBox_Page_Ban_Copy')) {
    class MaBox_Page_Ban_Copy
    {
        public static function run()
        {
            add_action('wp_footer', array(__CLASS__, 'render'), 999);
        }

        public static function render()
        {
            echo '<script>document.onselectstart=function(){return false}</script>' . "\n";
        }
    }
}
