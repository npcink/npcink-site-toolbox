<?php

/**
 * 效果：禁止纯英文评论
 * 来源：https://www.npc.ink/18129.html
 */

if (!class_exists('MaBox_Comment_Ban_Pure_English')) {
    class MaBox_Comment_Ban_Pure_English
    {
        public static function run()
        {
            add_filter('pre_comment_approved', array(__CLASS__, 'refused_english_comments'), 10, 2);
        }

        public static function refused_english_comments($approved, $commentdata)
        {
            $pattern = '/[一-龥]/u';
            if (!preg_match($pattern, $commentdata['comment_content'])) {
                return new \WP_Error('comment_chinese_required', '您的评论中必须包含汉字!');
            }
            return $approved;
        }
    }
}
