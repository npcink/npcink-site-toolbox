<?php

defined('ABSPATH') || exit;

/**
 * 效果：评论所需的最少和最多字数
 * 来源：https://www.npc.ink/17995.html
 */

if (!class_exists('Npcink_Toolbox_Comment_Limit_Word_Count')) {
    class Npcink_Toolbox_Comment_Limit_Word_Count implements Npcink_Toolbox_Module_Interface
    {
        public static $option; //配置
        public static function run($config = array())
        {
            self::$option = $config;
            add_filter('pre_comment_approved', array(__CLASS__, 'set_comments_length'), 10, 2);
        }

        public static function set_comments_length($approved, $commentdata)
        {
            $minCommentlength =  Npcink_Toolbox_Admin::get_config(self::$option, 'words_number_min'); //最少字數限制
            $maxCommentlength = Npcink_Toolbox_Admin::get_config(self::$option, 'words_number_max'); //最多字數限制
            $pointCommentlength = mb_strlen($commentdata['comment_content'], 'UTF8'); //mb_strlen 1個中文字符當作1個長度
            if ($pointCommentlength < $minCommentlength) {
                return new \WP_Error(
                    'comment_too_short',
                    sprintf(
                        /* translators: 1: Minimum comment length. 2: Current comment length. */
                        __('抱歉，您的评论字数过少，请至少输入 %1$d 个字（目前字数：%2$d 个字）。', 'npcink-site-toolbox'),
                        $minCommentlength,
                        $pointCommentlength
                    )
                );
            }
            if ($pointCommentlength > $maxCommentlength) {
                return new \WP_Error(
                    'comment_too_long',
                    sprintf(
                        /* translators: 1: Maximum comment length. 2: Current comment length. */
                        __('对不起，您的评论字数过多，请少于 %1$d 个字（目前字数：%2$d 个字）。', 'npcink-site-toolbox'),
                        $maxCommentlength,
                        $pointCommentlength
                    )
                );
            }
            return $approved;
        }
    }
}
