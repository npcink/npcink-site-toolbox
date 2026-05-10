<?php

/**
 * 效果：圆角彩色背景标签云
 * 来源：
 */

if (!class_exists('MaBox_Page_Color_Tags')) {
    class MaBox_Page_Color_Tags
    {
        public static function run()
        {
            add_filter('wp_tag_cloud', array(__CLASS__, 'colorCloud'), 1);
        }

        /**
         * 添加彩色标签云
         */
        public static function colorCloud($text)
        {
            $text = preg_replace_callback('|<a (.+?)>|i', array(__CLASS__, 'colorCloudCallback'), $text);
            return $text;
        }
        public static function colorCloudCallback($matches)
        {
            $text = $matches[1];
            $colors = array('F99', 'C9C', 'F96', '6CC', '6C9', '37A7FF', 'B0D686', 'E6CC6E');
            $color = $colors[dechex(rand(0, 7))];
            $pattern = '/style=(\'|\")(.*)(\'|\")/i';
            $text = preg_replace($pattern, "style=\"display: inline-block; *display: inline; *zoom: 1; color: #fff; padding: 1px 5px; margin: 0 5px 5px 0; background-color: #{$color}; border-radius: 3px; -webkit-transition: background-color .4s linear; -moz-transition: background-color .4s linear; transition: background-color .4s linear;\"", $text);
            $pattern = '/style=(\'|\")(.*)(\'|\")/i';
            return "<a $text>";
        }
    }
}
