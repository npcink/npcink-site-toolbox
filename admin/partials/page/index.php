<?php

/**
 * 页面优化
 */

if (!class_exists('Npcink_Page')) {
    class Npcink_Page
    {
        public static function run()
        {
            //获取设置选项值
            $config = MaBox_Admin::get_seting('page');

            /**
             * 页面 - 外观特效
             */
            require_once plugin_dir_path(__FILE__) . 'exterior/index.php';
            $aspect =  MaBox_Admin::get_config($config, 'feature');
            Npcink_Page_Exterior::run($aspect);

            /**
             * 页面 - 评论
             */
            require_once plugin_dir_path(__FILE__) . 'comment/index.php';
            $comment =  MaBox_Admin::get_config($config, 'comment');
            Npcink_Page_Comment::run($comment);

            /**
             * 页面 - 功能
             */
            require_once plugin_dir_path(__FILE__) . 'function/index.php';
            $function =  MaBox_Admin::get_config($config, 'function');
            Npcink_Page_Function::run($function);

             /**
             * 页面 - 权限
             */
            require_once plugin_dir_path(__FILE__) . 'jurisdiction/index.php';
            $jurisdiction =  MaBox_Admin::get_config($config, 'jurisdiction');
            Npcink_Page_Jurisdiction::run($jurisdiction);
        }
    }
}
