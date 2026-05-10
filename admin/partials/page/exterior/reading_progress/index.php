<?php

if (!class_exists('MaBox_Page_Reading_Progress')) {
    class MaBox_Page_Reading_Progress
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_assets'));
            add_action('wp_footer', array(__CLASS__, 'render_bar'));
        }

        public static function load_assets()
        {
            if (is_admin()) {
                return;
            }

            $dir = plugin_dir_url(__DIR__) . 'reading_progress/';
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_reading_progress_css',
                $dir . 'style.css',
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_reading_progress_js',
                $dir . 'script.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        public static function render_bar()
        {
            if (!is_single()) {
                return;
            }

            $color = MaBox_Admin::get_config(self::$option, 'reading_progress_color', '#1677ff');
            $height = MaBox_Admin::get_config(self::$option, 'reading_progress_height', 3);

            if (empty($color)) {
                $color = '#1677ff';
            }
            if (empty($height) || !is_numeric($height)) {
                $height = 3;
            }
            ?>
            <div id="mabox-reading-progress" style="background: <?php echo esc_attr($color); ?>; height: <?php echo esc_attr($height); ?>px;"></div>
            <?php
        }
    }
}
