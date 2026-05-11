<?php

/**
 * 效果：移除文章中的站外超链接，保留站内链接
 */

if (!class_exists('MaBox_Single_Remove_Link')) {
    class MaBox_Single_Remove_Link
    {
        public static function run()
        {
            add_filter('the_content', array(__CLASS__, 'replace_text_wps'));
        }
        
        public static function replace_text_wps($text)
        {
            return preg_replace_callback('/<a[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/is', function ($matches) {
                $url = $matches[1];
                $home = home_url();
                // 保留站内链接，移除站外链接
                if (strpos($url, $home) === 0 || strpos($url, '/') === 0) {
                    return $matches[0];
                }
                return $matches[2];
            }, $text);
        }
    }
}
