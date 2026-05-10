<?php

/**
 * 效果：隐藏顶部工具条，仅管理员和编辑可见
 * 来源：https://www.huitheme.com/show_admin_bar.html
 */
if (!class_exists('MaBox_Hide_Top_Toolbar')) {
    class MaBox_Hide_Top_Toolbar
    {
        /**
         * 执行代码
         */
        public static  function run()
        {

            // 
            add_action('init', array(__CLASS__, 'disable_plugin_update_notification'));
        }


        public static  function disable_plugin_update_notification()
        {
            if (!current_user_can('edit_posts')) {
                add_filter('show_admin_bar', '__return_false');
            }
            /**
             * 仅管理员可见
             * if (!current_user_can('manage_options')) {
             * 	    add_filter('show_admin_bar', '__return_false');
             * 	}
             * 
             * 完全去除
             * add_filter('show_admin_bar', '__return_false');
             * 
             * show_admin_bar(false);
             */
        }
    }
}
