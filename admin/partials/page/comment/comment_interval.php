<?php

defined('ABSPATH') || exit;

/**
 * 效果：两次评论间隔
 * 来源：https://www.npc.ink/19960.html
 */

if (!class_exists('Npcink_Toolbox_Page_Comment_Interval')) {
    class Npcink_Toolbox_Page_Comment_Interval implements Npcink_Toolbox_Module_Interface
    {
        public static $option; //配置
        public static function run($config = array())
        {
            self::$option = $config;
            add_filter('comment_flood_filter', array(__CLASS__, 'suren_comment_flood_filter'), 10, 3);
        }

        public static function suren_comment_flood_filter($flood_control, $time_last, $time_new)
        {
            //间隔时间
            $seconds = Npcink_Toolbox_Admin::get_config(self::$option, 'interval_time');
            if (($time_new - $time_last) < $seconds) {
                $time = $seconds - ($time_new - $time_last);
                $message = sprintf(
                    /* translators: %d: Remaining seconds before another comment can be submitted. */
                    __('评论过快！请在 %d 秒后再来评论。', 'npcink-site-toolbox'),
                    $time
                );

                $message = $message . Npcink_Toolbox_Admin::back_button();
                $allowed_html = array(
                    'p' => array(),
                    'a' => array(
                        'href'  => true,
                        'class' => true,
                    ),
                );
                wp_die(wp_kses($message, $allowed_html));
            } else {
                return false;
            }
        }
    }
}
