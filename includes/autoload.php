<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/**
 * SPL 自动加载器
 *
 * 覆盖 includes/、admin/、public/ 目录下的核心类。
 *
 * @since 2.5.0
 */

spl_autoload_register(function ($class) {
    $plugin_dir = dirname(__DIR__);

    // 特殊类名映射（类名 => 相对文件路径）
    $map = array(
        'Npcink_Toolbox_Admin' => 'admin/class-npcink-toolbox-admin.php',
        'Npcink_Toolbox_Admin_Add_Time_Screen' => 'admin/partials/optimize/admin/add_time_screen.php',
        'Npcink_Toolbox_Admin_Single_Add_User_Screen' => 'admin/partials/optimize/admin/single_add_user_screen.php',
        'Npcink_Toolbox_Admin_Single_Show_ID' => 'admin/partials/optimize/admin/single_show_id.php',
        'Npcink_Toolbox_Admin_Single_Thumbnail_Switcher' => 'admin/partials/optimize/admin/thumbnail_switcher/index.php',
        'Npcink_Toolbox_Audit_Logger' => 'includes/class-npcink-toolbox-audit-logger.php',
        'Npcink_Toolbox_Baidu_Tonji' => 'admin/partials/function/auxiliary/baidu_tonji.php',
        'Npcink_Toolbox_Ban_Malice_Search' => 'admin/partials/function/auxiliary/ban_malice_search.php',
        'Npcink_Toolbox_Biying_Tonji' => 'admin/partials/function/auxiliary/biying_tonji.php',
        'Npcink_Toolbox_Block_Patterns' => 'includes/class-npcink-toolbox-block-patterns.php',
        'Npcink_Toolbox_Category_Link_Simplify' => 'admin/partials/optimize/site/category_link_simplify.php',
        'Npcink_Toolbox_Census_Single' => 'admin/partials/function/auxiliary/census-single.php',
        'Npcink_Toolbox_Comment_Ban_Pure_English' => 'admin/partials/page/comment/ban_pure_english.php',
        'Npcink_Toolbox_Comment_Limit_Word_Count' => 'admin/partials/page/comment/limit_word_count.php',
        'Npcink_Toolbox_Comment_Only_Once' => 'admin/partials/page/comment/only_comment_once.php',
        'Npcink_Toolbox_Comment_Sensitive_Words' => 'admin/partials/page/comment/sensitive_words.php',
        'Npcink_Toolbox_Search_Health' => 'includes/class-npcink-toolbox-search-health.php',
        'Npcink_Toolbox_Config_Manager' => 'includes/class-npcink-toolbox-config-manager.php',
        'Npcink_Toolbox_Config_Schema' => 'includes/class-npcink-toolbox-config-schema.php',
        'Npcink_Toolbox_Diagnostics' => 'includes/class-npcink-toolbox-diagnostics.php',
        'Npcink_Toolbox_Domestic_Comment_Security' => 'admin/partials/domestic/comment_security/index.php',
        'Npcink_Toolbox_Domestic_Compliance' => 'admin/partials/domestic/compliance/index.php',
        'Npcink_Toolbox_Domestic_Login_Security' => 'admin/partials/domestic/login_security/index.php',
        'Npcink_Toolbox_Domestic_Wechat' => 'admin/partials/domestic/wechat/index.php',
        'Npcink_Toolbox_Github_Project' => 'includes/class-npcink-toolbox-github-project.php',
        'Npcink_Toolbox_Google_Tonji' => 'admin/partials/function/auxiliary/google_tonji.php',
        'Npcink_Toolbox_Helpers' => 'includes/class-npcink-toolbox-helpers.php',
        'Npcink_Toolbox_Hide_Email_IP' => 'admin/partials/optimize/site/hide_email_ip.php',
        'Npcink_Toolbox_Hide_Top_Toolbar' => 'admin/partials/optimize/site/hide_top_toolbar.php',
        'Npcink_Toolbox_Image_Add_Tag' => 'admin/partials/optimize/medium/image_add_tag.php',
        'Npcink_Toolbox_Interface_Category_Data' => 'admin/partials/page/jurisdiction/interface_category_data.php',
        'Npcink_Toolbox_Maintenance_Tips' => 'admin/partials/page/function/maintenance_tips.php',
        'Npcink_Toolbox_My_Comments' => 'includes/class-npcink-toolbox-my-comments.php',
        'Npcink_Toolbox_No_Escape' => 'admin/partials/optimize/site/no_escape.php',
        'Npcink_Toolbox_Medium_Ban_Auto_Size' => 'admin/partials/optimize/medium/ban_auto_size.php',
        'Npcink_Toolbox_Medium_Image_Rename' => 'admin/partials/optimize/medium/image_rename.php',
        'Npcink_Toolbox_Medium_Svg_Support' => 'admin/partials/optimize/medium/svg_support.php',
        'Npcink_Toolbox_Module_Interface' => 'includes/interface-npcink-toolbox-module.php',
        'Npcink_Toolbox_Module_Loader' => 'admin/modules/loader.php',
        'Npcink_Toolbox_Module_Metadata' => 'admin/modules/metadata.php',
        'Npcink_Toolbox_Page_Comment_Interval' => 'admin/partials/page/comment/comment_interval.php',
        'Npcink_Toolbox_Page_Default_Thumbnail' => 'admin/partials/page/function/default_thumbnail.php',
        'Npcink_Toolbox_Page_Hide_Category' => 'admin/partials/page/jurisdiction/hide_category.php',
        'Npcink_Toolbox_Page_Hide_Page' => 'admin/partials/page/jurisdiction/hide_page.php',
        'Npcink_Toolbox_Page_Hide_Tag' => 'admin/partials/page/jurisdiction/hide_tag.php',
        'Npcink_Toolbox_Page_Login_Search' => 'admin/partials/page/function/login_search.php',
        'Npcink_Toolbox_Page_Reading_Progress' => 'admin/partials/page/exterior/reading_progress/index.php',
        'Npcink_Toolbox_Page_Search_Limit' => 'admin/partials/page/function/search_limit.php',
        'Npcink_Toolbox_Performance_Db_Clean' => 'admin/partials/performance/db_clean/index.php',
        'Npcink_Toolbox_Performance_Media_Health' => 'admin/partials/performance/media_health/index.php',
        'Npcink_Toolbox_Performance_Oss' => 'admin/partials/performance/oss/index.php',
        'Npcink_Toolbox_Performance_Search_Enhance' => 'admin/partials/performance/search_enhance/index.php',
        'Npcink_Toolbox_Performance_Seo_Checker' => 'admin/partials/performance/seo_checker/index.php',
        'Npcink_Toolbox_Privacy' => 'admin/partials/privacy/index.php',
        'Npcink_Toolbox_Public' => 'public/class-npcink-toolbox-public.php',
        'Npcink_Toolbox_Rate_Limiter' => 'includes/class-npcink-toolbox-rate-limiter.php',
        'Npcink_Toolbox_Rest_Route_Registry' => 'includes/class-npcink-toolbox-rest-route-registry.php',
        'Npcink_Toolbox_Remove_Sitemap_Users' => 'admin/partials/optimize/site/remove_sitemap_users.php',
        'Npcink_Toolbox_Remove_WP_Version' => 'admin/partials/optimize/site/remove_wp_version.php',
        'Npcink_Toolbox_Search_Link_Simplify' => 'admin/partials/optimize/site/search_link_simplify.php',
        'Npcink_Toolbox_Seo_Category' => 'admin/partials/function/seo/seo_category.php',
        'Npcink_Toolbox_Seo_Category_Add_Meat' => 'admin/partials/function/seo/seo_category_add_meat.php',
        'Npcink_Toolbox_Seo_Home' => 'admin/partials/function/seo/seo_home.php',
        'Npcink_Toolbox_Seo_Single' => 'admin/partials/function/seo/seo_single.php',
        'Npcink_Toolbox_Seo_Tag' => 'admin/partials/function/seo/seo_tag.php',
        'Npcink_Toolbox_Single_Add_Last_Updated_Date' => 'admin/partials/page/function/add_article_update_time.php',
        'Npcink_Toolbox_Single_First_Picture' => 'admin/partials/page/function/first_picture.php',
        'Npcink_Toolbox_Single_Keyword_Add_Link' => 'admin/partials/page/function/single_keyword_add_link.php',
        'Npcink_Toolbox_Site_Health' => 'includes/class-npcink-toolbox-site-health.php',
        'Npcink_Toolbox_Site_Stats' => 'includes/class-npcink-toolbox-site-stats.php',
        'Npcink_Toolbox_Tool' => 'includes/class-npcink-toolbox-tool.php',
        'Npcink_Toolbox_Unlisted_Vague_Img' => 'admin/partials/page/function/unlisted_vague_img.php',
        'Npcink_Toolbox_User_List_Show_Nickname' => 'admin/partials/optimize/site/user_list_show_nickname.php',
        'Npcink_Toolbox_Widgets' => 'admin/partials/optimize/widget/index.php',
        'Npcink_Site_Toolbox' => 'includes/class-npcink-site-toolbox.php',
    );

    if (isset($map[$class])) {
        $path = $plugin_dir . '/' . $map[$class];
        if (file_exists($path)) {
            require_once $path;
        }
        return;
    }

    // Npcink_Toolbox_* 前缀类的默认映射
    $prefix = 'Npcink_Toolbox_';
    if (strpos($class, $prefix) === 0) {
        $relative = substr($class, strlen($prefix));
        $file = 'class-npcink-toolbox-' . str_replace('_', '-', strtolower($relative)) . '.php';
        $path = $plugin_dir . '/includes/' . $file;
        if (file_exists($path)) {
            require_once $path;
        }
    }
});
