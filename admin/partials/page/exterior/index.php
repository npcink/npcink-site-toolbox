<?php

/**
 * 外观美化
 */

if (!class_exists('Npcink_Page_Exterior')) {
    class Npcink_Page_Exterior
    {
        public static function run($option)
        {


            //烟花特效
            $particle = MaMi_Admin::get_config($option, 'particle', "false");
            if ($particle === "diffuse") {
                require_once plugin_dir_path(__FILE__) . 'add_fireworks.php';
                Npcink_Page_Add_Fireworks::run();
            }

            //粒子特效
            $coupling = MaMi_Admin::get_config($option, 'coupling');
            if ($coupling === true) {
                require_once plugin_dir_path(__FILE__) . 'add_particle.php';
                Npcink_Page_Add_Particle::run();
            }

            //美化滚动条
            $scrol = MaMi_Admin::get_config($option, 'scrol');
            if ($scrol !== "false") {
                require_once plugin_dir_path(__FILE__) . 'add_scroll_bar.php';
                Npcink_Page_Add_Scroll_Bar::run($scrol);
            }

            //屏幕上有根毛
            $screen_hair = MaMi_Admin::get_config($option, 'screen_hair');
            if ($screen_hair === true) {
                require_once plugin_dir_path(__FILE__) . 'screen_hair.php';
                Npcink_Page_Screen_Hair::run();
            }

            /**
             * 网页整体变灰
             */
            $site_grey =  MaMi_Admin::get_config($option, 'site_grey');
            if ($site_grey === true) {
                require_once plugin_dir_path(__FILE__) . 'all_grey.php';
                Npcink_Page_All_Grey::run();
            }
            /**
             * 添加灯笼
             */
            $lantern =  MaMi_Admin::get_config($option, 'lantern');
            if ($lantern === true) {
                require_once plugin_dir_path(__FILE__) . 'lantern.php';
                Npcink_Page_Lantern::run($option);
            }

            /**
             * 添加樱花
             */
            $sakura =  MaMi_Admin::get_config($option, 'sakura');
            if ($sakura === true) {
                require_once plugin_dir_path(__FILE__) . 'sakura_drops.php';
                Npcink_Page_Sakura_Drops::run();
            }
        }
    }
}
