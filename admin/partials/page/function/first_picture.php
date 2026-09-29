<?php

defined('ABSPATH') || exit;

/**
 * 效果：未设置特色图时，自动将第一张图设为特色图
 * 来源：https://www.huitheme.com/wordpress-auto-featured-image.html
 */

if (!class_exists('Npcink_Toolbox_Single_First_Picture')) {
    class Npcink_Toolbox_Single_First_Picture implements Npcink_Toolbox_Module_Interface
    {
        public static function run($config = array())
        {
            // 在保存文章时一次性设置特色图，渲染路径（含归档、Feed）不再写数据库
            add_action('save_post', array(__CLASS__, 'set_featured_image_on_save'), 10, 2);
        }
        //保存时自动添加特色图像
        public static function set_featured_image_on_save($post_id, $post)
        {
            if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
                return;
            }
            if (!post_type_supports(get_post_type($post), 'thumbnail')) {
                return;
            }
            if (has_post_thumbnail($post_id)) {
                return;
            }
            $attached_image = get_children("post_parent=$post_id&post_type=attachment&post_mime_type=image&numberposts=1");
            if ($attached_image) {
                foreach ($attached_image as $attachment_id => $attachment) {
                    set_post_thumbnail($post_id, $attachment_id);
                }
            }
        }
    }
}
