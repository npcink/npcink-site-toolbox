<?php

if (!class_exists('MaBox_Page_Ban_Open_QQ')) {
    class MaBox_Page_Ban_Open_QQ
    {
        public static function run()
        {
            require_once('WxqqJump/WxqqJump.php');
            add_action('wp_footer', array(__CLASS__, 'render'), 999);
        }

        public static function render()
        {
            echo '<script>function is_weixn_qq(){var ua=navigator.userAgent.toLowerCase();if(ua.match(/QQ/i)=="qq"){alert("QQ中打开")}}is_weixn_qq()</script>' . "\n";
        }
    }
}
