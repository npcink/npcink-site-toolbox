<?php

/**
 * 外观美化
 */

if (!class_exists('Npcink_Page_Exterior')) {
    class Npcink_Page_Exterior
    {
        public static function run($option)
        {
            //动态标题
            $title = MaBox_Admin::get_config($option, 'title');
            if ($title === true) {
                require_once plugin_dir_path(__FILE__) . 'dynamic_title.php';
                Npcink_Page_Dynamic_Title::run($option);
            }


            //烟花特效
            $particle = MaBox_Admin::get_config($option, 'particle', "false");
            if ($particle === "diffuse") {
                require_once plugin_dir_path(__FILE__) . 'click_effect.php';
                Npcink_Page_Add_Click_Effect::run();
            }

            //汇聚线条
            $coupling = MaBox_Admin::get_config($option, 'coupling');
            if ($coupling === true) {
                require_once plugin_dir_path(__FILE__) . 'convergence_line.php';
                Npcink_Page_Add_Convergence_Line::run();
            }

            //美化滚动条
            $scrol = MaBox_Admin::get_config($option, 'scrol');
            if ($scrol !== "false") {
                require_once plugin_dir_path(__FILE__) . 'add_scroll_bar.php';
                Npcink_Page_Add_Scroll_Bar::run($scrol);
            }

            //屏幕上有根毛
            $screen_hair = MaBox_Admin::get_config($option, 'screen_hair');
            if ($screen_hair === true) {
                require_once plugin_dir_path(__FILE__) . 'screen_hair.php';
                Npcink_Page_Screen_Hair::run();
            }

            /**
             * 网页整体变灰
             */
            $site_grey =  MaBox_Admin::get_config($option, 'site_grey');
            if ($site_grey === true) {
                require_once plugin_dir_path(__FILE__) . 'all_grey.php';
                Npcink_Page_All_Grey::run();
            }
            /**
             * 添加灯笼
             */
            $lantern =  MaBox_Admin::get_config($option, 'lantern');
            if ($lantern === true) {
                require_once plugin_dir_path(__FILE__) . 'lantern.php';
                Npcink_Page_Lantern::run($option);
            }

            /**
             * 添加樱花
             */
            $sakura =  MaBox_Admin::get_config($option, 'sakura');
            if ($sakura === true) {
                require_once plugin_dir_path(__FILE__) . 'sakura_drops.php';
                Npcink_Page_Sakura_Drops::run();
            }

            //已写完的书
            $past_books = MaBox_Admin::get_config($option, 'past_books');
            if ($past_books === true) {
                require_once plugin_dir_path(__FILE__) . 'completed_book.php';
                Npcink_Page_Completed_Book::run();
            }
        }
    }
}
