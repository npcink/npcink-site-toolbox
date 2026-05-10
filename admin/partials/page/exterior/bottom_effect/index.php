<?php

/**
 * 效果：页面底部效果
 */
if (!class_exists('MaBox_Page_Bottom_Effect')) {
    class MaBox_Page_Bottom_Effect
    {

        public static function run($config)
        {
            //TODO:为啥没效果，移动端不展示
            //鱼群
            if ($config === "fish") {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'add_page_fish'));
                add_action('wp_footer', array(__CLASS__, 'fish'), 100);
            }
        }


        //鱼群跳动
        public static function fish()
        {

?>
            <div id="j-fish-skip"></div>
<?php

        }

        //加载鱼群跳动资源
        public static function add_page_fish()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_fish_skip',
                plugin_dir_url(__FILE__) . 'fish/fish.min.js',
                array("jquery"),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
