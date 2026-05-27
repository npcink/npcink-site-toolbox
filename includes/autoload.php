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
        'MaBox_Admin' => 'admin/class-magick-mixture-admin.php',
        'MaBox_Admin_Add_Time_Screen' => 'admin/partials/optimize/admin/add_time_screen.php',
        'MaBox_Admin_Single_Add_User_Screen' => 'admin/partials/optimize/admin/single_add_user_screen.php',
        'MaBox_Admin_Single_Show_ID' => 'admin/partials/optimize/admin/single_show_id.php',
        'MaBox_Admin_Single_Thumbnail_Switcher' => 'admin/partials/optimize/admin/thumbnail_switcher/index.php',
        'MaBox_Ai_Provider_Aliyun' => 'admin/partials/ai_review/provider/aliyun.php',
        'MaBox_Ai_Provider_Custom_Api' => 'admin/partials/ai_review/provider/custom_api.php',
        'MaBox_Ai_Provider_DeepSeek' => 'admin/partials/ai_review/provider/deepseek.php',
        'MaBox_Ai_Provider_Local_Rules' => 'admin/partials/ai_review/provider/local_rules.php',
        'MaBox_Ai_Provider_Manager' => 'admin/partials/ai_review/provider_manager.php',
        'MaBox_Ai_Review' => 'admin/partials/ai_review/index.php',
        'MaBox_Audit_Logger' => 'includes/class-magick-audit-logger.php',
        'MaBox_B2_Shop' => 'admin/partials/function/b2/index.php',
        'MaBox_B2_Shop_Add_Menu' => 'admin/partials/function/b2/add_menu.php',
        'MaBox_B2_Shop_Day' => 'admin/partials/function/b2/day.php',
        'MaBox_Baidu_Tonji' => 'admin/partials/function/auxiliary/baidu_tonji.php',
        'MaBox_Ban_Malice_Search' => 'admin/partials/function/auxiliary/ban_malice_search.php',
        'MaBox_Ban_Update' => 'admin/partials/optimize/site/ban_update.php',
        'MaBox_Biying_Tonji' => 'admin/partials/function/auxiliary/biying_tonji.php',
        'MaBox_CDN_Replace' => 'admin/partials/optimize/site/cdn_replace.php',
        'MaBox_Category_Link_Simplify' => 'admin/partials/optimize/site/category_link_simplify.php',
        'MaBox_Census_Single' => 'admin/partials/function/auxiliary/census-single.php',
        'MaBox_Comment_Baidu_Moderation' => 'admin/partials/page/comment/baidu_moderation/index.php',
        'MaBox_Comment_Ban_Pure_English' => 'admin/partials/page/comment/ban_pure_english.php',
        'MaBox_Comment_Limit_Word_Count' => 'admin/partials/page/comment/limit_word_count.php',
        'MaBox_Comment_Modify_User_Style' => 'admin/partials/page/comment/comment_modify_user_style.php',
        'MaBox_Comment_Only_Once' => 'admin/partials/page/comment/only_comment_once.php',
        'MaBox_Comment_Sensitive_Words' => 'admin/partials/page/comment/sensitive_words.php',
        'MaBox_Config' => 'admin/partials/function/config/index.php',
        'MaBox_Config_Manager' => 'includes/class-magick-config-manager.php',
        'MaBox_Config_Schema' => 'includes/class-mabox-config-schema.php',
        'MaBox_Diagnostics' => 'includes/class-mabox-diagnostics.php',
        'MaBox_Diary_Post_Type' => 'admin/partials/page/diary/index.php',
        'MaBox_Domestic_Baidu_Push' => 'admin/partials/domestic/baidu_push/index.php',
        'MaBox_Domestic_Comment_Security' => 'admin/partials/domestic/comment_security/index.php',
        'MaBox_Domestic_Compliance' => 'admin/partials/domestic/compliance/index.php',
        'MaBox_Domestic_Login_Security' => 'admin/partials/domestic/login_security/index.php',
        'MaBox_Domestic_Wechat' => 'admin/partials/domestic/wechat/index.php',
        'MaBox_Download_SQL_Table' => 'admin/partials/function/download-sql-table.php',
        'MaBox_Feedback' => 'admin/partials/feedback/index.php',
        'MaBox_Function_Wx_Xcx_Link' => 'admin/partials/function/wx_xcx_link/index.php',
        'MaBox_Google_Tonji' => 'admin/partials/function/auxiliary/google_tonji.php',
        'MaBox_H5' => 'admin/partials/h5.php',
        'MaBox_Helpers' => 'includes/class-magick-helpers.php',
        'MaBox_Hide_Email_IP' => 'admin/partials/optimize/site/hide_email_ip.php',
        'MaBox_Hide_Top_Toolbar' => 'admin/partials/optimize/site/hide_top_toolbar.php',
        'MaBox_Image_Add_Tag' => 'admin/partials/optimize/medium/image_add_tag.php',
        'MaBox_Interface_Category_Data' => 'admin/partials/page/jurisdiction/interface_category_data.php',
        'MaBox_Jump_Middle_Page' => 'admin/partials/page/function/jump_middle_page.php',
        'MaBox_Login_Change_Logo_Link' => 'admin/partials/login/beautify/change_login_logo_link.php',
        'MaBox_Login_Custom_Page' => 'admin/partials/login/beautify/custom_login_page.php',
        'MaBox_Login_Remove_Lang_Select' => 'admin/partials/login/beautify/remove_login_lang_select.php',
        'MaBox_Login_Verify' => 'admin/partials/login/security/login_verify.php',
        'MaBox_Maintenance_Tips' => 'admin/partials/page/function/maintenance_tips.php',
        'MaBox_Medium_Ban_Auto_Size' => 'admin/partials/optimize/medium/ban_auto_size.php',
        'MaBox_Medium_Image_Rename' => 'admin/partials/optimize/medium/image_rename.php',
        'MaBox_Medium_Svg_Support' => 'admin/partials/optimize/medium/svg_support.php',
        'MaBox_Module_Loader' => 'admin/modules/loader.php',
        'MaBox_Page_Add_Click_Effect' => 'admin/partials/page/exterior/click_effect/index.php',
        'MaBox_Page_Add_Convergence_Line' => 'admin/partials/page/exterior/background_effect/convergence_line/index.php',
        'MaBox_Page_Add_Scroll_Bar' => 'admin/partials/page/exterior/add_scroll_bar.php',
        'MaBox_Page_All_Grey' => 'admin/partials/page/exterior/all_grey.php',
        'MaBox_Page_Anti_Crawler' => 'admin/partials/page/function/anti_crawler/index.php',
        'MaBox_Page_Article_Rating' => 'admin/partials/page/function/article_rating.php',
        'MaBox_Page_Back_Top_Cat' => 'admin/partials/page/exterior/go_top/cord_cat/index.php',
        'MaBox_Page_Background_Effect' => 'admin/partials/page/exterior/background_effect/index.php',
        'MaBox_Page_Ban_Copy' => 'admin/partials/page/jurisdiction/ban_copy.php',
        'MaBox_Page_Ban_Open_QQ' => 'admin/partials/page/jurisdiction/ban_open_qq.php',
        'MaBox_Page_Ban_Open_WeiXing' => 'admin/partials/page/jurisdiction/ban_open_weixing.php',
        'MaBox_Page_Batch_Replace' => 'admin/partials/page/function/batch_replace.php',
        'MaBox_Page_Bottom_Effect' => 'admin/partials/page/exterior/bottom_effect/index.php',
        'MaBox_Page_Color_Tags' => 'admin/partials/page/function/color_tags.php',
        'MaBox_Page_Comment_Emoji' => 'admin/partials/page/comment/comment_emoji.php',
        'MaBox_Page_Comment_Interval' => 'admin/partials/page/comment/comment_interval.php',
        'MaBox_Page_Completed_Book' => 'admin/partials/page/exterior/completed_book.php',
        'MaBox_Page_Copy_Pop_Up' => 'admin/partials/page/exterior/copy_pop_up/index.php',
        'MaBox_Page_Default_Thumbnail' => 'admin/partials/page/function/default_thumbnail.php',
        'MaBox_Page_Drip_Ink' => 'admin/partials/page/exterior/background_effect/drip_ink/index.php',
        'MaBox_Page_Dynamic_Title' => 'admin/partials/page/exterior/dynamic_title.php',
        'MaBox_Page_Floating_Sphere' => 'admin/partials/page/exterior/background_effect/floating_sphere/index.php',
        'MaBox_Page_Flowing_Lines' => 'admin/partials/page/exterior/background_effect/flowing_lines/index.php',
        'MaBox_Page_Font_Switch' => 'admin/partials/page/exterior/font_switch/index.php',
        'MaBox_Page_Footer_Star' => 'admin/partials/page/exterior/background_effect/footer-star/index.php',
        'MaBox_Page_Front_Debug' => 'admin/partials/page/jurisdiction/front_debug.php',
        'MaBox_Page_Go_Top' => 'admin/partials/page/exterior/go_top/index.php',
        'MaBox_Page_Go_Top_Peep_Cat' => 'admin/partials/page/exterior/go_top/peep_cat/index.php',
        'MaBox_Page_Go_Top_Smooth_Arrow' => 'admin/partials/page/exterior/go_top/smooth_arrow/index.php',
        'MaBox_Page_Header_Notice' => 'admin/partials/page/function/header_notice.php',
        'MaBox_Page_Hide_Category' => 'admin/partials/page/jurisdiction/hide_category.php',
        'MaBox_Page_Hide_Page' => 'admin/partials/page/jurisdiction/hide_page.php',
        'MaBox_Page_Hide_Tag' => 'admin/partials/page/jurisdiction/hide_tag.php',
        'MaBox_Page_Lantern' => 'admin/partials/page/exterior/lantern/index.php',
        'MaBox_Page_Link_Source' => 'admin/partials/page/function/link_source.php',
        'MaBox_Page_Login_Search' => 'admin/partials/page/function/login_search.php',
        'MaBox_Page_Pixel_Chicken' => 'admin/partials/page/exterior/pixel_chicken/index.php',
        'MaBox_Page_Random_Ribbon' => 'admin/partials/page/exterior/background_effect/random_ribbon/index.php',
        'MaBox_Page_Reading_Progress' => 'admin/partials/page/exterior/reading_progress/index.php',
        'MaBox_Page_Runcode' => 'admin/partials/shortcode/compose/runcode/index.php',
        'MaBox_Page_Sakura_Drops' => 'admin/partials/page/exterior/background_effect/sakura_drops/index.php',
        'MaBox_Page_Screen_Hair' => 'admin/partials/page/exterior/screen_hair/index.php',
        'MaBox_Page_Scrolling' => 'admin/partials/page/exterior/scrolling/index.php',
        'MaBox_Page_Search_Limit' => 'admin/partials/page/function/search_limit.php',
        'MaBox_Page_Sliding_Ribbon' => 'admin/partials/page/exterior/background_effect/sliding_ribbon/index.php',
        'MaBox_Page_Top_Ad' => 'admin/partials/page/function/top_ad.php',
        'MaBox_Page_Top_Loading' => 'admin/partials/page/exterior/top_loading/index.php',
        'MaBox_Performance_Db_Clean' => 'admin/partials/performance/db_clean/index.php',
        'MaBox_Performance_Media_Health' => 'admin/partials/performance/media_health/index.php',
        'MaBox_Performance_Oss' => 'admin/partials/performance/oss/index.php',
        'MaBox_Performance_Search_Enhance' => 'admin/partials/performance/search_enhance/index.php',
        'MaBox_Performance_Seo_Checker' => 'admin/partials/performance/seo_checker/index.php',
        'MaBox_Privacy' => 'admin/partials/privacy/index.php',
        'MaBox_Public' => 'public/class-magick-mixture-public.php',
        'MaBox_Public_Add_Share' => 'admin/partials/page/function/share/index.php',
        'MaBox_Rate_Limiter' => 'includes/class-magick-rate-limiter.php',
        'MaBox_Remove_Sitemap_Users' => 'admin/partials/optimize/site/remove_sitemap_users.php',
        'MaBox_Remove_WP_Version' => 'admin/partials/optimize/site/remove_wp_version.php',
        'MaBox_Search_Link_Simplify' => 'admin/partials/optimize/site/search_link_simplify.php',
        'MaBox_Seo_Category' => 'admin/partials/function/seo/seo_category.php',
        'MaBox_Seo_Category_Add_Meat' => 'admin/partials/function/seo/seo_category_add_meat.php',
        'MaBox_Seo_Home' => 'admin/partials/function/seo/seo_home.php',
        'MaBox_Seo_Single' => 'admin/partials/function/seo/seo_single.php',
        'MaBox_Seo_Tag' => 'admin/partials/function/seo/seo_tag.php',
        'MaBox_Services' => 'admin/partials/services/index.php',
        'MaBox_ShortCode' => 'admin/partials/shortcode/index.php',
        'MaBox_ShortCode_Bilibili' => 'admin/partials/shortcode/compose/bilibili/index.php',
        'MaBox_ShortCode_Compose' => 'admin/partials/shortcode/compose/index.php',
        'MaBox_ShortCode_Merc_Map' => 'admin/partials/shortcode/pendant/merc_map/index.php',
        'MaBox_ShortCode_Pendant' => 'admin/partials/shortcode/pendant/index.php',
        'MaBox_ShortCode_Reward' => 'admin/partials/shortcode/compose/reward/index.php',
        'MaBox_ShortCode_Single_Copy' => 'admin/partials/shortcode/compose/single_copy/index.php',
        'MaBox_ShortCode_Single_List' => 'admin/partials/shortcode/compose/single_list/index.php',
        'MaBox_ShortCode_Wx_Unlock' => 'admin/partials/shortcode/compose/wx_unlock/index.php',
        'MaBox_Single_Add_Last_Updated_Date' => 'admin/partials/page/function/add_article_update_time.php',
        'MaBox_Single_First_Picture' => 'admin/partials/page/function/first_picture.php',
        'MaBox_Single_Keyword_Add_Link' => 'admin/partials/page/function/single_keyword_add_link.php',
        'MaBox_Single_Lang_Jf' => 'admin/partials/page/function/lang_jf/index.php',
        'MaBox_Single_Remove_Link' => 'admin/partials/page/function/single_remove_link.php',
        'MaBox_Site_Health' => 'includes/class-magick-site-health.php',
        'MaBox_Template' => 'admin/partials/template/index.php',
        'MaBox_Template_Special' => 'admin/partials/template/trends/special/index.php',
        'MaBox_Template_Static' => 'admin/partials/template/static/index.php',
        'MaBox_Template_Trends' => 'admin/partials/template/trends/index.php',
        'MaBox_Template_Triangle' => 'admin/partials/template/static/triangle/index.php',
        'MaBox_Ticket_System' => 'admin/partials/page/ticket/index.php',
        'MaBox_Tool' => 'includes/class-magick-mixture-tool.php',
        'MaBox_Unlisted_Vague_Img' => 'admin/partials/page/function/unlisted_vague_img.php',
        'MaBox_User_List_Show_Nickname' => 'admin/partials/optimize/site/user_list_show_nickname.php',
        'MaBox_Widgets' => 'admin/partials/optimize/widget/index.php',
        'Magick_Mixture' => 'includes/class-magick-mixture.php',
        'RunCode' => 'admin/partials/shortcode/compose/runcode/demo.php',
        'TS_Admin_Notice' => 'admin/partials/optimize/admin/thumbnail_switcher/class-ts-admin-notice.php',
        'TS_Easy_Thumbnail_Switcher' => 'admin/partials/optimize/admin/thumbnail_switcher/easy-thumbnail-switcher.php',
    );

    if (isset($map[$class])) {
        $path = $plugin_dir . '/' . $map[$class];
        if (file_exists($path)) {
            require_once $path;
        }
        return;
    }

    // MaBox_* 前缀类的默认映射
    $prefix = 'MaBox_';
    if (strpos($class, $prefix) === 0) {
        $relative = substr($class, strlen($prefix));
        $file = 'class-magick-' . str_replace('_', '-', strtolower($relative)) . '.php';
        $path = $plugin_dir . '/includes/' . $file;
        if (file_exists($path)) {
            require_once $path;
        }
    }
});
