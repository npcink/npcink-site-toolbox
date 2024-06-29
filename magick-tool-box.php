<?php
/*
 * Plugin Name: WP Magick Toolbox【BETA】
 * Description: 魔法工具箱，诸多实用且有趣的功能合集，简单易用；详情请见插件中的「关于」页内容
 * Plugin URI: https://www.npc.ink/277510.html
 * Version: 2.0.7
 * Author: Npcink
 * Author URI: https://www.npc.ink/
 * Requires at least: 4.6
 * Requires PHP:      7.0
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
define('MAGICK_MIXTURE_VERSION', '2.0.7');
//定义保存选项字段
define('MAGICK_MIXTURE_OPTION', "Magick_ToolBox_Option_207");

/**
 * 用于定义需要用到的插件类，
 */
require plugin_dir_path(__FILE__) . 'includes/class-magick-mixtrue.php';




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

    $plugin = new Magick_Mixtrue();
    $plugin->run();
}
run_magick_mixture();



//设置按钮
add_filter('plugin_action_links_' . plugin_basename(__FILE__), function ($links) {
    $links[] = '<a href="' . get_admin_url(null, 'plugins.php?page=MaBox_config') . '">' . __('设置', 'n') . '</a>';
    return $links;
});



//测试类 - 开发用，正式用记得注释掉
//require plugin_dir_path(__FILE__) . 'index.php';

