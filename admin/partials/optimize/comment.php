<?php
//优化 评论
if (!class_exists('MaMi_Optimize_Comment')) {
    class MaMi_Optimize_Comment
    {
        //加载
        public static function run($config)
        {
            //获取选项
            $option =  MaMi_Admin::get_config($config, 'comment');

            //评论时间间隔
            $interval = MaMi_Admin::get_config($option, 'interval');
            //间隔时间
            $interval_time = MaMi_Admin::get_config($option, 'interval_time');
            if ($interval) {
                //add_filter('comment_flood_filter', array(__CLASS__, 'suren_comment_flood_filter'), 10, 3);
                add_filter('comment_flood_filter', function ($flood_control, $time_last, $time_new) use ($interval_time) {
                    return self::suren_comment_flood_filter($flood_control, $time_last, $time_new, $interval_time);
                }, 10, 3);
            }

            //评论最少和最多字数
            $words_number = MaMi_Admin::get_config($option, 'words_number');
            $words_number_min = MaMi_Admin::get_config($option, 'words_number_min');
            $words_number_max = MaMi_Admin::get_config($option, 'words_number_max');
            if ($words_number) {
                // add_filter('preprocess_comment', array(__CLASS__, 'set_comments_length'), 10, 3);
                add_filter('preprocess_comment', function ($commentdata) use ($words_number_min, $words_number_max) {
                    return self::set_comments_length($commentdata, $words_number_min, $words_number_max);
                }, 10, 1);
            }

            //禁止纯英文评论
            $english = MaMi_Admin::get_config($option, 'english');
            if ($english) {
                add_filter('preprocess_comment', array(__CLASS__, 'refused_english_comments'));
            }

            //TODO:想办法先检查评论一次，再检查纯英文
            //一篇文章只能评论一次
            $only = MaMi_Admin::get_config($option, 'only');
            if ($only) {
                add_filter('preprocess_comment', array(__CLASS__, 'ludou_only_one_comment'));
            }
        }

        /**
         * 优化-评论
         */

        /**
         * 效果：两次评论之间间隔
         * 来源：https://www.npc.ink/19960.html
         */
        public static function suren_comment_flood_filter($flood_control, $time_last, $time_new, $interval_time)
        {
            $seconds = $interval_time; //间隔时间

            if (($time_new - $time_last) < $seconds) {
                $time = $seconds - ($time_new - $time_last);
                $message = '评论过快！请' . $time . '秒后再来评论';
                $message .= '<br/><a href="#" onclick="history.back();">
                <button class="button" style="margin: 1em 0;">返回</button>
                </a>';
                wp_die($message);
            } else {
                return false;
            }
        }

        /**
         * 效果：评论所需的最少和最多字数
         * 来源：https://www.npc.ink/17995.html
         */
        public static function set_comments_length($commentdata, $words_number_min, $words_number_max)
        {
            $minCommentlength = $words_number_min; //最少字數限制
            $maxCommentlength = $words_number_max; //最多字數限制
            $pointCommentlength = mb_strlen($commentdata['comment_content'], 'UTF8'); //mb_strlen 1個中文字符當作1個長度
            if ($pointCommentlength < $minCommentlength) {
                header("Content-type: text/html; charset=utf-8");
                $message = '抱歉，您的评论字数过少，请至少输入' . $minCommentlength . '个字（目前字数：' . $pointCommentlength . '个字）';
                $message .= '<br/><a href="#" onclick="history.back();">
                <button class="button" style="margin: 1em 0;">返回</button>
                </a>';
                wp_die($message);


                exit;
            }
            if ($pointCommentlength > $maxCommentlength) {
                header("Content-type: text/html; charset=utf-8");
                $message = '对不起，您的评论字数过多，请少于' . $maxCommentlength . '个字（目前字数：' . $pointCommentlength . '个字）';
                $message .= '<br/><a href="#" onclick="history.back();">
                <button class="button" style="margin: 1em 0;">返回</button>
                </a>';
                wp_die($message);

                exit;
            }
            return $commentdata;
        }

        /* 作用：禁止纯英文评论
         * 来源：https://www.npc.ink/18129.html
         * */
        public static function refused_english_comments($incoming_comment)
        {
            $pattern = '/[\p{Script=Han}]/u';
            if (!preg_match($pattern, $incoming_comment['comment_content'])) {
                $message = '您的评论中必须包含汉字!';
                $message .= '<br/><a href="#" onclick="history.back();">
                <button class="button" style="margin: 1em 0;">返回</button>
                </a>';
                wp_die($message);
            }
            return $incoming_comment;
        }



        /* 作用：一篇文章只能评论一次，管理员不受影响
         * 来源：https://www.npc.ink/13477.html
         * */
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
                    $message .= '<br/><a href="#" onclick="history.back();">
                    <button class="button" style="margin: 1em 0;">返回</button>
                    </a>';
                    wp_die($message);
                    //wp_die('<br/><a href="' . get_permalink($commentdata['comment_post_ID']) . '">返回</a>');
                }
            }

            return $commentdata;
        }
    } //end
}
