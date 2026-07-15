<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;
/**
 * 模块分层定义
 *
 * 将模块按风险/稳定性分为四个层级：
 * - core: 核心功能，稳定、安全、推荐使用
 * - advanced: 进阶功能，正常使用，需注意兼容性
 * - high_risk: 高风险功能，谨慎启用，可能影响站点正常运行
 * - experimental: 实验性功能，不稳定或仅适合特定场景
 *
 * @since 2.4.0
 */
return array(
    // ===== core: 核心功能 =====
    'core' => array(
        'optimize.hide_top_toolbar', 'optimize.no_escape', 'optimize.remove_wp_version',
        'optimize.category_link_simplify', 'optimize.search_link_simplify',
        'optimize.remove_sitemap_users', 'optimize.hide_email_ip',
        'optimize.image_add_tag', 'optimize.image_rename',
        'optimize.widgets',
        'optimize.admin_single_add_user_screen', 'optimize.admin_add_time_screen',
        'optimize.admin_single_show_id', 'optimize.admin_thumbnail_switcher',
        'optimize.user_list_show_nickname',
        'seo.seo_home', 'seo.seo_single', 'seo.seo_category', 'seo.seo_tag',
        'seo.seo_category_add_meat',
        'auxiliary.census_single', 'auxiliary.ban_malice_search',
        'login.login_verify',

        'domestic.compliance', 'domestic.wechat',
        'domestic.comment_security', 'domestic.login_security',
        'performance.oss', 'performance.seo_checker', 'performance.media_health',
        'performance.search_enhance',

        'function.config',
        'page.interface_category_data',

        'page.first_picture', 'page.add_article_update_time',
        'page.search_limit', 'page.default_thumbnail',
        'page.comment_interval', 'page.limit_word_count',
        'page.ban_pure_english', 'page.only_comment_once',
        'page.comment_sensitive_words',
        'page.reading_progress',
        'page.unlisted_vague_img',
        'page.login_search',

    ),

    // ===== advanced: 进阶功能 =====
    'advanced' => array(
        'page.maintenance_tips',
        'optimize.svg_support',
    ),

    // ===== high_risk: 高风险功能 =====
    'high_risk' => array(
        'optimize.ban_update', 'optimize.ban_auto_size',
        'optimize.cdn_replace',
        'page.batch_replace',
        'page.single_keyword_add_link',
        'performance.db_clean',

    ),

    // ===== experimental: 实验性功能 =====
    'experimental' => array(
        'page.hide_category', 'page.hide_tag', 'page.hide_page',
        'auxiliary.baidu_tonji', 'auxiliary.google_tonji', 'auxiliary.biying_tonji',
    ),
);
