<?php
defined('ABSPATH') || exit;
if (!class_exists('Npcink_Toolbox_Domestic_Wechat')) {
    class Npcink_Toolbox_Domestic_Wechat implements Npcink_Toolbox_Module_Interface {
        private static $config;
        public static function run($config = array()) {
            self::$config = $config;
            if (!empty($config['jssdk_enabled']) && !empty($config['appid'])) {
                // 票据改为后台定时刷新：渲染期只读缓存，避免页面输出被微信 API 阻塞
                add_action('npcink_site_toolbox_wechat_ticket_refresh', array(__CLASS__, 'refresh_jsapi_ticket'));
                if (!wp_next_scheduled('npcink_site_toolbox_wechat_ticket_refresh')) {
                    wp_schedule_event(time() + 60, 'hourly', 'npcink_site_toolbox_wechat_ticket_refresh');
                    // 首次启用时尽快补一次票据，避免第一小时分享配置缺位
                    if (empty(get_transient('npcink_site_toolbox_wx_jsapi_ticket'))) {
                        wp_schedule_single_event(time(), 'npcink_site_toolbox_wechat_ticket_refresh');
                    }
                }
                add_action('wp_enqueue_scripts', array(__CLASS__, 'jssdk_config'));
            }
            if (!empty($config['guide_overlay_enabled'])) {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'guide_overlay'));
            }
        }

        public static function clear_ticket_schedule() {
            wp_clear_scheduled_hook('npcink_site_toolbox_wechat_ticket_refresh');
        }

        public static function refresh_jsapi_ticket() {
            // 设置被关闭后定时任务可能在窗口期内残留触发，这里再校验一次
            $config = Npcink_Toolbox_Helpers::get_config('domestic', 'wechat', array());
            if (!is_array($config) || empty($config['jssdk_enabled']) || empty($config['appid']) || empty($config['appsecret'])) {
                return;
            }
            $token_url = 'https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=' . urlencode($config['appid']) . '&secret=' . urlencode($config['appsecret']);
            $token_res = wp_remote_get($token_url, array('timeout' => 5));
            if (is_wp_error($token_res)) {
                return;
            }
            $token_data = json_decode(wp_remote_retrieve_body($token_res), true);
            if (empty($token_data['access_token'])) {
                return;
            }
            $ticket_url = 'https://api.weixin.qq.com/cgi-bin/ticket/getticket?access_token=' . urlencode($token_data['access_token']) . '&type=jsapi';
            $ticket_res = wp_remote_get($ticket_url, array('timeout' => 5));
            if (is_wp_error($ticket_res)) {
                return;
            }
            $ticket_data = json_decode(wp_remote_retrieve_body($ticket_res), true);
            if (!empty($ticket_data['ticket'])) {
                set_transient('npcink_site_toolbox_wx_jsapi_ticket', $ticket_data['ticket'], 7000);
            }
        }

        public static function jssdk_config() {
            if (!is_singular()) return;
            $appid = self::$config['appid'];
            $ticket = get_transient('npcink_site_toolbox_wx_jsapi_ticket');
            // 渲染期绝不发起远程请求：票据缺失时本次跳过分享配置，待后台任务补齐
            if (empty($ticket)) return;
            $url = get_permalink();
            $nonce = wp_create_nonce('npcink_site_toolbox_wx_jssdk');
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
            $text = !empty(self::$config['guide_text']) ? self::$config['guide_text'] : __('点击右上角 ··· 在浏览器中打开', 'npcink-site-toolbox');
            $css = '.mabox-wechat-guide{position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.9);z-index:99999;display:flex;flex-direction:column;align-items:center;justify-content:center;color:#fff;text-align:center;padding:20px;}';
            $css .= '.mabox-wechat-guide .arrow{position:absolute;top:20px;right:30px;font-size:40px;transform:rotate(-45deg);}';
            $css .= '.mabox-wechat-guide .text{font-size:18px;margin-top:60px;line-height:1.6;}';
            $css .= '.mabox-wechat-guide .dismiss{margin-top:28px;padding:10px 28px;font-size:15px;line-height:1;border:none;border-radius:20px;background:#fff;color:#333;cursor:pointer;}';
            wp_register_style('mabox-wechat-guide-style', false, array(), NPCINK_SITE_TOOLBOX_VERSION);
            wp_add_inline_style('mabox-wechat-guide-style', $css);
            wp_enqueue_style('mabox-wechat-guide-style');
            // 保留关闭途径：遮罩必须可退出，避免误命中 UA 的访客被永久挡在页面外
            $dismiss_text = __('继续浏览', 'npcink-site-toolbox');
            $html = '<div class="mabox-wechat-guide"><div class="arrow">↗</div><div class="text">' . esc_html($text) . '</div>';
            if (!empty(self::$config['guide_qrcode'])) {
                $html .= '<div style="margin-top:20px;"><img src="' . esc_url(self::$config['guide_qrcode']) . '" style="width:150px;height:150px;background:#fff;padding:5px;border-radius:8px;" alt="qrcode"></div>';
            }
            $html .= '<button type="button" class="dismiss">' . esc_html($dismiss_text) . '</button></div>';
            $js = "document.addEventListener('DOMContentLoaded',function(){document.body.insertAdjacentHTML('beforeend','" . str_replace("'", "\\'", $html) . "');";
            $js .= "var dismissBtn=document.querySelector('.mabox-wechat-guide .dismiss');";
            $js .= "if(dismissBtn){dismissBtn.addEventListener('click',function(){var overlay=document.querySelector('.mabox-wechat-guide');if(overlay){overlay.remove();}document.body.style.overflow='';});}";
            $js .= "});";
            if ($mode === 'redirect') {
                $js .= "if(document.querySelector('.mabox-wechat-guide')){document.body.style.overflow='hidden';}";
            }
            wp_register_script('mabox-wechat-guide-script', false, array(), NPCINK_SITE_TOOLBOX_VERSION, true);
            wp_add_inline_script('mabox-wechat-guide-script', $js);
            wp_enqueue_script('mabox-wechat-guide-script');
        }
        private static function is_wechat_qq() {
            $ua = isset($_SERVER['HTTP_USER_AGENT']) && is_string($_SERVER['HTTP_USER_AGENT'])
                ? sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT']))
                : '';
            // MicroMessenger 是微信内置浏览器；移动端 QQ Webview 的 UA 含 "QQ/"。
            // 桌面 QQ 浏览器等仅含 "QQ" 字样的 UA 不是引导目标，不能被全屏遮罩拦截
            return stripos($ua, 'MicroMessenger') !== false
                || (wp_is_mobile() && stripos($ua, 'QQ/') !== false);
        }
    }
}
