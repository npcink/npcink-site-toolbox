<?php
defined('ABSPATH') || exit;
if (!class_exists('MaBox_Domestic_Wechat')) {
    class MaBox_Domestic_Wechat implements MaBox_Module_Interface {
        private static $config;
        public static function run($config = array()) {
            self::$config = $config;
            if (!empty($config['jssdk_enabled']) && !empty($config['appid'])) {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'jssdk_config'));
            }
            if (!empty($config['guide_overlay_enabled'])) {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'guide_overlay'));
            }
        }
        public static function jssdk_config() {
            if (!is_singular()) return;
            $appid = self::$config['appid'];
            $appsecret = self::$config['appsecret'];
            if (empty($appsecret)) return;
            $ticket = get_transient('mabox_wx_jsapi_ticket');
            if (empty($ticket)) {
                $token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . urlencode($appid) . '&secret=' . urlencode($appsecret);
                $token_res = wp_remote_get($token_url);
                if (!is_wp_error($token_res)) {
                    $token_data = json_decode(wp_remote_retrieve_body($token_res), true);
                    if (!empty($token_data['access_token'])) {
                        $ticket_url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . $token_data['access_token'] . '&type=jsapi';
                        $ticket_res = wp_remote_get($ticket_url);
                        if (!is_wp_error($ticket_res)) {
                            $ticket_data = json_decode(wp_remote_retrieve_body($ticket_res), true);
                            if (!empty($ticket_data['ticket'])) {
                                $ticket = $ticket_data['ticket'];
                                set_transient('mabox_wx_jsapi_ticket', $ticket, 7000);
                            }
                        }
                    }
                }
            }
            if (empty($ticket)) return;
            $url = get_permalink();
            $nonce = wp_create_nonce('mabox_wx_jssdk');
            $timestamp = time();
            $string = "jsapi_ticket=$ticket&noncestr=$nonce&timestamp=$timestamp&url=$url";
            $signature = sha1($string);
            $title = get_the_title();
            $desc = has_excerpt() ? get_the_excerpt() : wp_trim_words(get_the_content(), 50);
            $img = get_the_post_thumbnail_url(get_the_ID(), 'thumbnail') ?: '';
            $js = "wx.config({appId:'" . esc_js($appid) . "',timestamp:$timestamp,nonceStr:'" . esc_js($nonce) . "',signature:'" . esc_js($signature) . "',jsApiList:['onMenuShareTimeline','onMenuShareAppMessage','updateAppMessageShareData','updateTimelineShareData']});";
            $js .= "wx.ready(function(){var shareData={title:'" . esc_js($title) . "',desc:'" . esc_js($desc) . "',link:'" . esc_js($url) . "',imgUrl:'" . esc_js($img) . "'};wx.onMenuShareAppMessage(shareData);wx.onMenuShareTimeline(shareData);});";
            wp_register_script('mabox-wechat-jssdk', 'https://res.wx.qq.com/open/js/jweixin-1.6.0.js', array(), '1.6.0', true);
            wp_add_inline_script('mabox-wechat-jssdk', $js);
            wp_enqueue_script('mabox-wechat-jssdk');
        }
        public static function guide_overlay() {
            if (!self::is_wechat_qq()) return;
            $mode = !empty(self::$config['guide_mode']) ? self::$config['guide_mode'] : 'guide';
            $text = !empty(self::$config['guide_text']) ? self::$config['guide_text'] : '点击右上角 ··· 在浏览器中打开';
            $css = '.mabox-wechat-guide{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.9);z-index:99999;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;text-align:center;padding:20px;}';
            $css .= '.mabox-wechat-guide .arrow{position:absolute;top:20px;right:30px;font-size:40px;transform:rotate(-45deg);}';
            $css .= '.mabox-wechat-guide .text{font-size:18px;margin-top:60px;line-height:1.6;}';
            wp_register_style('mabox-wechat-guide-style', false, array(), MAGICK_MIXTURE_VERSION);
            wp_add_inline_style('mabox-wechat-guide-style', $css);
            wp_enqueue_style('mabox-wechat-guide-style');
            $html = '<div class="mabox-wechat-guide"><div class="arrow">↗</div><div class="text">' . esc_html($text) . '</div></div>';
            if (!empty(self::$config['guide_qrcode'])) {
                $html .= '<div style="margin-top:20px;"><img src="' . esc_url(self::$config['guide_qrcode']) . '" style="width:150px;height:150px;background:#fff;padding:5px;border-radius:8px;" alt="qrcode"></div>';
            }
            $js = "document.addEventListener('DOMContentLoaded',function(){document.body.insertAdjacentHTML('beforeend','" . str_replace("'", "\\'", $html) . "');});";
            if ($mode === 'redirect') {
                $js .= "if(document.querySelector('.mabox-wechat-guide')){document.body.style.overflow='hidden';}";
            }
            wp_register_script('mabox-wechat-guide-script', false, array(), MAGICK_MIXTURE_VERSION, true);
            wp_add_inline_script('mabox-wechat-guide-script', $js);
            wp_enqueue_script('mabox-wechat-guide-script');
        }
        private static function is_wechat_qq() {
            $ua = isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])
                ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))
                : '';
            return stripos($ua, 'MicroMessenger') !== false || stripos($ua, 'QQ') !== false;
        }
    }
}
