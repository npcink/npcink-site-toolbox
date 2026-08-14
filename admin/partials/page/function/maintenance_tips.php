<?php

defined('ABSPATH') || exit;

/**
 * 效果：维护提示
 * 来源：
 */

if (!class_exists('Npcink_Toolbox_Maintenance_Tips')) {
    class Npcink_Toolbox_Maintenance_Tips implements Npcink_Toolbox_Module_Interface
    {

        private static $configs; //配置
        private static $blogname; //博客名
        private static $blogdescription; //博客描述
        private static $url; //网址
        private static $path; //路径
        /**
         * 传来的页面类型
         */
        public static function run($config = array())
        {
            self::$configs = Npcink_Toolbox_Admin::get_config($config, 'maintenance_tips', 'false'); //展示类型
            self::$blogname =  get_bloginfo('name');
            self::$blogdescription = get_bloginfo('description');
            self::$url = plugin_dir_url((__FILE__)) . 'maintenance/';
            self::$path = plugin_dir_path((__FILE__)) . 'maintenance/';
            //检查
            add_action('template_redirect', array(__CLASS__, 'check_administrator_permission'));
        }
        public static  function check_administrator_permission()
        {
            //不是管理员
            // if (!current_user_can('edit_themes') || !is_user_logged_in()) {
            if (!current_user_can('manage_options')) {
                self::add_responsive_css();

                switch (self::$configs) {
                    case "default":
                        wp_die(
                            esc_html(
                                sprintf(
                                    /* translators: %s: Site name. */
                                    __('%s 正在升级维护，请稍后再来。', 'npcink-site-toolbox'),
                                    self::$blogname
                                )
                            )
                        );
                        break;
                    case "default_img":
                        include(self::$path . 'default/index.php');
                        exit;
                        break;
                    case "red":
                        include(self::$path . 'red.php');
                        exit;
                        break;
                    default:
                        break;
                }
            }
        }

        public static function add_responsive_css()
        {
            wp_enqueue_style(
                'mabox-maintenance-responsive',
                self::$url . 'responsive.css',
                array(),
                NPCINK_SITE_TOOLBOX_VERSION
            );
        }

    }
}
