<?php

if (!class_exists('MaBox_Unlisted_Vague_Img')) {
    class MaBox_Unlisted_Vague_Img
    {
        public static function run()
        {
            add_action('wp_head', array(__CLASS__, 'render'), 999);
        }

        public static function render()
        {
            if (!MaBox_Helpers::is_logged_in()) {
                echo '<style>.entry-content img{-webkit-filter:blur(10px)!important;-moz-filter:blur(10px)!important;-ms-filter:blur(10px)!important;filter:blur(6px)!important}.entry-content img:before{content:"登录可见"}</style>' . "\n";
            }
        }
    }
}
