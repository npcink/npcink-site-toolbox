<?php

/**
 * 效果：像素小鸡
 * 来源：https://www.bber.cn
 */
if (!class_exists('MaBox_Page_Pixel_Chicken')) {
    class MaBox_Page_Pixel_Chicken
    {
        public static function run()
        {
            //移动端不展示
            if (!MaBox_Helpers::is_mobile()) {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'load_css'));
                add_action('wp_footer', array(__CLASS__, 'load'));
            }
        }
        /**
         * 添加小鸡css
         */
        public static function load_css()
        {
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_pixel_chicken',
                plugin_dir_url(__FILE__) . 'chicken.css',
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );
        }
        /**
         * 添加灯笼节点
         */
        public static function load()
        {
?>

            <div id="sceneji" class="sceneji">
                <div class="flowerji"></div>
                <div class="linkji"></div>
            </div>
<?php
        }
    }
}
