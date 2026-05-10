<?php

if (!class_exists('MaBox_Page_Ban_Open_WeiXing')) {
    class MaBox_Page_Ban_Open_WeiXing
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            require_once('WxqqJump/WxqqJump.php');
            add_action('wp_footer', array(__CLASS__, 'render'), 999);
            add_action('wp_head', array(__CLASS__, 'wechat_optimize'));
        }

        public static function render()
        {
            $mode = MaBox_Admin::get_config(self::$option, 'ban_open_weixing_mode', 'alert');
            if ($mode === 'alert') {
                echo '<script>(function(){var ua=navigator.userAgent.toLowerCase();if(ua.match(/MicroMessenger/i)=="micromessenger"){alert("请在浏览器中打开此页面")}})()</script>' . "\n";
            } elseif ($mode === 'optimize') {
                echo '<script>(function(){var ua=navigator.userAgent.toLowerCase();if(ua.match(/MicroMessenger/i)=="micromessenger"){document.body.classList.add("mabox-wechat-mode")}})()</script>' . "\n";
            }
        }

        public static function wechat_optimize()
        {
            $mode = MaBox_Admin::get_config(self::$option, 'ban_open_weixing_mode', 'alert');
            if ($mode !== 'optimize') {
                return;
            }

            $guide_text = MaBox_Admin::get_config(self::$option, 'wechat_guide_text', '点击右上角 ··· 在浏览器中打开');
            $xcx_guide = MaBox_Admin::get_config(self::$option, 'wechat_xcx_guide', false);
            $xcx_guide_text = MaBox_Admin::get_config(self::$option, 'wechat_xcx_guide_text', '在小程序中打开');
            $xcx_link = MaBox_Admin::get_config(self::$option, 'wechat_xcx_link', '');
            ?>
            <style>
            .mabox-wechat-mode .mabox-wechat-guide {
                display: block !important;
            }
            .mabox-wechat-mode #wpadminbar {
                display: none !important;
            }
            .mabox-wechat-guide {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                background: #f8f8f8;
                padding: 10px 16px;
                text-align: center;
                font-size: 13px;
                color: #666;
                z-index: 99999;
                border-bottom: 1px solid #eee;
            }
            .mabox-wechat-guide .mabox-xcx-btn {
                display: inline-block;
                margin-top: 6px;
                padding: 6px 16px;
                background: #07C160;
                color: #fff;
                border-radius: 16px;
                font-size: 13px;
                text-decoration: none;
            }
            </style>
            <div class="mabox-wechat-guide">
                <div><?php echo esc_html($guide_text); ?></div>
                <?php if ($xcx_guide && !empty($xcx_link)): ?>
                <a href="<?php echo esc_url($xcx_link); ?>" class="mabox-xcx-btn"><?php echo esc_html($xcx_guide_text); ?></a>
                <?php endif; ?>
            </div>
            <?php
        }
    }
}
