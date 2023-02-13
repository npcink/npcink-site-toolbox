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
define('MAGICK_MIXTURE_VERSION', '1.0.0');

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

//创建两个菜单
//创建插件基础类
//检查有没有这个类
if (!class_exists('MAGICK_Mixtrue')) {
    //新建一个类
    class MAGICK_Mixtrue
    {
        //创建一个方法，在后台顶部显示一个通知
        public function magick_admin_notice_acfs()
        {
            ?>
    <div class = 'notice notice-error '>
    <p><?php _e('魔法合剂插件测试', 'sample-text-domain');
            ?></p>
    </div>
    <?php
}

//添加插件用菜单
        public function add_magick_menu()
        {

            add_menu_page(__('药水菜单'), __('药水菜单'), 'administrator', 'magick-mix-census-single', false, 'dashicons-visibility');
            add_submenu_page('magick-mix-census-single', __('发文统计'), __('发文统计'), 'administrator', 'magick-mix-census-single', 'magick-mix_census_single_content');
            add_submenu_page('magick-mix-census-single', __('销售统计'), __('销售统计'), 'administrator', 'magick-mix-census-shop', 'magick-mix_censcus_shop_content');
        }

    }

}

$magck_test = new MAGICK_Mixtrue();
//测试
add_action('admin_notices', function () use ($magck_test) {return $magck_test->magick_admin_notice_acfs();});

$magick_mixtrue_class = new MAGICK_Mixtrue();
//添加菜单
add_action('admin_menu', function () use ($magick_mixtrue_class) {return $magick_mixtrue_class->add_magick_menu();});
