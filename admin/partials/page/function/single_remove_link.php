<?php

/**
 * 效果：移除文章中的超链接，可恢复
 * TODO:仅移除站外链接，暴露站内的
 */

if (!class_exists('Npcink_Single_Remove_Link')) {
    class Npcink_Single_Remove_Link
    {
        public static function run()
        {
            add_filter('the_content', array(__CLASS__, 'replace_text_wps'));
        }
        
        public static function replace_text_wps($text)
        {
            $text = preg_replace("/<a[^>]*>(.*?)<\/a>/is", "$1", $text);
            return $text;
        }
    }
}
