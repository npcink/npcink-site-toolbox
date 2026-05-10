<?php

/**
 * 效果：评论区加载表情包
 * 来源：https://github.com/DIYgod/OwO
 */

if (!class_exists('MaBox_Page_Comment_Emoji')) {
    class MaBox_Page_Comment_Emoji
    {
        public static function run()
        {
            add_action('wp', array(__CLASS__, 'run_owo'));
        }

        public static function run_owo()
        {
            /**
             * TODO:判断当前页面是否加载评论区
             */
            //获取当前页面的帖子对象
            $current_post = get_post();
            if ($current_post && $current_post->comment_status === 'open') {
                //加载js和css资源
                add_action('wp_enqueue_scripts', array(__CLASS__, 'load_owo_resouce'));
                //加载配置js
                add_action('wp_footer', array(__CLASS__, 'load_owo_comment_js'));
                //加载表情包位置
                add_filter('comment_form_defaults', array(__CLASS__, 'load_owo_content'));
            }
        }

        /**
         * 加载表情用资源
         */
        public static function load_owo_resouce()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_OwO-js',
                plugin_dir_url(__FILE__) . 'emoji/OwO.min.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );

            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_OwO-css',
                plugin_dir_url(__FILE__) . 'emoji/OwO.min.css',
                array(),
                MAGICK_MIXTURE_VERSION,
                'all'
            );
        }

        /**
         * 加载表情用JS
         */
        public static function load_owo_comment_js()
        {
            $target_id = esc_js('comment');
            $json_src = esc_js(plugin_dir_url(__FILE__) . 'emoji/OwO.json');
            echo '<script>var OwO_demo=new OwO({logo:"OωO表情",container:document.getElementsByClassName("OwO")[0],target:document.getElementById("' . $target_id . '"),api:"' . $json_src . '",position:"down",width:"100%",maxHeight:"250px"})</script>' . "\n";
        }

        /**
         * 加载表情用文件内容
         */
        public static function load_owo_content($default)
        {
            $default['comment_field'] .= '<div class="OwO"></div>';
            add_action('wp_head', array(__CLASS__, 'render_owo_inline_css'), 999);
            return $default;
        }

        public static function render_owo_inline_css()
        {
            echo '<style>.OwO{padding:0 0 20px 0}.OwO .OwO-body{position:initial!important}</style>' . "\n";
        }
    }
}
