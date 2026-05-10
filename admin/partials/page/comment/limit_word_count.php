<?php

/**
 * 效果：评论所需的最少和最多字数
 * 来源：https://www.npc.ink/17995.html
 */

if (!class_exists('MaBox_Comment_Limit_Word_Count')) {
    class MaBox_Comment_Limit_Word_Count
    {
        public static $option; //配置
        public static function run($config)
        {
            self::$option = $config;
            add_filter('pre_comment_approved', array(__CLASS__, 'set_comments_length'), 10, 2);
        }

        public static function set_comments_length($approved, $commentdata)
        {
            $minCommentlength =  MaBox_Admin::get_config(self::$option, 'words_number_min'); //最少字數限制
            $maxCommentlength = MaBox_Admin::get_config(self::$option, 'words_number_max'); //最多字數限制
            $pointCommentlength = mb_strlen($commentdata['comment_content'], 'UTF8'); //mb_strlen 1個中文字符當作1個長度
            if ($pointCommentlength < $minCommentlength) {
                return new \WP_Error('comment_too_short', '抱歉，您的评论字数过少，请至少输入' . $minCommentlength . '个字（目前字数：' . $pointCommentlength . '个字）');
            }
            if ($pointCommentlength > $maxCommentlength) {
                return new \WP_Error('comment_too_long', '对不起，您的评论字数过多，请少于' . $maxCommentlength . '个字（目前字数：' . $pointCommentlength . '个字）');
            }
            return $approved;
        }
    }
}
