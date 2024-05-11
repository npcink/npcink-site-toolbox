<?php

/**
 * 效果：一篇文章只能评论一次，管理员不受影响
 * 来源：https://www.npc.ink/13477.html
 */

if (!class_exists('Npcink_Comment_Only_Once')) {
    class Npcink_Comment_Only_Once
    {
        public static function run()
        {
            add_filter('preprocess_comment', array(__CLASS__, 'ludou_only_one_comment'));
        }

        // 获取评论用户的ip，参考wp-includes/comment.php
        public static function ludou_getIP()
        {
            $ip = $_SERVER['REMOTE_ADDR'];
            $ip = preg_replace('/[^0-9a-fA-F:., ]/', '', $ip);

            return $ip;
        }
        public static function ludou_only_one_comment($commentdata)
        {
            global $wpdb;
            $currentUser = wp_get_current_user();

            // 不限制管理员发表评论
            if (empty($currentUser->roles) || !in_array('administrator', $currentUser->roles)) {
                $bool = $wpdb->get_var("SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = " . $commentdata['comment_post_ID'] . "  AND (comment_author = '" . $commentdata['comment_author'] . "' OR comment_author_email = '" . $commentdata['comment_author_email'] . "' OR comment_author_IP = '" . self::ludou_getIP() . "') LIMIT 0, 1;");

                if ($bool) {
                    $message = '本站每篇文章仅允许评论一次。';
                    $message = $message . MaMi_Admin::blank_button();
                    wp_die($message);
                }
            }

            return $commentdata;
        }
    }
}
