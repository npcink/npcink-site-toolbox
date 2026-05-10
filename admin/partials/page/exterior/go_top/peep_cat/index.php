<?php

/**
 * 效果：偷瞄猫猫
 * 来源1：https://lishuma.com/connect
 * 来源2：https://www.shephe.com/website/
 */
if (!class_exists('MaBox_Page_Go_Top_Peep_Cat')) {
    class MaBox_Page_Go_Top_Peep_Cat
    {
        public static function run()
        {
            //偷瞄猫猫
            add_action('wp_head', array(__CLASS__, 'peep_cat'), 100);
            //加载jS
            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_js'));
            //add_action('wp_enqueue_scripts', array(__CLASS__, 'peep_cat_js'));//纯jS方案
        }
        //偷瞄猫猫
        public static function peep_cat()
        {
            //准备图片地址
            $cat_url = plugin_dir_url(__FILE__) . 'cat.png';
?>
            <div id="topcontrol" onclick="goTop()">
                <img src="<?php echo esc_url($cat_url); ?>" alt="偷瞄猫猫" title="偷瞄猫猫">
            </div>
            <style>
                #topcontrol {
                    position: fixed;
                    bottom: 20px;
                    right: 0px;
                    /* 修正位置使其不贴边 */
                    opacity: 0;
                    /* 初始状态隐藏 */
                    transition: opacity 0.3s ease;
                    /* 动画效果 */
                    cursor: pointer;
                    z-index: 99;
                }

                #topcontrol.npcShow {
                    opacity: 1;
                    /* 滚动到一定高度后显示 */
                }
            </style>
<?php
        }


        //加载资源
        public static function peep_cat_js()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_go_top_cat',
                plugin_dir_url(__FILE__) . 'peep_cat/cat.js',
                array("jquery"),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
        public static function load_js()
        {
            //加载jS
            //判断下，是否在前端页中
            if (is_admin()) {
                return;
            }

            //准备数据
            $build_js =  plugin_dir_url(__DIR__) . 'go_top.js';

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_public_go_top_js',
                $build_js,
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
