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

            //顶部加载进度条
            $top_loading = MaBox_Admin::get_config($option, 'top_loading');
            //有值
            if ($top_loading === true) {
                require_once plugin_dir_path(__FILE__) . 'top_loading/index.php';
                Npcink_Page_Top_Loading::run();
            }


            //点击特效
            $particle = MaBox_Admin::get_config($option, 'particle', "false");
            //有值且不是手机端
            if ($particle !== false && !wp_is_mobile()) {
                require_once plugin_dir_path(__FILE__) . 'click_effect/index.php';
                Npcink_Page_Add_Click_Effect::run($particle);
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
                require_once plugin_dir_path(__FILE__) . 'screen_hair/index.php';
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
                require_once plugin_dir_path(__FILE__) . 'lantern/index.php';
                Npcink_Page_Lantern::run($option);
            }

 
             //像素小鸡
             $pixel_chicken = MaBox_Admin::get_config($option, 'pixel_chicken');
             if ($pixel_chicken === true) {
                 require_once plugin_dir_path(__FILE__) . 'pixel_chicken/index.php';
                 Npcink_Page_Pixel_Chicken::run();
             }


            //已写完的书
            $past_books = MaBox_Admin::get_config($option, 'past_books');
            if ($past_books === true) {
                require_once plugin_dir_path(__FILE__) . 'completed_book.php';
                Npcink_Page_Completed_Book::run();
            }

            //复制弹窗
            $copy_pop_up = MaBox_Admin::get_config($option, 'copy_pop_up');
            if ($copy_pop_up !== "false") {
                require_once plugin_dir_path(__FILE__) . 'copy_pop_up/index.php';
                Npcink_Page_Copy_Pop_Up::run($copy_pop_up);
            }

            //平滑滚动
            $page_scrolling = MaBox_Admin::get_config($option, 'page_scrolling');
            if ($page_scrolling === true) {
                require_once plugin_dir_path(__FILE__) . 'scrolling/index.php';
                Npcink_Page_Scrolling::run();
            }

            //上吊猫
            $page_back_top_cat = MaBox_Admin::get_config($option, 'page_back_top_cat');
            if ($page_back_top_cat === true) {
                require_once plugin_dir_path(__FILE__) . 'back_top_cat/index.php';
                Npcink_Page_Back_Top_Cat::run($option);
            }

             //背景特效
             $background_effect = MaBox_Admin::get_config($option, 'background_effect');
             if ($background_effect !== 'false') {
                 require_once plugin_dir_path(__FILE__) . 'background_effect/index.php';
                 Npcink_Page_Background_Effect::run($background_effect);
             }
        }
    }
}
