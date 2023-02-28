<?php

/**
 * 为插件提供面向公众的视图
 *
 *此文件用于标记插件的面向公共方面。
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/public/partials
 */

if (!class_exists('Magick_Mixtrue_Display')) {
    class Magick_Mixtrue_Display
    {

        public function __construct()
        {

            //加载表情包
            add_action('wp', array(__CLASS__, 'load_owo'));

        }

        /**
         * 加载表情包
         */
        public static function load_owo()
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
                MAGICK_MIXTURE_NAME,
                plugin_dir_url(\dirname(__FILE__)) . 'js/OwO.min.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );

            wp_enqueue_style(
                MAGICK_MIXTURE_NAME,
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

class My_Plugin
{
    public function __construct()
    {
        // Add a hook to execute our function on page load
        add_action('wp', array($this, 'my_function'));
    }

    public function my_function()
    {
        // Get the current page ID
        $page_id = get_the_ID();

        // Do something with the page ID
        echo 'The current page ID is: ' . $page_id;
        return $page_id;
    }
}

// Instantiate the plugin class
//$my_plugin = new My_Plugin();

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
