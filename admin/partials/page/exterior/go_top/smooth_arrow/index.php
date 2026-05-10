<?php

/**
 * 效果：返回顶部
 * 平滑箭头：https://www.shephe.com/website/
 */
if (!class_exists('MaBox_Page_Go_Top_Smooth_Arrow')) {
    class MaBox_Page_Go_Top_Smooth_Arrow
    {
        public static function run()
        {
            //加载节点
            add_action('wp_head', array(__CLASS__, 'smooth_arrow'), 100);
            //加载jS
            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_js'));
        }
        //平滑箭头
        public static function smooth_arrow()
        {
?>
            <div id="topcontrol" class="grve-back-top">
                <div class="grve-arrow-wrapper" onclick="goTop()">
                    <svg width="16px" height="40px" viewBox="0 0 16 40">
                        <polygon class="grve-arrow-point" fill-rule="nonzero" points="8 0 14.75 6.60691267 13.3267423 8 8 2.78694936 2.67325773 8 1.25 6.60691267"></polygon>
                        <polygon class="grve-arrow-line" points="7 2 9 2 9 40 7 40"></polygon>
                    </svg>
                </div>
            </div>
<?php
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
            $build_css =  plugin_dir_url(__DIR__) . 'smooth_arrow/smooth_arrow.css';

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_public_smooth_arrow_js',
                $build_js,
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );

            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_public_smooth_arrow_css',
                $build_css,
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );
        }
    }
}
