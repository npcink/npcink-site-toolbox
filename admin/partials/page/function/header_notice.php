<?php
/**
 * 页眉通知栏
 * 在页面顶部显示通知信息
 */
if (!class_exists('MaBox_Page_Header_Notice')) {
    class MaBox_Page_Header_Notice
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_action('wp_body_open', array(__CLASS__, 'display_notice'), 1);
        }

        public static function display_notice()
        {
            $text = MaBox_Admin::get_config(self::$option, 'header_notice_text');
            if (empty($text)) {
                return;
            }

            $color = MaBox_Admin::get_config(self::$option, 'header_notice_color', '#1677ff');
            $link = MaBox_Admin::get_config(self::$option, 'header_notice_link');
            $dismissible = MaBox_Admin::get_config(self::$option, 'header_notice_dismissible', true);

            $content = esc_html($text);
            if (!empty($link)) {
                $content = '<a href="' . esc_url($link) . '" target="_blank">' . $content . '</a>';
            }

            $dismiss_class = $dismissible ? ' dismissible' : '';
            $dismiss_btn = $dismissible ? '<button class="notice-close" onclick="this.parentElement.style.display=\'none\'">&times;</button>' : '';

            echo '<div class="mabox-header-notice' . $dismiss_class . '" style="background-color: ' . esc_attr($color) . '; color: #fff; text-align: center; padding: 10px; font-size: 14px;">';
            echo $content;
            echo $dismiss_btn;
            echo '</div>';
        }
    }
}
