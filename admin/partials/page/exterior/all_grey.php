<?php

if (!class_exists('MaBox_Page_All_Grey')) {
    class MaBox_Page_All_Grey
    {
        public static function run()
        {
            add_action('wp_head', array(__CLASS__, 'render'), 999);
        }

        public static function render()
        {
            // 将 filter 应用到 body 而非 html，避免影响 fixed/absolute 定位的弹窗
            // 排除 .OwO 表情选择器，保持表情色彩正常
            echo '<style>body{-webkit-filter:grayscale(0.95);-moz-filter:grayscale(0.95);-ms-filter:grayscale(0.95);-o-filter:grayscale(0.95);filter:grayscale(0.95)}.OwO,.OwO *{-webkit-filter:grayscale(0)!important;-moz-filter:grayscale(0)!important;-ms-filter:grayscale(0)!important;-o-filter:grayscale(0)!important;filter:grayscale(0)!important}</style>' . "\n";
        }
    }
}
