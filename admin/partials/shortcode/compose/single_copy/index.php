<?php

/**
 * 功能：复制按钮
 * 来源：
 */
if (!class_exists('MaBox_ShortCode_Single_Copy')) {
    class MaBox_ShortCode_Single_Copy
    {
        public static function run()
        {
            //添加短代码
            add_shortcode('mabox_copy_btn', array(__CLASS__, 'caption_shortcode'));

            // 判断当前页面是否有 mabox_copy_btn 短代码，如果有则加载前端资源
            add_action('wp_enqueue_scripts', function () {
                global $post;
                if (has_shortcode($post->post_content, 'mabox_copy_btn')) {
                    self::load_js();
                }
            });
        }
        //解析短代码
        public static function caption_shortcode($atts, $content = null)
        {
            $a = shortcode_atts(array(
                'name' => '按钮名称',
                'alert' => '复制成功提示',
                'link' => '跳转链接',
                // ...etc
            ), $atts);
            $name = esc_attr($a['name']);
            $alert = esc_attr($a['alert']);
            $link = esc_url($a['link']);
            //递归解析短代码
            // 生成按钮的 HTML 代码，使用 htmlspecialchars 进行安全输出
            $button_html = '
            
            <span style="display: block ruby;text-align: center;">
            <span class="mabox_copy_btn"  onClick="copys(&quot;' . htmlspecialchars($content) . '&quot;, &quot;' . htmlspecialchars($alert) . '&quot;, &quot;' . htmlspecialchars($link) . '&quot;)">
            
            <span class="button__text">
            ' . $name . '
            </span>
                
                
                <span class="button__icon">
            <svg t="1720593066129" class="icon" viewBox="0 0 1024 1024" version="1.1" xmlns="http://www.w3.org/2000/svg" p-id="4463" width="16" height="16"><path d="M678.758927 731.426377a51.930909 51.930909 0 0 0 52.66233-51.930909V51.936029A51.930909 51.930909 0 0 0 678.758927 0.00512H51.930909A51.930909 51.930909 0 0 0 0 51.936029v627.559439a51.930909 51.930909 0 0 0 51.930909 51.930909h626.828018z" p-id="4464" fill="#ffffff"></path><path d="M971.32743 292.573623H804.563383v454.212601a57.782279 57.782279 0 0 1-57.782279 57.782279H292.568503v167.495468a51.930909 51.930909 0 0 0 52.66233 51.930909h626.828018a51.930909 51.930909 0 0 0 51.930909-51.930909V344.504532a51.930909 51.930909 0 0 0-52.66233-51.930909z" p-id="4465" fill="#ffffff"></path></svg>
            </span>
                </span></span>
                ';

            return $button_html;
        }

        //加载JS
        public static function load_js()
        {
            //判断下，是否在前端页中
            if (is_admin()) {
                return;
            }

            //准备css
            $build_css =  plugin_dir_url(__DIR__) . 'single_copy/copy.css';
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_public_single_copy_css',
                $build_css,
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );
            //准备js 
            $build_js =  plugin_dir_url(__DIR__) . 'single_copy/copy.js';
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_public_single_copy_js',
                $build_js,
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
