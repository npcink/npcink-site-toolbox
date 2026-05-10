<?php
/**
 * 顶部广告位
 * 在页面顶部显示广告内容
 */
if (!class_exists('MaBox_Page_Top_Ad')) {
    class MaBox_Page_Top_Ad
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_action('wp_body_open', array(__CLASS__, 'display_ad'));
        }

        public static function display_ad()
        {
            $ad_content = MaBox_Admin::get_config(self::$option, 'top_ad_content');
            if (empty($ad_content)) {
                return;
            }

            $position = MaBox_Admin::get_config(self::$option, 'top_ad_position', 'before_header');

            $allowed_html = array(
                'div' => array('class' => array(), 'id' => array(), 'style' => array()),
                'a' => array('href' => array(), 'target' => array(), 'rel' => array(), 'class' => array()),
                'img' => array('src' => array(), 'alt' => array(), 'class' => array(), 'style' => array()),
                'script' => array('src' => array(), 'type' => array(), 'async' => array(), 'defer' => array()),
                'ins' => array('class' => array(), 'style' => array(), 'data-ad-slot' => array(), 'data-ad-client' => array(), 'data-ad-format' => array()),
                'p' => array('class' => array(), 'style' => array()),
                'span' => array('class' => array(), 'style' => array()),
                'h1' => array('class' => array()),
                'h2' => array('class' => array()),
                'h3' => array('class' => array()),
            );

            $safe_content = wp_kses($ad_content, $allowed_html);

            if ($position === 'before_header') {
                echo '<div class="mabox-top-ad">' . $safe_content . '</div>';
            } elseif ($position === 'after_header') {
                add_action('wp_footer', array(__CLASS__, 'display_ad_after_header'), 999);
            } elseif ($position === 'before_content') {
                add_filter('the_content', array(__CLASS__, 'inject_before_content'), 1);
            }
        }

        public static function display_ad_after_header()
        {
            $ad_content = MaBox_Admin::get_config(self::$option, 'top_ad_content');
            if (empty($ad_content)) {
                return;
            }
            $allowed_html = array(
                'div' => array('class' => array(), 'id' => array(), 'style' => array()),
                'a' => array('href' => array(), 'target' => array(), 'rel' => array(), 'class' => array()),
                'img' => array('src' => array(), 'alt' => array(), 'class' => array(), 'style' => array()),
                'script' => array('src' => array(), 'type' => array(), 'async' => array(), 'defer' => array()),
                'ins' => array('class' => array(), 'style' => array(), 'data-ad-slot' => array(), 'data-ad-client' => array(), 'data-ad-format' => array()),
            );
            $safe_content = wp_kses($ad_content, $allowed_html);
            echo '<div class="mabox-top-ad" style="margin-top: 2em;">' . $safe_content . '</div>';
        }

        public static function inject_before_content($content)
        {
            $ad_content = MaBox_Admin::get_config(self::$option, 'top_ad_content');
            if (empty($ad_content)) {
                return $content;
            }
            $allowed_html = array(
                'div' => array('class' => array(), 'id' => array(), 'style' => array()),
                'a' => array('href' => array(), 'target' => array(), 'rel' => array(), 'class' => array()),
                'img' => array('src' => array(), 'alt' => array(), 'class' => array(), 'style' => array()),
                'script' => array('src' => array(), 'type' => array(), 'async' => array(), 'defer' => array()),
                'ins' => array('class' => array(), 'style' => array(), 'data-ad-slot' => array(), 'data-ad-client' => array(), 'data-ad-format' => array()),
            );
            $safe_content = wp_kses($ad_content, $allowed_html);
            return '<div class="mabox-top-ad">' . $safe_content . '</div>' . $content;
        }
    }
}
