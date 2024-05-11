<?php
//优化 评论
if (!class_exists('MaMi_Optimize_Comment')) {
    class MaMi_Optimize_Comment
    {
        //选项值
        private static $option;
        //加载
        public static function run($config)
        {
            //获取选项
            $option =  MaMi_Admin::get_config($config, 'comment');

            //类内赋值
            self::$option = $option;

           

          

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

       

       
        /* 作用：禁止纯英文评论
         * 来源：https://www.npc.ink/18129.html
         * */
        public static function refused_english_comments($incoming_comment)
        {
            $pattern = '/[一-龥]/u';
            if (!preg_match($pattern, $incoming_comment['comment_content'])) {
                $message = '您的评论中必须包含汉字!';
                $message = $message . MaMi_Admin::blank_button();
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
                    $message = $message . MaMi_Admin::blank_button();
                    wp_die($message);
                }
            }

            return $commentdata;
        }
    } //end
}
