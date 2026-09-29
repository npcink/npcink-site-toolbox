<?php

defined('ABSPATH') || exit;

/**
 * 效果：在全部文章类型的列表页添加按钮，把当前筛选结果的所有编辑页在新标签页打开
 */
if (!class_exists('Npcink_Toolbox_Admin_Open_All_Edit_Tabs')) {
    class Npcink_Toolbox_Admin_Open_All_Edit_Tabs implements Npcink_Toolbox_Module_Interface
    {
        //加载
        public static function run($config = array())
        {
            add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_script'));
        }

        public static function enqueue_script($hook_suffix)
        {
            // edit.php 是全部文章类型（文章、页面、自定义类型）列表页的统一后缀；
            // 行内「编辑」链接仅对有对应权限的内容渲染，无需再做事后权限判断
            if ('edit.php' !== $hook_suffix) {
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
                'buttonLabel'  => __('在新标签页打开全部编辑', 'npcink-site-toolbox'),
                'emptyMessage' => __('当前列表没有可编辑的内容。', 'npcink-site-toolbox'),
                'blockedTemplate' => __('有 %d 个标签页被浏览器拦截，请在允许本站弹出式窗口后重试。', 'npcink-site-toolbox'),
            ));
        }
    }
}
