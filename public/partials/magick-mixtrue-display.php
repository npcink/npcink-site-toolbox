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
            self::load_owo();

        }

        /**
         * 加载表情包
         */
        public static function load_owo()
        {

            //判断，当前文章或页面是否开启评论

            //加载js和css资源
            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_owo_resouce'));
            //加载配置js
            add_action('wp_footer', array(__CLASS__, 'load_owo_comment_js'));
            //加载表情包位置
            add_filter('comment_form_defaults', array(__CLASS__, 'load_owo_content'));

        }

        /**
         * 加载表情用资源
         */
        public static function load_owo_resouce()
        {
            wp_enqueue_script(
                "插件名",
                plugin_dir_url(\dirname(__FILE__)) . 'js/OwO.min.js',
                array(),
                "1.0.0",
                false
            );

            wp_enqueue_style(
                '插件名',
                plugin_dir_url(\dirname(__FILE__)) . 'css/OwO.min.css',
                array(),
                '1.0.0',
                'all'
            );
        }

        /**
         * 若安装指定主题，则加载商城统计内容
         */
        public function load_b2_shop()
        {
            $tool = new Magick_Mixtrue_Tool;
            //$theme = 'Twenty Twenty';
            $theme = 'B2 PRO';

            if ($tool->theme_active($theme)) {
                //安装了2020主题
                add_action('admin_menu', array(__CLASS__, 'add_menu_shop'));
            } else {
                //啥也不做
            }

        }

        /**
         * 加载表情用JS
         */
        public static function load_owo_comment_js()
        {

            $tool = new Magick_Mixtrue_Tool;
            //$theme = 'Twenty Twenty';
            $theme = 'B2 PRO';

            //输入框定位
            $target_id = 'comment';

            if ($tool->theme_active($theme)) {
                //安装了B2 PRO主题
                $target_id = 'textarea';
            } else {
                //啥也不做
            }

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
?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
