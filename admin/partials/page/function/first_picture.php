<?php

/**
 * 效果：未设置特色图时，自动将第一张图设为特色图
 * 来源：https://www.huitheme.com/wordpress-auto-featured-image.html
 */

if (!class_exists('Npcink_Single_First_Picture')) {
    class Npcink_Single_First_Picture
    {
        public static function run()
        {
            add_action('the_post', array(__CLASS__, 'huitheme_auto_set_featured_image'));
        }
        //自动添加特色图像
        public static function huitheme_auto_set_featured_image()
        {
            global $post;
            $featured_image_exists = has_post_thumbnail($post->ID);
            if (!$featured_image_exists) {
                $attached_image = get_children("post_parent=$post->ID&post_type=attachment&post_mime_type=image&numberposts=1");
                if ($attached_image) {
                    foreach ($attached_image as $attachment_id => $attachment) {
                        set_post_thumbnail($post->ID, $attachment_id);
                    }
                }
            }
        }
    }
}
