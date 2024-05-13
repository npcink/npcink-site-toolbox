<?php

/**
 * 页面 功能
 */

if (!class_exists('Npcink_Page_Function')) {
    class Npcink_Page_Function
    {
        public static function run($option)
        {
            //文章关键词自动添加内链链接代码
            $add_inks = MaMi_Admin::get_config($option, 'add_inks');
            if ($add_inks === true) {
                require_once plugin_dir_path(__FILE__) . 'single_keyword_add_link.php';
                Npcink_Single_Keyword_Add_Link::run();
            }
            //去除文章内的超链接，可复原
            $remove_single_link = MaMi_Admin::get_config($option, 'remove_single_link');
            if ($remove_single_link === true) {
                require_once plugin_dir_path(__FILE__) . 'single_remove_link.php';
                Npcink_Single_Remove_Link::run();
            }
        }
    }
}
