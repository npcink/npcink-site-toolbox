<?php

/**
 * 效果：背景特效
 * 来源：
 */
if (!class_exists('MaBox_Page_Background_Effect')) {
    class MaBox_Page_Background_Effect
    {
        public static function run($config)
        {


            switch ($config) {
                case 'star': //底部飘星星
                    require_once plugin_dir_path(__FILE__) . 'footer-star/index.php';
                    MaBox_Page_Footer_Star::run();
                    break;
                case 'sakura': //飘落樱花
                    require_once plugin_dir_path(__FILE__) . 'sakura_drops/index.php';
                    MaBox_Page_Sakura_Drops::run();
                    break;
                case 'coupling': //细线联结
                    require_once plugin_dir_path(__FILE__) . 'convergence_line/index.php';
                    MaBox_Page_Add_Convergence_Line::run();
                    break;
                case 'flowing_lines': //流动线条
                    require_once plugin_dir_path(__FILE__) . 'flowing_lines/index.php';
                    MaBox_Page_Flowing_Lines::run();
                    break;
                case 'drip_ink': //滴墨水
                    require_once plugin_dir_path(__FILE__) . 'drip_ink/index.php';
                    MaBox_Page_Drip_Ink::run();
                    break;
                case 'sliding_ribbon': //流动彩带
                    require_once plugin_dir_path(__FILE__) . 'sliding_ribbon/index.php';
                    MaBox_Page_Sliding_Ribbon::run();
                    break;
                case 'random_ribbon': //随机彩带
                    require_once plugin_dir_path(__FILE__) . 'random_ribbon/index.php';
                    MaBox_Page_Random_Ribbon::run();
                    break;
                case 'floating_sphere': //漂浮球体
                    require_once plugin_dir_path(__FILE__) . 'floating_sphere/index.php';
                    MaBox_Page_Floating_Sphere::run();
                    break;
            }
        }
    }
}
