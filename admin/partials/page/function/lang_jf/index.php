<?php

/**
 * 效果：前端简体字，繁体字切换
 * 来源：https://www.npc.ink/15778.html
 */

if (!class_exists('MaBox_Single_Lang_Jf')) {
    class MaBox_Single_Lang_Jf
    {
        public static function run()
        {
            //加载HTML
            add_action('wp_footer', array(__CLASS__, 'add_html'));

            //加载js
            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_js'));
        }

        //添加切换按钮
        public static function add_html()
        {
?>
            <a id="StranLink" class="wencode ">繁體</a>
            <style>
                .wencode {
                    bottom: 50px;
                    right: 50px;
                    position: fixed;
                    background: #ffffff;
                    border-color: #d9d9d9;
                    color: rgba(0, 0, 0, 0.88);
                    box-shadow: 0 2px 0 rgba(0, 0, 0, 0.02);
                    padding: 4px 15px;
                    font-size: 14px;
                    border-radius: 8px;
                    text-decoration: none;

                    transition: all 0.2s cubic-bezier(0.645, 0.045, 0.355, 1);
                }

                .wencode:hover {
                    background: #e2e2e2;
                }
            </style>
<?php
        }
        //加载jS文件
        public static function load_js()
        {
            //判断下，是否在前端页中
            if (is_admin()) {
                return;
            }

            //准备打包后的数据
            $build_js =  plugin_dir_url(__DIR__) . 'lang_jf/zh-cn-tw.js';

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_public_lang_jf_js',
                $build_js,
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
