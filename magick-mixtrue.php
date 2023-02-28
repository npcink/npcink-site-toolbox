<?php
/*
Plugin Name: 魔法合剂插件
Plugin URI: https://www.npc.ink/
Description: 目前主要是统计功能
Version: 0.0.3
Author: Muze
Author URI: https://www.npc.ink/
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
define('MAGICK_MIXTURE_NAME', 'magick-mixtrue');
//定义插件版本
define('MAGICK_MIXTURE_VERSION', '0.0.3');

/**
 * 用于定义需要用到的插件类，
 */
require plugin_dir_path(__FILE__) . 'includes/class-magick-mixtrue.php';

//require plugin_dir_path(__FILE__) . 'index.php';

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

//echo '<h1>当前文章评论已打开</h1>';
//$magick_tool->run_page_hook();

require plugin_dir_path(__FILE__) . 'includes/carbon-fields/carbon-fields-plugin.php';

use Carbon_Fields\Container;
use Carbon_Fields\Field;

add_action('carbon_fields_register_fields', 'crb_attach_post_options');
function crb_attach_post_options()
{
    Container::make('post_meta', __('Section Options'))
        ->where('post_type', '=', 'page')
        ->where('post_template', '=', 'template-section-based.php')
        ->add_fields(array(
            Field::make('complex', 'crb_sections', 'Sections')
            // Our first group will be a simple rich text field
                ->add_fields('text', 'Text', array(
                    Field::make('rich_text', 'text', 'Text'),
                ))

            // Second group will be a list of files for users to download
                ->add_fields('file_list', 'File List', array(
                    Field::make('complex', 'files', 'Files')
                        ->add_fields(array(
                            Field::make('file', 'file', 'File'),
                        )),
                ))

            // Third group will be a list of manually selected posts
            // used as a simple curated "Related posts" listing
                ->add_fields('related_posts', 'Related Posts', array(
                    Field::make('association', 'posts', 'Posts')
                        ->set_types(array(
                            array(
                                'type' => 'post',
                                'post_type' => 'post',
                            ),
                        )),
                )),
        ));
}
