<?php
defined('ABSPATH') || exit;
if (!class_exists('MaBox_Domestic_Compliance')) {
    class MaBox_Domestic_Compliance implements MaBox_Module_Interface {
        private static $config;
        public static function run($config = array()) {
            self::$config = $config;
            add_action('wp_footer', array(__CLASS__, 'render_footer'), 100);
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_cookie_assets'));
        }
        public static function render_footer() {
            $c = self::$config;
            $output = '';
            if (!empty($c['icp_enabled']) && !empty($c['icp_number'])) {
                $link = !empty($c['icp_link']) ? esc_url($c['icp_link']) : 'https://beian.miit.gov.cn/';
                $output .= '<span class="mabox-icp">' . esc_html($c['icp_number']) . '</span>';
            }
            if (!empty($c['police_enabled']) && !empty($c['police_number'])) {
                $link = !empty($c['police_link']) ? esc_url($c['police_link']) : 'https://www.beian.gov.cn/portal/registerSystemInfo';
                if ($output) $output .= ' | ';
                $output .= '<a href="' . $link . '" target="_blank" rel="nofollow">' . esc_html($c['police_number']) . '</a>';
            }
            if (!empty($c['copyright_enabled'])) {
                if ($output) $output .= ' | ';
                if (!empty($c['copyright_html'])) {
                    $output .= wp_kses_post($c['copyright_html']);
                } else {
                    $output .= '&copy; ' . wp_date('Y') . ' ' . esc_html(get_bloginfo('name')) . ' 版权所有';
                }
            }
            if ($output) {
                echo '<div class="mabox-compliance-footer" style="text-align:center;padding:15px 0;font-size:13px;color:#666;">' . wp_kses_post($output) . '</div>';
            }
        }
        public static function enqueue_cookie_assets() {
            $c = self::$config;
            if (empty($c['cookie_enabled'])) return;
            if (isset($_COOKIE['mabox_cookie_consent'])) return;
            $title = !empty($c['cookie_title']) ? $c['cookie_title'] : 'Cookie 同意';
            $content = !empty($c['cookie_content']) ? $c['content'] : '本网站使用 Cookie 来改善您的体验。';
            $button = !empty($c['cookie_button']) ? $c['cookie_button'] : '我知道了';
            $style = !empty($c['cookie_style']) ? $c['cookie_style'] : 'bottom';
            $css = '.mabox-cookie-banner{position:fixed;' . ($style === 'bottom' ? 'bottom:0;left:0;right:0;' : 'top:0;left:0;right:0;') . 'background:rgba(0,0,0,0.85);color:#fff;z-index:99999;padding:15px;text-align:center;font-size:14px;display:flex;align-items:center;justify-content:center;gap:15px;flex-wrap:wrap;}';
            $css .= '.mabox-cookie-banner button{background:#1677ff;color:#fff;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;}';
            wp_register_style('mabox-cookie-style', false, array(), MAGICK_MIXTURE_VERSION);
            wp_add_inline_style('mabox-cookie-style', $css);
            wp_enqueue_style('mabox-cookie-style');
            $js = "document.addEventListener('DOMContentLoaded',function(){var b=document.createElement('div');b.className='mabox-cookie-banner';b.innerHTML='<span>" . esc_js($title) . ': ' . esc_js($content) . "</span><button onclick=\"this.parentElement.remove();document.cookie=\\'mabox_cookie_consent=1;path=/;max-age=" . (365*24*3600) . "\\';\">" . esc_js($button) . "</button>';document.body.appendChild(b);});";
            wp_register_script('mabox-cookie-script', false, array(), MAGICK_MIXTURE_VERSION, true);
            wp_add_inline_script('mabox-cookie-script', $js);
            wp_enqueue_script('mabox-cookie-script');
        }
    }
}
