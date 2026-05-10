<?php

/**
 * 功能：禁止自动生成缩略图
 * 来源：
 */
if (!class_exists('MaBox_Medium_Ban_Auto_Size')) {
    class MaBox_Medium_Ban_Auto_Size
    {
        //加载
        public static function run()
        {
            self::run_ban_auto_size();
        }
        // 禁用自动生成的图片尺寸
        public static function run_ban_auto_size()
        {

            // 禁用自动生成的图片尺寸
            add_action('intermediate_image_sizes_advanced', array(__CLASS__, 'shapeSpace_disable_image_sizes'));
            // 禁用缩放尺寸
            add_filter('big_image_size_threshold', '__return_false');
            // 禁用其他图片尺寸
            add_action('init', array(__CLASS__, 'shapeSpace_disable_other_image_sizes'));
        }

        // 禁用自动生成的图片尺寸
        public static function shapeSpace_disable_image_sizes($sizes)
        {
            unset($sizes['thumbnail']); // disable thumbnail size
            unset($sizes['medium']); // disable medium size
            unset($sizes['large']); // disable large size
            unset($sizes['medium_large']); // disable medium-large size
            unset($sizes['1536x1536']); // disable 2x medium-large size
            unset($sizes['2048x2048']); // disable 2x large size return $sizes;
        }

        // 禁用其他图片尺寸
        public static function shapeSpace_disable_other_image_sizes()
        {
            remove_image_size('post-thumbnail');
            // 禁用通过 set_post_thumbnail_size()  添加的图像
            remove_image_size('another-size');
            // 禁用任何其他添加的图像大小
        }
    }
}
