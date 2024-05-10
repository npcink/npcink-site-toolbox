<?php

/**
 * 外观美化
 */

if (!class_exists('Npcink_Page_Exterior')) {
    class Npcink_Page_Exterior
    {
        public static function run($option)
        {


            //烟花粒子特效
            $particle = MaMi_Admin::get_config($option, 'particle', "false");
            //四散
            if ($particle === "diffuse") {
                require_once plugin_dir_path(__FILE__) . 'page_add_particle.php';
                Npcink_Page_Add_Particle::run();
            }

            //美化滚动条
            $scrol = MaMi_Admin::get_config($option, 'scrol');
            if ($scrol !== "false") {
                require_once plugin_dir_path(__FILE__) . 'page_scroll_bar.php';
                Npcink_Page_Add_Scroll_Bar::run($scrol);
            }
        }
    }
}
