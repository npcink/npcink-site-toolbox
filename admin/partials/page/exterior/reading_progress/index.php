<?php

defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Page_Reading_Progress')) {
    class Npcink_Toolbox_Page_Reading_Progress implements Npcink_Toolbox_Module_Interface
    {
        private static $option;

        public static function run($config = array())
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

            // 进度条只在单篇文章页渲染，样式与脚本也只在同场景入队，
            // 避免首页、归档、搜索页为用不上的功能多背两个请求
            if (!is_single()) {
                return;
            }

            $dir = plugin_dir_url(__DIR__) . 'reading_progress/';
            wp_enqueue_style(
                NPCINK_SITE_TOOLBOX_NAME . '_reading_progress_css',
                $dir . 'style.css',
                array(),
                NPCINK_SITE_TOOLBOX_VERSION,
                false
            );
            wp_enqueue_script(
                NPCINK_SITE_TOOLBOX_NAME . '_reading_progress_js',
                $dir . 'script.js',
                array(),
                NPCINK_SITE_TOOLBOX_VERSION,
                true
            );
        }

        public static function render_bar()
        {
            if (!is_single()) {
                return;
            }

            $color = Npcink_Toolbox_Admin::get_config(self::$option, 'reading_progress_color', '#1677ff');
            $height = Npcink_Toolbox_Admin::get_config(self::$option, 'reading_progress_height', 3);

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
