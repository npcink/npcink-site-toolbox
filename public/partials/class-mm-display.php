<?php

/**
 * 为插件提供面向公众的视图
 *
 */

if (!class_exists('Magick_Mixtrue_Display')) {
    class Magick_Mixtrue_Display
    {

        public static function run()
        {
            add_action('wp', array(__CLASS__, 'load'));
        }

        public static function load()
        {
            self::run_owo();
            self::run_particle();
        }

        /**
         * 效果：页面添加烟花粒子
         * 来源：https://www.iowen.cn/canvas-click-effect-second-edition/
         */
        public static function run_particle()
        {
            if (carbon_get_theme_option('cmma_page_show_particle')) {
                //手机端不加载
                if (!wp_is_mobile()) {
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'add_page_particle_js'));
                    add_action('wp_body_open', array(__CLASS__, 'add_page_particle'));
                }

            }

        }
        //添加文件
        public static function add_page_particle()
        {

            echo '<div id="clickCanvas" style=" position:fixed;left:0;top:0;z-index:999999999;pointer-events:none;"></div>';

        }
        //加载js
        public static function add_page_particle_js()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_particle-js',
                plugin_dir_url(\dirname(__FILE__)) . 'js/style-click-particle.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );

        }

        /**
         * 效果：评论区加载表情包
         * 来源：https://github.com/DIYgod/OwO
         */
        public static function run_owo()
        {
            //判断当前页面是否加载评论区
            if (comments_open()) {
                //判断开关
                if (carbon_get_theme_option('cmma_show_owo')) {
                    //加载js和css资源
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'load_owo_resouce'));
                    //加载配置js
                    add_action('wp_footer', array(__CLASS__, 'load_owo_comment_js'));
                    //加载表情包位置
                    add_filter('comment_form_defaults', array(__CLASS__, 'load_owo_content'));
                }
            }

        }

        /**
         * 加载表情用资源
         */
        public static function load_owo_resouce()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_OwO-js',
                plugin_dir_url(\dirname(__FILE__)) . 'js/OwO.min.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );

            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_OwO-css',
                plugin_dir_url(\dirname(__FILE__)) . 'css/OwO.min.css',
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
            //输入框定位
            $target_id = 'comment';

            //拿到表情包用js地址
            $json_src = plugin_dir_url(\dirname(__FILE__)) . 'json/OwO.json';
            ?>
        <script>
            let $src = '<?php echo $json_src ?>';
            let $target = '<?php echo $target_id ?>'
            var OwO_demo = new OwO({
                logo: 'OωO表情',
                container: document.getElementsByClassName('OwO')[0],
                target: document.getElementById($target),
                api: $src,
                position: 'down',
                width: '100%',
                maxHeight: '250px'
            });

        </script>
        <?php
}

        /**
         * 加载表情用文件内容
         */
        public static function load_owo_content($default)
        {
            $commenter = wp_get_current_commenter();
            $default['comment_field'] .= '<div class="OwO"></div>
        <style>
        .OwO {
            padding: 0 0 20px 0;
        }
        .OwO .OwO-body {
            position: initial!important;
        }
        </style>
        ';

            return $default;

        }

    }
}

//这个文件应该主要由HTML和一点点PHP组成
