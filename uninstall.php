<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/**
 * 在卸载插件时激发。
 */

// 如果未从WordPress调用卸载，请退出。
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

wp_clear_scheduled_hook('npcink_site_toolbox_auto_db_clean');

global $wpdb;

// 固定 Option 使用核心 API 删除，确保对象缓存同步失效。
$npcink_site_toolbox_option_names = array(
	'npcink_site_toolbox_optimize',
	'npcink_site_toolbox_page',
	'npcink_site_toolbox_function',
	'npcink_site_toolbox_domestic',
	'npcink_site_toolbox_performance',
	'npcink_site_toolbox_active_modules',
	'npcink_site_toolbox_census',
	'npcink_site_toolbox_audit_log',
	'npcink_site_toolbox_search_log',
	'npcink_site_toolbox_spam_comment_log',
	'npcink_site_toolbox_privacy_notice_dismissed',
	'widget_npcink_site_toolbox_site_stats',
	'widget_npcink_site_toolbox_recent_posts_thumb',
);

foreach ($npcink_site_toolbox_option_names as $npcink_site_toolbox_option_name) {
	delete_option($npcink_site_toolbox_option_name);
}

delete_metadata('comment', 0, '_npcink_site_toolbox_block_reason', '', true);

// 分类 SEO 字段按分类 ID 动态生成，只能按插件专属前缀清理。
foreach (array('npcink_site_toolbox_category_title_', 'npcink_site_toolbox_category_keywords_') as $npcink_site_toolbox_prefix) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time uninstall cleanup; dynamic option names cannot be enumerated through the Options API.
	$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like($npcink_site_toolbox_prefix) . '%'));
}

// 限流、登录保护、微信票据和环境检测均使用同一插件专属 Transient 前缀。
foreach (array('_transient_npcink_site_toolbox_', '_transient_timeout_npcink_site_toolbox_') as $npcink_site_toolbox_prefix) {
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- One-time uninstall cleanup; dynamic transient names cannot be enumerated through the Transients API.
	$wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s", $wpdb->esc_like($npcink_site_toolbox_prefix) . '%'));
}
