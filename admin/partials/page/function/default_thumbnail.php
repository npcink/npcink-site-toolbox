<?php
/**
 * 默认文章缩略图
 * 当文章没有特色图时，使用默认缩略图
 */
if (!class_exists('MaBox_Page_Default_Thumbnail')) {
    class MaBox_Page_Default_Thumbnail
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_filter('post_thumbnail_html', array(__CLASS__, 'default_thumbnail'), 10, 5);
        }

        public static function default_thumbnail($html, $post_id, $post_thumbnail_id, $size, $attr)
        {
            $default_url = MaBox_Admin::get_config(self::$option, 'default_thumbnail');
            if (empty($default_url)) {
                return $html;
            }
            if (empty($html)) {
                $html = '<img src="' . esc_url($default_url) . '" alt="' . esc_attr(get_the_title($post_id)) . '" />';
            }
            return $html;
        }
    }
}
