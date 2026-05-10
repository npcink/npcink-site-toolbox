<?php

/**
 * 作用：修改评论区样式中的管理员信息
 * 来源：https://rudrastyh.com/wordpress/11-security-steps.html
 * 检查：<li id="comment-2" class="comment byuser comment-author-1 bypostauthor even thread-even depth-1">
 */

if (!class_exists('MaBox_Comment_Modify_User_Style')) {
    class MaBox_Comment_Modify_User_Style
    {
        //加载
        public static function run()
        {
            add_filter('comment_class', array(__CLASS__, 'true_completely_remove_css_class'));
        }

        public static function true_completely_remove_css_class($classes)
        {
            foreach ($classes as $key => $class) {
                if (strstr($class, "comment-author-")) {
                    unset($classes[$key]);
                }
            }
            return $classes;
        }
    }
}
