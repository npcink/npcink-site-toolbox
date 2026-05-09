<?php

/**
 * 在卸载插件时激发。
 */

// 如果未从WordPress调用卸载，请退出。
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

global $wpdb;

// 删除自定义字段
delete_post_meta_by_key('mabox_trends_special');

// 删除插件选项（旧版单键 + 2.1.0 拆分后的模块键）
delete_option('Magick_ToolBox_Option');
delete_option('Magick_ToolBox_Option_Optimize');
delete_option('Magick_ToolBox_Option_Page');
delete_option('Magick_ToolBox_Option_Function');
delete_option('Magick_ToolBox_Option_H5');
delete_option('Magick_ToolBox_Option_Login');
delete_option('Magick_ToolBox_Option_Shortcode');
delete_option('Magick_ToolBox_Option_Template');
delete_option('Magick_ToolBox_Option_Backup_v210');
delete_option('Magick_ToolBox_Config_Version');

// 删除模块路由表选项
delete_option('Magick_ToolBox_Active_Modules');

// 删除 SEO 相关选项（可能由 seo_category_add_meat.php 创建）
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'cat-title-%'));
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", 'cat-words-%'));

// 删除缩略图切换器相关 transient
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_ts_%'));
$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_timeout_ts_%'));

// 删除字数统计缓存
delete_transient('mabox_total_chars');

// 删除链接统计自定义表（如果存在）
$table_name = $wpdb->prefix . 'link_counter';
$wpdb->query("DROP TABLE IF EXISTS {$table_name}");
