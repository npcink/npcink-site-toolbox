<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/*
 * Plugin Name: WP Magick Toolbox
 * Description: 魔法工具箱，诸多实用且有趣的功能合集，简单易用；详情请见插件中的「关于」页内容
 * Plugin URI: https://www.npc.ink/277510.html
 * Version: 3.0.0
 * Author: Npcink
 * Author URI: https://www.npc.ink/
 * Requires at least: 6.0
 * Requires PHP:      7.4
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
define('MAGICK_MIXTURE_NAME', 'magick-optimize');
//定义插件版本
define('MAGICK_MIXTURE_VERSION', '3.0.0');
define('MAGICK_TOOLBOX_ACTIVE_MODULES', 'Magick_ToolBox_Active_Modules');

/**
 * 配置拆分后的模块级 Option 键名
 * @since 2.1.0
 */
define('MAGICK_MIXTURE_OPTION_OPTIMIZE', 'Magick_ToolBox_Option_Optimize');
define('MAGICK_MIXTURE_OPTION_PAGE', 'Magick_ToolBox_Option_Page');
define('MAGICK_MIXTURE_OPTION_FUNCTION', 'Magick_ToolBox_Option_Function');


/**
 * 第三阶段：国内生态 & 性能优化模块 Option 键名
 * @since 2.2.0
 */
define('MAGICK_MIXTURE_OPTION_DOMESTIC', 'Magick_ToolBox_Option_Domestic');
define('MAGICK_MIXTURE_OPTION_PERFORMANCE', 'Magick_ToolBox_Option_Performance');

/**
 * 用于定义需要用到的插件类，
 */
require_once plugin_dir_path(__FILE__) . 'includes/autoload.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-magick-mixture.php';

// 生命周期 Hook 必须由主插件文件在顶层注册，模块按需加载时注册会错过事件。
register_activation_hook(__FILE__, array('MaBox_Category_Link_Simplify', 'activate'));
register_deactivation_hook(__FILE__, array('MaBox_Category_Link_Simplify', 'deactivate'));
add_action(
    'update_option_' . MAGICK_MIXTURE_OPTION_OPTIMIZE,
    array('MaBox_Category_Link_Simplify', 'handle_optimize_option_update'),
    10,
    2
);




/**
 * 开始执行插件。
 *
 *由于插件内的所有内容都是通过钩子注册的，
 *然后从文件中的这一点启动插件
 *不影响页面生命周期。
 *
 */
function run_magick_mixture()
{
    $plugin = new Magick_Mixture();
    $plugin->run();
}
run_magick_mixture();

// 插件激活时初始化路由表
register_activation_hook(__FILE__, function() {
    update_option(MAGICK_TOOLBOX_ACTIVE_MODULES, array());
});



//设置按钮
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $links[] = '<a href="' . get_admin_url(null, 'plugins.php?page=MaBox_config') . '">' . __('设置', 'magick-toolbox') . '</a>';
    return $links;
});
