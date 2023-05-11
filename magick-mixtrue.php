<?php
/*
Plugin Name: 魔法合剂插件
Plugin URI: https://www.npc.ink/
Description: 文章统计+小功能
Version: 0.1.1
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
define('MAGICK_MIXTURE_VERSION', '0.0.2');

/**
 * 用于定义需要用到的插件类，
 */
require plugin_dir_path(__FILE__) . 'includes/class-magick-mixtrue.php';

//测试类
require plugin_dir_path(__FILE__) . 'index.php';

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

//$magick_tool = new Magick_Mixtrue_Tool;

//echo '<h1>当前文章评论已打开</h1>';
//$magick_tool->run_page_hook();
/**
 * 为WordPress后台的文章、分类等显示ID
 */
// 添加一个新的列 ID

// 定义 REST API 端点（Endpoint）
add_action('rest_api_init', 'mytheme_register_rest_endpoints');

function mytheme_register_rest_endpoints()
{
    // Get theme options
    register_rest_route('carbon-fields/v1', 'h5-options', array(
        'methods' => 'GET',
        'callback' => 'mytheme_get_theme_options',
        // 权限控制
        // 'permission_callback' => function () {
        //     return current_user_can('manage_options');
        // },
    ));
}

//返回选项值
function mytheme_get_theme_options($request)
{
    $fields = [
        'comm_h5_index_tone',
        'comm_h5_index_tone_cat',
        'comm_h5_index_category',
        'comm_h5_single_contact_title',
        'comm_h5_single_contact_one_title',
        'comm_h5_single_contact_one_content',
        'comm_h5_single_contact_two_title',
        'comm_h5_single_contact_two_content',
        'comm_h5_singel_featured_link',
        'comm_h5_singel_featured_logo',
        'comm_h5_singel_featured_msg',
    ];

    $options = [];
    foreach ($fields as $field) {
        $options[$field] = carbon_get_theme_option($field);
        //处理数组
        if ($field == "comm_h5_index_tone") {
            //创建数组存储数据
            $arr = [];
            $sum = carbon_get_theme_option($field);
            $arr = array_map(function ($obj) {
                return $obj['id'];
            }, $sum);
            //转成数组
            $intArray = array_map('intval', $arr);
            $options[$field] = $intArray;
        }
    }
    return $options;
}

//查询文章接口
//http://magick.plugin/wp-json/carbon-fields/v1/posts/2278
add_action('rest_api_init', function () {
    register_rest_route('carbon-fields/v1', 'posts/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => 'mytheme_get_post_infos',
        //'permission_callback' => '__return_true',
    ));
});

//撰写查询用接口
function mytheme_get_post_infos($request)
{
    $post_id = $request->get_param('id');
    $post = get_post($post_id);

    $post_title = get_the_title($post_id);
    $post_excerpt = $post->post_excerpt; // 获取文章描述
    $post_date = get_the_date('Y-m-d H:i:s', $post_id);

    $post_categories = get_the_category($post_id);
    $cat_array = array();
    foreach ($post_categories as $cat) {
        $cat_array[] = array(
            'id' => $cat->cat_ID,
            'name' => $cat->name,
        );
    }
    $categories = $cat_array;

    $featured_image = array();
    if (has_post_thumbnail($post_id)) {
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $featured_image['url'] = wp_get_attachment_url($thumbnail_id);
        $featured_image['caption'] = get_post($thumbnail_id)->post_excerpt;
        $featured_image['details'] = wp_get_attachment_metadata($thumbnail_id);
    }
    $post_content = apply_filters('the_content', $post->post_content); // 获取文章正文内容
    $response = array(
        'id' => $post_id,
        'date' => $post_date,
        'title' => $post_title,
        'excerpt' => $post_excerpt,
        'image' => $featured_image,
        'cat' => $categories,
        'content' => $post_content,
    );

    return $response;
}

//撰写首页用接口
//根据设置，输出首页要展示用的数据
add_action('rest_api_init', function () {
    register_rest_route('carbon-fields/v1', 'posts', array(
        'methods' => 'GET',
        'callback' => 'mytheme_get_posts',
    ));
});

function mytheme_get_posts($request)
{
    $args = array(
        'posts_per_page' => 11, // 获取最新的11篇文章
        'post_status' => 'publish', // 只获取已发布的文章
    );

    $query = new WP_Query($args);

    if ($query->have_posts()) {
        $result = array();

        while ($query->have_posts()) {
            $query->the_post();
            $post_id = get_the_ID();

            $post_title = get_the_title();
            $post_excerpt = get_the_excerpt(); // 获取文章摘要
            $post_date = get_the_date('Y-m-d H:i:s');

            $post_categories = get_the_category();
            $cat_array = array();
            foreach ($post_categories as $cat) {
                $cat_array[] = array(
                    'id' => $cat->cat_ID,
                    'name' => $cat->name,
                );
            }
            $categories = $cat_array;

            $featured_image = array();
            if (has_post_thumbnail()) {
                $thumbnail_id = get_post_thumbnail_id($post_id);
                $featured_image['url'] = wp_get_attachment_url($thumbnail_id);
                $featured_image['caption'] = get_post($thumbnail_id)->post_excerpt;
                $featured_image['details'] = wp_get_attachment_metadata($thumbnail_id);
            }
            $post_content = apply_filters('the_content', get_the_content()); // 获取文章正文内容
            $response = array(
                'id' => $post_id,
                'date' => $post_date,
                'title' => $post_title,
                'excerpt' => $post_excerpt,
                'image' => $featured_image,
                'cat' => $categories,
                'content' => $post_content,
            );

            $result[] = $response;
        }

        wp_reset_postdata();

        return $result; // 返回文章数据
    }

    return new WP_Error('no_posts', 'No posts found', array('status' => 404)); // 若无文章，返回404错误
}

add_action('rest_api_init', function () {
    register_rest_route('mytheme/v1', 'postssli', array(
        'methods' => 'GET',
        'callback' => 'mytheme_get_posts_data',
    ));
});

function mytheme_get_posts_data()
{
    $post_ids = carbon_get_theme_option('comm_h5_index_tone'); // 获取主题选项 'comm_h5_index_tone' 的值，即多个文章 ID
    if (!$post_ids) {
        return new WP_Error('no_post', 'No post found', array('status' => 404));
    }
    $posts_data = array();
    foreach ($post_ids as $post_id) {
        $post = get_post($post_id);
        if (!$post) {
            continue;
        }
        $post_title = get_the_title($post_id);
        $post_excerpt = get_the_excerpt($post_id);
        $featured_image = array();
        if (has_post_thumbnail($post_id)) {
            $thumbnail_id = get_post_thumbnail_id($post_id);
            $featured_image['url'] = wp_get_attachment_url($thumbnail_id);
            $featured_image['caption'] = get_post($thumbnail_id)->post_excerpt;
            $featured_image['details'] = wp_get_attachment_metadata($thumbnail_id);
        }
        $post_categories = get_the_category($post_id);
        $cat_array = array();
        foreach ($post_categories as $cat) {
            $cat_array[] = array(
                'id' => $cat->cat_ID,
                'name' => $cat->name,
            );
        }
        $categories = $cat_array;
        $post_content = apply_filters('the_content', get_post_field('post_content', $post_id));

        $response = array(
            'id' => $post_id,
            'title' => $post_title,
            'excerpt' => $post_excerpt,
            'image' => $featured_image,
            'cat' => $categories,
            'content' => $post_content,
        );
        $posts_data[] = $response;
    }
    return $posts_data; // 返回文章数据
}

//加载js文件
add_action('wp_enqueue_scripts', 'npcink_plugin_ad_scripts');
function npcink_plugin_ad_scripts()
{
    wp_enqueue_script('npcink-name', plugin_dir_url(__FILE__) . '/main.js', array('jquery'), '1.0.0', true);
    wp_localize_script('npcink-name', 'ajax_object', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
    ));
}
