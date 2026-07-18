<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/*
 * Plugin Name: Npcink Site Toolbox
 * Description: An opt-in toolbox for site settings, media, SEO, security, integrations, diagnostics, and maintenance.
 * Plugin URI: https://www.npc.ink/277510.html
 * Version: 3.2.0
 * Author: Npcink
 * Author URI: https://www.npc.ink/
 * Requires at least: 6.3
 * Requires PHP:      7.4
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       npcink-site-toolbox
 */
//调试内容，在后台顶部显示一个通知
// 如果直接调用此文件，请中止。
if (!defined('WPINC')) {
    die;
}

/**
 * 当前插件版本。
 *从1.0.0版本开始，使用SemVer-https://semver.org
 *重命名此插件，并在发布新版本时进行更新。
 */
//定义插件名
define('NPCINK_SITE_TOOLBOX_NAME', 'npcink-site-toolbox');
//定义插件版本
define('NPCINK_SITE_TOOLBOX_VERSION', '3.2.0');
define('NPCINK_SITE_TOOLBOX_ACTIVE_MODULES', 'npcink_site_toolbox_active_modules');

/**
 * 配置拆分后的模块级 Option 键名
 * @since 2.1.0
 */
define('NPCINK_SITE_TOOLBOX_OPTION_OPTIMIZE', 'npcink_site_toolbox_optimize');
define('NPCINK_SITE_TOOLBOX_OPTION_PAGE', 'npcink_site_toolbox_page');
define('NPCINK_SITE_TOOLBOX_OPTION_FUNCTION', 'npcink_site_toolbox_function');


/**
 * 第三阶段：国内生态 & 性能优化模块 Option 键名
 * @since 2.2.0
 */
define('NPCINK_SITE_TOOLBOX_OPTION_DOMESTIC', 'npcink_site_toolbox_domestic');
define('NPCINK_SITE_TOOLBOX_OPTION_PERFORMANCE', 'npcink_site_toolbox_performance');

/**
 * 用于定义需要用到的插件类，
 */
require_once plugin_dir_path(__FILE__) . 'includes/autoload.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-npcink-site-toolbox.php';

// Cron 回调必须在所有请求上下文中注册；类本身仍由自动加载器按需加载。
add_filter('cron_schedules', array('Npcink_Toolbox_Performance_Db_Clean', 'add_cron_schedules'));
add_action('npcink_site_toolbox_auto_db_clean', array('Npcink_Toolbox_Performance_Db_Clean', 'run_scheduled_cleanup'));
add_action(
    'update_option_' . NPCINK_SITE_TOOLBOX_OPTION_PERFORMANCE,
    array('Npcink_Toolbox_Performance_Db_Clean', 'handle_performance_option_update'),
    10,
    2
);
add_action(
    'delete_option_' . NPCINK_SITE_TOOLBOX_OPTION_PERFORMANCE,
    array('Npcink_Toolbox_Performance_Db_Clean', 'clear_schedule'),
    10,
    0
);

// 生命周期 Hook 必须由主插件文件在顶层注册，模块按需加载时注册会错过事件。
register_activation_hook(__FILE__, array('Npcink_Toolbox_Category_Link_Simplify', 'activate'));
register_deactivation_hook(__FILE__, array('Npcink_Toolbox_Category_Link_Simplify', 'deactivate'));
register_deactivation_hook(__FILE__, array('Npcink_Toolbox_Performance_Db_Clean', 'clear_schedule'));
add_action(
    'update_option_' . NPCINK_SITE_TOOLBOX_OPTION_OPTIMIZE,
    array('Npcink_Toolbox_Category_Link_Simplify', 'handle_optimize_option_update'),
    10,
    2
);




// 插件仅通过 WordPress 钩子注册行为，不需要暴露额外的全局启动函数。
(new Npcink_Site_Toolbox())->run();

// 插件激活时初始化路由表
register_activation_hook(__FILE__, function() {
    update_option(NPCINK_SITE_TOOLBOX_ACTIVE_MODULES, array());
});



//设置按钮
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $links[] = '<a href="' . get_admin_url(null, 'plugins.php?page=npcink-site-toolbox') . '">' . __('设置', 'npcink-site-toolbox') . '</a>';
    return $links;
});
