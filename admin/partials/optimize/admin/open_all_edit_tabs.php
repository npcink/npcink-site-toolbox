<?php

defined('ABSPATH') || exit;

/**
 * 效果：在全部文章类型的列表页添加按钮，把当前筛选结果的编辑页或查看页在新标签页打开
 */
if (!class_exists('Npcink_Toolbox_Admin_Open_All_Edit_Tabs')) {
    class Npcink_Toolbox_Admin_Open_All_Edit_Tabs implements Npcink_Toolbox_Module_Interface
    {
        private static $config = array();

        //加载
        public static function run($config = array())
        {
            self::$config = is_array($config) ? $config : array();
            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_script'));
        }

        public static function enqueue_script($hook_suffix)
        {
            // edit.php 是全部文章类型（文章、页面、自定义类型）列表页的统一后缀；
            // 行内链接仅对当前用户有权限的内容渲染，无需再做事后权限判断
            if ('edit.php' !== $hook_suffix) {
                return;
            }

            $screen = function_exists('get_current_screen') ? get_current_screen() : null;
            if (!$screen || 'edit' !== $screen->base || empty($screen->post_type)) {
                return;
            }

            $open_edit = !empty(self::$config['open_all_edit_tabs']);
            $open_view = !empty(self::$config['open_all_view_tabs']);

            if (!$open_edit && !$open_view) {
                return;
            }

            wp_enqueue_script(
                'npcink-site-toolbox-open-all-edit-tabs',
                plugins_url('open_all_edit_tabs.js', __FILE__),
                array(),
                NPCINK_SITE_TOOLBOX_VERSION,
                true
            );

            wp_localize_script('npcink-site-toolbox-open-all-edit-tabs', 'npcinkSiteToolboxOpenAllEditTabs', array(
                'actions'          => array(
                    'edit' => $open_edit,
                    'view' => $open_view,
                ),
                'editButtonLabel'  => __('在新标签页打开全部编辑', 'npcink-site-toolbox'),
                'viewButtonLabel'  => __('在新标签页打开全部查看', 'npcink-site-toolbox'),
                'editEmptyMessage' => __('当前列表没有可编辑的内容。', 'npcink-site-toolbox'),
                'viewEmptyMessage' => __('当前列表没有可查看的内容。', 'npcink-site-toolbox'),
                /* translators: %d: Number of tabs blocked by the browser's pop-up blocker. */
                'blockedTemplate'  => __('有 %d 个标签页被浏览器拦截，请在允许本站弹出式窗口后重试。', 'npcink-site-toolbox'),
            ));
        }
    }
}
