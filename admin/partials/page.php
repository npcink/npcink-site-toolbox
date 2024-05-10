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
            $config = MaMi_Admin::get_seting('style');

            /**
             * 页面 - 外观特效
             */
            require_once plugin_dir_path(__FILE__) . 'page/exterior/index.php';
            //禁用
            $aspect =  MaMi_Admin::get_config($config, 'aspect');
            Npcink_Page_Exterior::run($aspect);
        }
    }
}
