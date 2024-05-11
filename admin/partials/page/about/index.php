<?php

/**
 * 其他
 */

if (!class_exists('Npcink_Page_About')) {
    class Npcink_Page_About
    {
        public static function run($option)
        {
            //圆角彩色背景标签云
            $color_tag = MaMi_Admin::get_config($option, 'color_tag');
            if ($color_tag === true) {
                require_once plugin_dir_path(__FILE__) . 'color_tags.php';
                Npcink_Page_Color_Tags::run();
            }

            //评论区添加表情
            $comment_emote = MaMi_Admin::get_config($option, 'comment_emote');
            if ($comment_emote === true) {
                require_once plugin_dir_path(__FILE__) . 'comment_emoji.php';
                Npcink_Page_Comment_Emoji::run();
            }
        }
    }
}
