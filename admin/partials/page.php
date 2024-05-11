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
            $config = MaMi_Admin::get_seting('page');

            /**
             * 页面 - 外观特效
             */
            require_once plugin_dir_path(__FILE__) . 'page/exterior/index.php';
            $aspect =  MaMi_Admin::get_config($config, 'feature');
            Npcink_Page_Exterior::run($aspect);

            /**
             * 页面 - 评论
             */
            require_once plugin_dir_path(__FILE__) . 'page/comment/index.php';
            $page =  MaMi_Admin::get_config($config, 'comment');
            Npcink_Page_Comment::run($page);
        }
    }
}
