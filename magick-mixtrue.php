<?php
/*
Plugin Name: 魔法合剂插件
Plugin URI: https://dongbd.com/
Description: 添加一些有趣的功能
Version: 0.1.1
Author: Muze
Author URI: https://www.npc.ink/276641.html
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
define('MAGICK_MIXTURE_VERSION', '1.1.2');

/**
 * 用于定义国际化的核心插件类，
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

$magick_tool = new Magick_Mixtrue_Tool;

$magick_tool->p($magick_tool->get_time_long('last_monthsdfasdf'));
