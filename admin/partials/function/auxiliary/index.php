<?php

/**
 * 辅助功能
 */
if (!class_exists('MaMi_Function_Auxiliary')) {
    class MaMi_Function_Auxiliary
    {
        public static function run($auxiliary)
        {
            //加载文件
            self::load();

            //加载文章统计
            $single_count = MaMi_Admin::get_config($auxiliary, 'single_count');
            if ($single_count === true) {
                Magick_Mixtrue_Census_Single::run();
            }

            //屏蔽恶意关键词搜索
            $no_malice_key = MaMi_Admin::get_config($auxiliary, 'no_malice_key'); //状态
            $keyword_arr = MaMi_Admin::get_config($auxiliary, 'malice_keu_content'); //关键词数组
            if ($no_malice_key === true) {
                Npcink_Ban_Malice_Search::run($keyword_arr);
            }

           

            //跳转中间页
            $go_middle = MaMi_Admin::get_config($auxiliary, 'go_middle');
            if ($go_middle !== false) {
                Npcink_Jump_Middle_Page::run($go_middle);
            }
        }

        /**
         * 加载所需文件
         */
        public static function load()
        {
            //文章统计页面
            require_once plugin_dir_path(__FILE__) . '/census-single.php';

            //屏蔽恶意关键词搜索
            require_once plugin_dir_path(__FILE__) . 'ban_malice_search.php';

          

            //跳转中间页
            require_once plugin_dir_path(__FILE__) . 'jump_middle_page.php';
        }
    }
}
