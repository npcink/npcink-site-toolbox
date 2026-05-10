<?php
/**
 * 字体切换功能
 *
 * 在页面右下角添加字体切换按钮，支持切换多种字体。
 */
if (!class_exists('MaBox_Page_Font_Switch')) {
    class MaBox_Page_Font_Switch {

        private static $config;

        public static function run($config) {
            self::$config = $config;
            add_action('wp_footer', array(__CLASS__, 'render_font_switcher'));
        }

        public static function render_font_switcher() {
            $fonts = !empty(self::$config['fonts']) ? self::$config['fonts'] : 'Microsoft YaHei,Simsun,PingFang SC,Noto Sans SC';
            $font_list = array_filter(array_map('trim', explode(',', $fonts)));

            if (empty($font_list)) {
                return;
            }

            $position = !empty(self::$config['position']) ? self::$config['position'] : 'bottom-right';
            $position_css = self::get_position_css($position);

            echo '<style>';
            echo '.mabox-font-switcher{position:fixed;' . $position_css . 'z-index:99999;display:flex;flex-direction:column;gap:8px;}';
            echo '.mabox-font-switcher-btn{width:36px;height:36px;border-radius:50%;background:#fff;border:1px solid #ddd;cursor:pointer;font-size:14px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 8px rgba(0,0,0,0.1);transition:all 0.3s;}';
            echo '.mabox-font-switcher-btn:hover{background:#1677ff;color:#fff;border-color:#1677ff;}';
            echo '.mabox-font-switcher-panel{display:none;position:absolute;bottom:44px;right:0;background:#fff;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.15);padding:12px;min-width:150px;}';
            echo '.mabox-font-switcher-panel.show{display:block;}';
            echo '.mabox-font-switcher-panel button{display:block;width:100%;padding:8px 12px;border:none;background:none;cursor:pointer;text-align:left;border-radius:4px;font-size:14px;}';
            echo '.mabox-font-switcher-panel button:hover{background:#f0f0f0;}';
            echo '.mabox-font-switcher-panel button.active{background:#e6f4ff;color:#1677ff;font-weight:bold;}';
            echo '</style>';

            echo '<div class="mabox-font-switcher">';
            echo '<button class="mabox-font-switcher-btn" onclick="this.nextElementSibling.classList.toggle(\'show\')" title="切换字体">字</button>';
            echo '<div class="mabox-font-switcher-panel">';

            foreach ($font_list as $font) {
                $font_safe = esc_attr($font);
                echo '<button onclick="document.body.style.fontFamily=\'' . $font_safe . '\';document.querySelectorAll(\'*\').forEach(function(el){el.style.fontFamily=\'' . $font_safe . '\'});this.parentElement.querySelectorAll(\'button\').forEach(function(b){b.classList.remove(\'active\')});this.classList.add(\'active\');this.parentElement.classList.remove(\'show\');" style="font-family:' . $font_safe . '">' . $font . '</button>';
            }

            echo '</div></div>';

            echo '<script>document.addEventListener("click",function(e){var panel=document.querySelector(".mabox-font-switcher-panel");if(panel&&!panel.contains(e.target)&&!e.target.classList.contains("mabox-font-switcher-btn")){panel.classList.remove("show")}});</script>';
        }

        private static function get_position_css($position) {
            switch ($position) {
                case 'bottom-left':
                    return 'bottom:100px;left:20px;';
                case 'top-right':
                    return 'top:100px;right:20px;';
                case 'top-left':
                    return 'top:100px;left:20px;';
                default:
                    return 'bottom:100px;right:20px;';
            }
        }
    }
}
