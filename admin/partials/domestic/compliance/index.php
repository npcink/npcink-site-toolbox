<?php
defined('ABSPATH') || exit;
if (!class_exists('Npcink_Toolbox_Domestic_Compliance')) {
    class Npcink_Toolbox_Domestic_Compliance implements Npcink_Toolbox_Module_Interface {
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
                $output .= '<a class="mabox-icp" href="' . $link . '" target="_blank" rel="nofollow noopener">' . esc_html($c['icp_number']) . '</a>';
            }
            if (!empty($c['police_enabled']) && !empty($c['police_number'])) {
                $link = !empty($c['police_link']) ? esc_url($c['police_link']) : 'https://www.beian.gov.cn/portal/registerSystemInfo';
                if ($output) $output .= ' | ';
                $output .= '<a href="' . $link . '" target="_blank" rel="nofollow noopener">' . esc_html($c['police_number']) . '</a>';
            }
            if (!empty($c['copyright_enabled'])) {
                if ($output) $output .= ' | ';
                if (!empty($c['copyright_html'])) {
                    $output .= wp_kses_post($c['copyright_html']);
                } else {
                    $output .= sprintf(
                        /* translators: 1: Current year. 2: Site name. */
                        esc_html__('© %1$s %2$s 版权所有', 'npcink-site-toolbox'),
                        wp_date('Y'),
                        get_bloginfo('name')
                    );
                }
            }
            if ($output) {
                echo '<div class="mabox-compliance-footer" style="text-align:center;padding:15px 0;font-size:13px;color:#666;">' . wp_kses_post($output) . '</div>';
            }
        }
        public static function enqueue_cookie_assets() {
            $c = self::$config;
            if (empty($c['cookie_enabled'])) return;
            if (isset($_COOKIE['npcink_site_toolbox_cookie_consent'])) return;
            $title = !empty($c['cookie_title']) ? $c['cookie_title'] : __('Cookie 同意', 'npcink-site-toolbox');
            $content = !empty($c['cookie_content']) ? $c['cookie_content'] : __('本网站使用 Cookie 来改善您的体验。', 'npcink-site-toolbox');
            $button = !empty($c['cookie_button']) ? $c['cookie_button'] : __('我知道了', 'npcink-site-toolbox');
            $style = !empty($c['cookie_style']) ? $c['cookie_style'] : 'bottom';
            if ($style === 'center') {
                $position = 'top:50%;left:50%;transform:translate(-50%,-50%);max-width:min(520px,calc(100vw - 32px));border-radius:8px;';
            } elseif ($style === 'top') {
                $position = 'top:0;left:0;right:0;';
            } else {
                $position = 'bottom:0;left:0;right:0;';
            }
            $css = '.mabox-cookie-banner{position:fixed;' . $position . 'background:rgba(0,0,0,0.85);color:#fff;z-index:99999;padding:15px;text-align:center;font-size:14px;display:flex;align-items:center;justify-content:center;gap:15px;flex-wrap:wrap;}';
            $css .= '.mabox-cookie-banner button{background:#1677ff;color:#fff;border:none;padding:8px 20px;border-radius:4px;cursor:pointer;}';
            wp_register_style('mabox-cookie-style', false, array(), NPCINK_SITE_TOOLBOX_VERSION);
            wp_add_inline_style('mabox-cookie-style', $css);
            wp_enqueue_style('mabox-cookie-style');
            $cookie = 'npcink_site_toolbox_cookie_consent=1; path=/; max-age=' . (365 * DAY_IN_SECONDS) . '; SameSite=Lax';
            if (is_ssl()) {
                $cookie .= '; Secure';
            }
            $payload = wp_json_encode(
                array(
                    'title'   => (string) $title,
                    'content' => (string) $content,
                    'button'  => (string) $button,
                    'cookie'  => $cookie,
                ),
                JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
            );
            if (!is_string($payload)) return;
            $js = "document.addEventListener('DOMContentLoaded',function(){const c={$payload};const b=document.createElement('div');b.className='mabox-cookie-banner';const t=document.createElement('span');t.textContent=c.title+': '+c.content;const a=document.createElement('button');a.type='button';a.textContent=c.button;a.addEventListener('click',function(){b.remove();document.cookie=c.cookie;});b.append(t,a);document.body.appendChild(b);});";
            wp_register_script('mabox-cookie-script', false, array(), NPCINK_SITE_TOOLBOX_VERSION, true);
            wp_add_inline_script('mabox-cookie-script', $js);
            wp_enqueue_script('mabox-cookie-script');
        }
    }
}
