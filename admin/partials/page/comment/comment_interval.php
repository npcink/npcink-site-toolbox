<?php

/**
 * 效果：两次评论间隔
 * 来源：https://www.npc.ink/19960.html
 */

if (!class_exists('MaBox_Page_Comment_Interval')) {
    class MaBox_Page_Comment_Interval
    {
        public static $option; //配置
        public static function run($config)
        {
            self::$option = $config;
            add_filter('comment_flood_filter', array(__CLASS__, 'suren_comment_flood_filter'), 10, 3);
        }

        public static function suren_comment_flood_filter($flood_control, $time_last, $time_new)
        {
            //间隔时间
            $seconds = MaBox_Admin::get_config(self::$option, 'interval_time');
            if (($time_new - $time_last) < $seconds) {
                $time = $seconds - ($time_new - $time_last);
                $message = '评论过快！请' . $time . '秒后再来评论';

                $message = $message . MaBox_Admin::back_button();
                wp_die($message);
            } else {
                return false;
            }
        }
    }
}
