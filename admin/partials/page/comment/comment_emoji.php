<?php

/**
 * 效果：评论区加载表情包
 * 来源：https://github.com/DIYgod/OwO
 */

if (!class_exists('Npcink_Page_Comment_Emoji')) {
    class Npcink_Page_Comment_Emoji
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
                false
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
            //输入框定位
            $target_id = 'comment';

            //拿到表情包用js地址
            $json_src = plugin_dir_url(__FILE__) . 'emoji/OwO.json';
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
            //$commenter = wp_get_current_commenter();
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
