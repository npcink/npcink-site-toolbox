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

/**
 * 清理单个站点的插件数据。
 *
 * 多站点模式下对每个站点各执行一次；单站点直接执行。
 */
function npcink_site_toolbox_uninstall_cleanup()
{
    global $wpdb;

    wp_clear_scheduled_hook('npcink_site_toolbox_auto_db_clean');
    wp_clear_scheduled_hook('npcink_site_toolbox_wechat_ticket_refresh');

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
        'npcink_site_toolbox_search_log_write_lock',
        'npcink_site_toolbox_show_activation_notice',
        'npcink_site_toolbox_version',
        'widget_npcink_site_toolbox_site_stats',
        'widget_npcink_site_toolbox_recent_posts_thumb',
    );

    foreach ($npcink_site_toolbox_option_names as $npcink_site_toolbox_option_name) {
        delete_option($npcink_site_toolbox_option_name);
    }

    // pre-2.1/已退役功能遗留的 Option 行（键名经 git 历史考古确认，与升级清理保持一致）
    $npcink_site_toolbox_legacy_option_names = array(
        'magick_plugin_config',
        'mabox_ai_review_log',
        'mabox_feature_popularity',
        'mabox_feedback_stats',
        'mabox_login_log',
        'mabox_privacy_notice_dismissed',
        'mabox_search_log',
        'mabox_spam_comment_log',
        'mabox_telemetry_data',
        'mabox_telemetry_user_count',
        'mabox_wizard_completed',
    );
    foreach ($npcink_site_toolbox_legacy_option_names as $npcink_site_toolbox_legacy_option_name) {
        delete_option($npcink_site_toolbox_legacy_option_name);
    }

    delete_metadata('comment', 0, '_npcink_site_toolbox_block_reason', '', true);

    // 数据库清理的预览凭证按用户记录在 usermeta 中，卸载时一并清除。
    delete_metadata('user', 0, 'npcink_site_toolbox_consumed_db_previews', '', true);

    // 清理纯运行状态；WebP 恢复记录必须保留，否则已转换附件会失去恢复原 JPEG 的依据。
    delete_metadata('post', 0, '_npcink_site_toolbox_oss_offloaded', '', true);
    delete_metadata('post', 0, '_npcink_site_toolbox_webp_lock', '', true);

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
}

if (is_multisite()) {
    $npcink_site_toolbox_site_ids = get_sites(array('fields' => 'ids', 'number' => 0));
    foreach ($npcink_site_toolbox_site_ids as $npcink_site_toolbox_site_id) {
        switch_to_blog( (int) $npcink_site_toolbox_site_id);
        npcink_site_toolbox_uninstall_cleanup();
        restore_current_blog();
    }
} else {
    npcink_site_toolbox_uninstall_cleanup();
}
