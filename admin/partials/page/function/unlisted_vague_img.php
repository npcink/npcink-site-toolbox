<?php

defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Unlisted_Vague_Img')) {
    class Npcink_Toolbox_Unlisted_Vague_Img implements Npcink_Toolbox_Module_Interface
    {
        private static $config;

        public static function run($config = array())
        {
            self::$config = $config;
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
        }

        /**
         * 图片选择器可在设置中按主题调整，仅放行 CSS 选择器安全字符
         */
        private static function get_selector()
        {
            $raw = isset(self::$config['no_login_img_selector']) && is_string(self::$config['no_login_img_selector'])
                ? trim(self::$config['no_login_img_selector'])
                : '';
            if ($raw === '' || preg_match('/[^A-Za-z0-9_.#\->,+~:\[\]="\'() *]/', $raw)) {
                return '.entry-content img';
            }
            return $raw;
        }

        public static function enqueue_assets()
        {
            if (Npcink_Toolbox_Helpers::is_logged_in()) {
                return;
            }

            $selector = self::get_selector();
            $login_visible_label = wp_json_encode(__('登录可见', 'npcink-site-toolbox'));

            // 模糊样式；标签挂在包裹元素上（::before/::after 挂在 img 这类替换元素上不会渲染）
            $css = $selector . '{-webkit-filter:blur(10px)!important;-moz-filter:blur(10px)!important;-ms-filter:blur(10px)!important;filter:blur(6px)!important}';
            $css .= '.npcink-vague-wrap{position:relative;display:inline-block;max-width:100%}';
            $css .= '.npcink-vague-wrap::after{content:' . $login_visible_label . ';position:absolute;inset:0;display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;text-shadow:0 1px 3px rgba(0,0,0,.8);}';
            wp_register_style('npcink-site-toolbox-unlisted-images', false, array(), NPCINK_SITE_TOOLBOX_VERSION);
            wp_enqueue_style('npcink-site-toolbox-unlisted-images');
            wp_add_inline_style('npcink-site-toolbox-unlisted-images', $css);

            // 用包裹元素承载标签：把匹配的图片逐个包进 .npcink-vague-wrap
            $js = '(function(){var sel=' . wp_json_encode($selector) . ';';
            $js .= 'document.addEventListener("DOMContentLoaded",function(){';
            $js .= 'document.querySelectorAll(sel).forEach(function(img){';
            $js .= 'if(img.closest(".npcink-vague-wrap")){return;}';
            $js .= 'var wrap=document.createElement("span");wrap.className="npcink-vague-wrap";';
            $js .= 'img.parentNode.insertBefore(wrap,img);wrap.appendChild(img);';
            $js .= '});});})();';
            wp_register_script('npcink-site-toolbox-unlisted-images', false, array(), NPCINK_SITE_TOOLBOX_VERSION, true);
            wp_enqueue_script('npcink-site-toolbox-unlisted-images');
            wp_add_inline_script('npcink-site-toolbox-unlisted-images', $js);
        }
    }
}
