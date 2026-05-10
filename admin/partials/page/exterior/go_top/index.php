<?php

/**
 * 效果：返回顶部挂件
 * 平滑箭头：https://www.shephe.com/website/
 */
if (!class_exists('MaBox_Page_Go_Top')) {
    class MaBox_Page_Go_Top
    {
        //选项值，进一步调用的值
        public static function run($config, $option)
        {
            //偷瞄猫猫
            if ($config === "peep_cat") {
                require_once plugin_dir_path(__FILE__) . 'peep_cat/index.php';
                MaBox_Page_Go_Top_Peep_Cat::run();
            }
            //平滑箭头
            if ($config === "smooth_arrow") {
                require_once plugin_dir_path(__FILE__) . 'smooth_arrow/index.php';
                MaBox_Page_Go_Top_Smooth_Arrow::run();
            }
            //抓绳猫猫
            if ($config === "cord_cat") {
                require_once plugin_dir_path(__FILE__) . 'cord_cat/index.php';
                MaBox_Page_Back_Top_Cat::run($option);
            }
        }
    }
}
