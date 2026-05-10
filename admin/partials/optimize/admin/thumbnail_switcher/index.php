<?php

/**
 * 效果：文章列表添加缩略图展示、添加和删除
 * 来源1：https://wordpress.org/plugins/easy-thumbnail-switcher/
 * 来源2：https://www.huitheme.com/wordpress_posts_custom_thumbnail.html
 */
if (!class_exists('MaBox_Admin_Single_Thumbnail_Switcher')) {
    class MaBox_Admin_Single_Thumbnail_Switcher
    {
        //加载
        public static function run()
        {
            // 加载 test.php 文件
            require_once 'easy-thumbnail-switcher.php';
        }
    }
}
