<?php

if (!class_exists('MaBox_Page_Copy_Pop_Up')) {
    class MaBox_Page_Copy_Pop_Up
    {
        public static function run($config)
        {
            if ($config === "concise") {
                add_action('wp_footer', array(__CLASS__, 'render_concise'), 999);
            }
            if ($config === "sweetalert") {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_sweetalert'));
                add_action('wp_footer', array(__CLASS__, 'render_sweetalert_js'), 999);
            }
        }

        public static function render_concise()
        {
            echo '<script>document.body.oncopy=function(){alert("复制成功！若要转载请务必保留原文链接，谢谢合作！")}</script>' . "\n";
        }

        public static function enqueue_sweetalert()
        {
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_sweetalert',
                plugin_dir_url(__FILE__) . 'sweetalert/sweetalert.min.css',
                array(),
                MAGICK_MIXTURE_VERSION
            );
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_sweetalert',
                plugin_dir_url(__FILE__) . 'sweetalert/sweetalert.min.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        public static function render_sweetalert_js()
        {
            echo '<script>document.addEventListener("copy",function(e){var t=window.getSelection().toString();swal("复制成功！","转载请务必保留原文链接，申明来源，谢谢合作！！","success");navigator.clipboard.writeText(t).catch(function(e){console.error("写入剪贴板失败:",e)})})</script>' . "\n";
        }
    }
}
