<?php

/**
 * 效果：屏幕上添加一根毛
 * 来源：https://mkblog.cn/2382/
 */

if (!class_exists('MaBox_Page_Screen_Hair')) {
    class MaBox_Page_Screen_Hair
    {
        public static function run()
        {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'screen_hair'));
        }
        /**
         * 屏幕上有根毛
         */
        public static function screen_hair()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_hair',
                plugin_dir_url(__FILE__) . 'hair.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
            // 获取上一层的 image 文件夹路径
            $image_folder_path =  plugin_dir_url(__FILE__) . '';

            //拼接完整图片地址
            $image_url = $image_folder_path. "hair.png";

            //传递路径给jS
            wp_localize_script(
                MAGICK_MIXTURE_NAME . '_hair',
                'image_url',
                $image_url,
            );
        }
    }
}
