<?php

defined('ABSPATH') || exit;

/**
 * 效果：禁止纯英文评论
 * 来源：https://www.npc.ink/18129.html
 */

if (!class_exists('Npcink_Toolbox_Comment_Ban_Pure_English')) {
    class Npcink_Toolbox_Comment_Ban_Pure_English implements Npcink_Toolbox_Module_Interface
    {
        public static function run($config = array())
        {
            add_filter('pre_comment_approved', array(__CLASS__, 'refused_english_comments'), 10, 2);
        }

        public static function refused_english_comments($approved, $commentdata)
        {
            $pattern = '/[一-龥]/u';
            if (!preg_match($pattern, $commentdata['comment_content'])) {
                return new \WP_Error('comment_chinese_required', __('您的评论中必须包含汉字！', 'npcink-site-toolbox'));
            }
            return $approved;
        }
    }
}
