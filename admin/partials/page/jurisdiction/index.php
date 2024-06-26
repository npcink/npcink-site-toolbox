<?php

/**
 * 页面 权限
 */

if (!class_exists('Npcink_Page_Jurisdiction')) {
    class Npcink_Page_Jurisdiction
    {
        public static function run($option)
        {

            //禁止复制
            $ban_copy = MaBox_Admin::get_config($option, 'ban_copy');
            if ($ban_copy === true) {
                require_once plugin_dir_path(__FILE__) . 'ban_copy.php';
                Npcink_Page_Ban_Copy::run();
            }
             //禁用前端 F12 调试
             $front_debug = MaBox_Admin::get_config($option, 'front_debug');
             if ($front_debug !== false) {
                 require_once plugin_dir_path(__FILE__) . 'front_debug.php';
                 Npcink_Page_Front_Debug::run();
             }

            //分类数组
            $category_id = MaBox_Admin::get_config($option, 'category_id');

            //标签数组
            $tag_id = MaBox_Admin::get_config($option, 'tag_id');


            //总有默认分类，添加判断意义不大
            //添加分类数据接口
            require_once plugin_dir_path(__FILE__) . 'interface_category_data.php';
            Npcink_Interface_Category_Data::run();


            //隐藏指定分类
            if (!empty($category_id)) {
                require_once plugin_dir_path(__FILE__) . 'hide_category.php';
                Npcink_Page_Hide_Category::run($category_id);
            }

            //隐藏指定标签
            if (!empty($tag_id)) {
                require_once plugin_dir_path(__FILE__) . 'hide_tag.php';
                Npcink_Page_Hide_Tag::run($tag_id);
            }
        }
    }
}
