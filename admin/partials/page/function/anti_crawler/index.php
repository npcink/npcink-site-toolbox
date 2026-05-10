<?php

if (!class_exists('MaBox_Page_Anti_Crawler')) {
    class MaBox_Page_Anti_Crawler
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_action('template_redirect', array(__CLASS__, 'check_access'));
            add_action('wp_ajax_mabox_anti_crawler_verify', array(__CLASS__, 'ajax_verify_deprecated'));
            add_action('wp_ajax_nopriv_mabox_anti_crawler_verify', array(__CLASS__, 'ajax_verify_deprecated'));
            add_action('wp_footer', array(__CLASS__, 'render_challenge'));
        }

        public static function check_access()
        {
            if (is_admin() || MaBox_Helpers::is_logged_in()) {
                return;
            }

            $max_requests = MaBox_Admin::get_config(self::$option, 'anti_crawler_max_requests', 60);
            $time_window = MaBox_Admin::get_config(self::$option, 'anti_crawler_time_window', 60);

            if (empty($max_requests) || empty($time_window)) {
                return;
            }

            $ip = self::get_ip();
            $transient_key = 'mabox_anti_crawler_' . md5($ip);
            $data = get_transient($transient_key);

            if ($data === false) {
                $data = array('count' => 1, 'start' => time());
                set_transient($transient_key, $data, $time_window);
                return;
            }

            $data['count']++;
            set_transient($transient_key, $data, $time_window);

            if ($data['count'] > $max_requests) {
                if (isset($_COOKIE['mabox_anti_crawler_passed']) && $_COOKIE['mabox_anti_crawler_passed'] === '1') {
                    return;
                }
                add_action('wp', function () {
                    wp_die(self::challenge_page(), esc_html__('访问过于频繁'), array('response_code' => 429));
                });
            }
        }

        public static function ajax_verify_deprecated() {
            _deprecated_function('wp_ajax_mabox_anti_crawler_verify', '2.1.0', 'REST API POST /mabox/v1/public/anti-crawler/verify');
            self::ajax_verify();
        }

        public static function ajax_verify()
        {
            $ticket = sanitize_text_field($_POST['ticket']);
            $randstr = sanitize_text_field($_POST['randstr']);
            $app_id = MaBox_Admin::get_config(self::$option, 'anti_crawler_tecent_id', '');
            $app_key = MaBox_Admin::get_config(self::$option, 'anti_crawler_tecent_key', '');

            if (empty($app_id) || empty($app_key) || empty($ticket) || empty($randstr)) {
                wp_send_json_error(array('message' => '验证参数不完整'));
            }

            $user_ip = self::get_ip();
            $url = 'https://ssl.captcha.qq.com/ticket/verify';
            $params = array(
                'aid' => $app_id,
                'AppSecretKey' => $app_key,
                'Ticket' => $ticket,
                'Randstr' => $randstr,
                'UserIP' => $user_ip,
            );

            $response = wp_remote_post($url, array(
                'body' => $params,
                'timeout' => 10,
            ));

            if (is_wp_error($response)) {
                wp_send_json_error(array('message' => '验证服务异常'));
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['response']) && $body['response'] == 1) {
                setcookie('mabox_anti_crawler_passed', '1', time() + HOUR_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN, is_ssl(), true);
                wp_send_json_success(array('message' => '验证成功'));
            }

            wp_send_json_error(array('message' => '验证失败'));
        }

        public static function render_challenge()
        {
            if (is_admin() || MaBox_Helpers::is_logged_in()) {
                return;
            }

            $app_id = MaBox_Admin::get_config(self::$option, 'anti_crawler_tecent_id', '');
            if (empty($app_id)) {
                return;
            }
            ?>
            <script src="https://ssl.captcha.qq.com/TCaptcha.js"></script>
            <script>
            var maboxAntiCaptcha = new TencentCaptcha('<?php echo esc_js($app_id); ?>', function(res) {
                if (res.ret === 0) {
                    var xhr = new XMLHttpRequest();
                    xhr.open('POST', '<?php echo esc_url(admin_url('admin-ajax.php')); ?>', true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.onload = function() {
                        var data = JSON.parse(xhr.responseText);
                        if (data.success) {
                            location.reload();
                        }
                    };
                    xhr.send('action=mabox_anti_crawler_verify&ticket=' + encodeURIComponent(res.ticket) + '&randstr=' + encodeURIComponent(res.randstr));
                }
            });
            window.maboxAntiCaptcha = maboxAntiCaptcha;
            </script>
            <?php
        }

        public static function challenge_page()
        {
            ob_start();
            ?>
            <!DOCTYPE html>
            <html>
            <head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">
            <title>访问过于频繁</title>
            <style>
            body{font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,sans-serif;display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;background:#f5f5f5}
            .card{background:#fff;border-radius:12px;padding:40px;text-align:center;max-width:400px;width:90%;box-shadow:0 2px 12px rgba(0,0,0,0.1)}
            h1{font-size:20px;color:#333;margin:0 0 12px}
            p{color:#666;font-size:14px;margin:0 0 24px}
            button{padding:10px 32px;background:#1677ff;color:#fff;border:none;border-radius:6px;font-size:14px;cursor:pointer}
            button:hover{background:#4096ff}
            </style></head>
            <body>
            <div class="card">
                <h1>访问过于频繁</h1>
                <p>您的访问频率触发了安全机制，请完成验证后继续访问</p>
                <button onclick="maboxAntiCaptcha.show()">完成验证</button>
            </div>
            </body></html>
            <?php
            return ob_get_clean();
        }

        private static function get_ip()
        {
            return MaBox_Helpers::get_real_ip() ?: '0.0.0.0';
        }
    }
}
