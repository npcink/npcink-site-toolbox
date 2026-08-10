<?php

defined('ABSPATH') || exit;

/**
 * 未登录隐藏指定分类下的文章
 */

if (!class_exists('Npcink_Toolbox_Page_Hide_Category')) {
    class Npcink_Toolbox_Page_Hide_Category implements Npcink_Toolbox_Module_Interface
    {
        private static $id_array; //分类数组
        private static $tip_content; //提示信息
        public static function run($config = array())
        {
            self::$id_array = Npcink_Toolbox_Admin::get_config($config, 'category_id', array());
            self::$tip_content = Npcink_Toolbox_Admin::get_config($config, 'tip_content', '');
            add_action('the_content', array(__CLASS__, 'restrict_content_for_specific_categories'));
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_restricted_category_style'));
        }

        public static function restrict_content_for_specific_categories($content)
        {
            $restricted_category_ids = self::$id_array;

            if (in_category($restricted_category_ids)) {
                if (!Npcink_Toolbox_Helpers::is_logged_in()) {
                    $content = wp_kses_post(self::$tip_content);
                }
            }
            return $content;
        }

        public static function enqueue_restricted_category_style()
        {
            if (Npcink_Toolbox_Helpers::is_logged_in()) {
                return;
            }

            if (in_category(self::$id_array)) {
                wp_register_style('npcink-site-toolbox-restricted-category', false, array(), NPCINK_SITE_TOOLBOX_VERSION);
                wp_enqueue_style('npcink-site-toolbox-restricted-category');
                wp_add_inline_style(
                    'npcink-site-toolbox-restricted-category',
                    '.b2-down-box, .down-box, .post-download, .download-box, .m-box.down { display: none !important; }'
                );
            }
        }
    }
}
