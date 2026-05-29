<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;
/**
 * 模块注册表
 *
 * 定义所有功能模块的元数据，替代硬编码的 build_active_modules_map + activate_module。
 *
 * @since 2.1.0
 */

return array(

    // ========== 站点优化 ==========
    'optimize.hide_top_toolbar' => array(
        'class'     => 'MaBox_Hide_Top_Toolbar',
        'file'      => 'optimize/site/hide_top_toolbar.php',
        'option_key'=> 'optimize.site.hide_top_toolbar',
        'category'  => 'optimize',
        'scope'     => 'both',
        'risk_tags' => array('推荐', '仅后台'),
    ),
    'optimize.no_escape' => array(
        'class'     => 'MaBox_No_Escape',
        'file'      => 'optimize/site/no_escape.php',
        'option_key'=> 'optimize.site.no_escape',
        'category'  => 'optimize',
        'scope'     => 'frontend',
        'risk_tags' => array('推荐'),
    ),
    'optimize.remove_wp_version' => array(
        'class'     => 'MaBox_Remove_WP_Version',
        'file'      => 'optimize/site/remove_wp_version.php',
        'option_key'=> 'optimize.site.remove_RSS_version',
        'category'  => 'optimize',
        'scope'     => 'both',
        'risk_tags' => array('推荐', '安全'),
    ),
    'optimize.ban_update' => array(
        'class'     => 'MaBox_Ban_Update',
        'file'      => 'optimize/site/ban_update.php',
        'option_key'=> 'optimize.site.renew',
        'category'  => 'optimize',
        'scope'     => 'admin',
        'risk_tags' => array('谨慎', '仅后台'),
    ),
    'optimize.category_link_simplify' => array(
        'class'     => 'MaBox_Category_Link_Simplify',
        'file'      => 'optimize/site/category_link_simplify.php',
        'option_key'=> 'optimize.site.category_link_simplify',
        'category'  => 'optimize',
        'scope'     => 'frontend',
        'risk_tags' => array('SEO'),
    ),
    'optimize.search_link_simplify' => array(
        'class'     => 'MaBox_Search_Link_Simplify',
        'file'      => 'optimize/site/search_link_simplify.php',
        'option_key'=> 'optimize.site.search_link_simplify',
        'category'  => 'optimize',
        'scope'     => 'frontend',
        'risk_tags' => array('SEO'),
    ),
    'optimize.remove_sitemap_users' => array(
        'class'     => 'MaBox_Remove_Sitemap_Users',
        'file'      => 'optimize/site/remove_sitemap_users.php',
        'option_key'=> 'optimize.site.remove_sitemap_users',
        'category'  => 'optimize',
        'scope'     => 'frontend',
        'risk_tags' => array('推荐', '安全'),
    ),
    'optimize.user_list_show_nickname' => array(
        'class'     => 'MaBox_User_List_Show_Nickname',
        'file'      => 'optimize/site/user_list_show_nickname.php',
        'option_key'=> 'optimize.site.user_list_show_nickname',
        'category'  => 'optimize',
        'scope'     => 'admin',
        'risk_tags' => array('仅后台'),
    ),
    'optimize.cdn_replace' => array(
        'class'     => 'MaBox_CDN_Replace',
        'file'      => 'optimize/site/cdn_replace.php',
        'option_key'=> 'optimize.site.cdn_replace',
        'category'  => 'optimize',
        'scope'     => 'frontend',
        'config_path' => 'optimize.site',
        'risk_tags' => array('性能'),
    ),
    'optimize.hide_email_ip' => array(
        'class'     => 'MaBox_Hide_Email_IP',
        'file'      => 'optimize/site/hide_email_ip.php',
        'option_key'=> 'optimize.site.hide_email_ip',
        'category'  => 'optimize',
        'scope'     => 'both',
        'risk_tags' => array('推荐', '安全'),
    ),
    'optimize.widgets' => array(
        'class'     => 'MaBox_Widgets',
        'file'      => 'optimize/widget/index.php',
        'option_key'=> 'optimize.widgets',
        'category'  => 'optimize',
        'scope'     => 'both',
        'always_load' => true,
        'risk_tags' => array('推荐'),
    ),
    'optimize.image_add_tag' => array(
        'class'     => 'MaBox_Image_Add_Tag',
        'file'      => 'optimize/medium/image_add_tag.php',
        'option_key'=> 'optimize.medium.img_add_tag',
        'category'  => 'optimize',
        'scope'     => 'frontend',
        'risk_tags' => array('推荐', 'SEO'),
    ),
    'optimize.ban_auto_size' => array(
        'class'     => 'MaBox_Medium_Ban_Auto_Size',
        'file'      => 'optimize/medium/ban_auto_size.php',
        'option_key'=> 'optimize.medium.no_auto_size',
        'category'  => 'optimize',
        'scope'     => 'admin',
        'risk_tags' => array('谨慎', '需主题兼容'),
    ),
    'optimize.svg_support' => array(
        'class'     => 'MaBox_Medium_Svg_Support',
        'file'      => 'optimize/medium/svg_support.php',
        'option_key'=> 'optimize.medium.medium_add_svg',
        'category'  => 'optimize',
        'scope'     => 'admin',
        'risk_tags' => array('进阶', 'XSS 风险'),
    ),
    'optimize.image_rename' => array(
        'class'     => 'MaBox_Medium_Image_Rename',
        'file'      => 'optimize/medium/image_rename.php',
        'option_key'=> 'optimize.medium.upload_auto_name',
        'category'  => 'optimize',
        'scope'     => 'admin',
        'config_path' => 'optimize.medium',
        'risk_tags' => array('推荐'),
    ),
    'optimize.admin_single_add_user_screen' => array(
        'class'     => 'MaBox_Admin_Single_Add_User_Screen',
        'file'      => 'optimize/admin/single_add_user_screen.php',
        'option_key'=> 'optimize.admin.add_user',
        'category'  => 'optimize',
        'scope'     => 'admin',
        'risk_tags' => array('仅后台'),
    ),
    'optimize.admin_add_time_screen' => array(
        'class'     => 'MaBox_Admin_Add_Time_Screen',
        'file'      => 'optimize/admin/add_time_screen.php',
        'option_key'=> 'optimize.admin.add_time',
        'category'  => 'optimize',
        'scope'     => 'admin',
        'risk_tags' => array('仅后台'),
    ),
    'optimize.admin_single_show_id' => array(
        'class'     => 'MaBox_Admin_Single_Show_ID',
        'file'      => 'optimize/admin/single_show_id.php',
        'option_key'=> 'optimize.admin.show_id',
        'category'  => 'optimize',
        'scope'     => 'admin',
        'risk_tags' => array('仅后台'),
    ),
    'optimize.admin_thumbnail_switcher' => array(
        'class'     => 'MaBox_Admin_Single_Thumbnail_Switcher',
        'file'      => 'optimize/admin/thumbnail_switcher/index.php',
        'option_key'=> 'optimize.admin.thumbnail_switcher',
        'category'  => 'optimize',
        'scope'     => 'admin',
        'risk_tags' => array('仅后台'),
    ),

    // ========== 页面外观 ==========
    'page.top_loading' => array(
        'class'     => 'MaBox_Page_Top_Loading',
        'file'      => 'page/exterior/top_loading/index.php',
        'option_key'=> 'page.feature.top_loading',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('仅前台'),
    ),

    'page.add_scroll_bar' => array(
        'class'     => 'MaBox_Page_Add_Scroll_Bar',
        'file'      => 'page/exterior/add_scroll_bar.php',
        'option_key'=> 'page.feature.scrol',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('仅前台'),
    ),

    'page.all_grey' => array(
        'class'     => 'MaBox_Page_All_Grey',
        'file'      => 'page/exterior/all_grey.php',
        'option_key'=> 'page.feature.site_grey',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('仅前台'),
    ),
    'page.copy_pop_up' => array(
        'class'     => 'MaBox_Page_Copy_Pop_Up',
        'file'      => 'page/exterior/copy_pop_up/index.php',
        'option_key'=> 'page.feature.copy_pop_up',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('仅前台'),
    ),

    'page.scrolling' => array(
        'class'     => 'MaBox_Page_Scrolling',
        'file'      => 'page/exterior/scrolling/index.php',
        'option_key'=> 'page.feature.page_scrolling',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('仅前台'),
    ),

    'page.reading_progress' => array(
        'class'     => 'MaBox_Page_Reading_Progress',
        'file'      => 'page/exterior/reading_progress/index.php',
        'option_key'=> 'page.feature.reading_progress',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.feature',
        'risk_tags' => array('仅前台'),
    ),
    'page.font_switch' => array(
        'class'     => 'MaBox_Page_Font_Switch',
        'file'      => 'page/exterior/font_switch/index.php',
        'option_key'=> 'page.feature.font_switch',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.feature',
        'risk_tags' => array('仅前台'),
    ),

    // ========== 页面评论 ==========
    'page.comment_emoji' => array(
        'class'     => 'MaBox_Page_Comment_Emoji',
        'file'      => 'page/comment/comment_emoji.php',
        'option_key'=> 'page.comment.comment_emote',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.comment_interval' => array(
        'class'     => 'MaBox_Page_Comment_Interval',
        'file'      => 'page/comment/comment_interval.php',
        'option_key'=> 'page.comment.interval',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.comment',
    ),
    'page.limit_word_count' => array(
        'class'     => 'MaBox_Comment_Limit_Word_Count',
        'file'      => 'page/comment/limit_word_count.php',
        'option_key'=> 'page.comment.words_number',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.comment',
    ),
    'page.ban_pure_english' => array(
        'class'     => 'MaBox_Comment_Ban_Pure_English',
        'file'      => 'page/comment/ban_pure_english.php',
        'option_key'=> 'page.comment.english',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.comment',
    ),
    'page.only_comment_once' => array(
        'class'     => 'MaBox_Comment_Only_Once',
        'file'      => 'page/comment/only_comment_once.php',
        'option_key'=> 'page.comment.only',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.comment_modify_user_style' => array(
        'class'     => 'MaBox_Comment_Modify_User_Style',
        'file'      => 'page/comment/comment_modify_user_style.php',
        'option_key'=> 'page.comment.modify_comment_user',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.comment_sensitive_words' => array(
        'class'     => 'MaBox_Comment_Sensitive_Words',
        'file'      => 'page/comment/sensitive_words.php',
        'option_key'=> 'page.comment.sensitive_words',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.comment',
    ),
    'page.comment_baidu_moderation' => array(
        'class'     => 'MaBox_Comment_Baidu_Moderation',
        'file'      => 'page/comment/baidu_moderation/index.php',
        'option_key'=> 'page.comment.baidu_moderation',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.comment',
    ),

    // ========== 页面功能 ==========
    'page.first_picture' => array(
        'class'     => 'MaBox_Single_First_Picture',
        'file'      => 'page/function/first_picture.php',
        'option_key'=> 'page.function.first_picture',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.single_keyword_add_link' => array(
        'class'     => 'MaBox_Single_Keyword_Add_Link',
        'file'      => 'page/function/single_keyword_add_link.php',
        'option_key'=> 'page.function.add_inks',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.single_remove_link' => array(
        'class'     => 'MaBox_Single_Remove_Link',
        'file'      => 'page/function/single_remove_link.php',
        'option_key'=> 'page.function.remove_single_link',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.add_article_update_time' => array(
        'class'     => 'MaBox_Single_Add_Last_Updated_Date',
        'file'      => 'page/function/add_article_update_time.php',
        'option_key'=> 'page.function.add_last_update',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.unlisted_vague_img' => array(
        'class'     => 'MaBox_Unlisted_Vague_Img',
        'file'      => 'page/function/unlisted_vague_img.php',
        'option_key'=> 'page.function.no_login_img',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.jump_middle_page' => array(
        'class'     => 'MaBox_Jump_Middle_Page',
        'file'      => 'page/function/jump_middle_page.php',
        'option_key'=> 'page.function.go_middle',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.maintenance_tips' => array(
        'class'     => 'MaBox_Maintenance_Tips',
        'file'      => 'page/function/maintenance_tips.php',
        'option_key'=> 'page.function.maintenance_tips',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.share' => array(
        'class'     => 'MaBox_Public_Add_Share',
        'file'      => 'page/function/share/index.php',
        'option_key'=> 'page.function.share',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.function',
    ),
    'page.lang_jf' => array(
        'class'     => 'MaBox_Single_Lang_Jf',
        'file'      => 'page/function/lang_jf/index.php',
        'option_key'=> 'page.function.switch_lang_jf',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),
    'page.default_thumbnail' => array(
        'class'     => 'MaBox_Page_Default_Thumbnail',
        'file'      => 'page/function/default_thumbnail.php',
        'option_key'=> 'page.function.default_thumbnail',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.function',
    ),
    'page.search_limit' => array(
        'class'     => 'MaBox_Page_Search_Limit',
        'file'      => 'page/function/search_limit.php',
        'option_key'=> 'page.function.search_limit',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.function',
    ),
    'page.batch_replace' => array(
        'class'     => 'MaBox_Page_Batch_Replace',
        'file'      => 'page/function/batch_replace.php',
        'option_key'=> 'page.function.batch_replace',
        'category'  => 'page',
        'scope'     => 'admin',
        'config_path' => 'page.function',
    ),
    'page.login_search' => array(
        'class'     => 'MaBox_Page_Login_Search',
        'file'      => 'page/function/login_search.php',
        'option_key'=> 'page.function.login_search',
        'category'  => 'page',
        'scope'     => 'frontend',
    ),

    'page.anti_crawler' => array(
        'class'     => 'MaBox_Page_Anti_Crawler',
        'file'      => 'page/function/anti_crawler/index.php',
        'option_key'=> 'page.function.anti_crawler',
        'category'  => 'page',
        'scope'     => 'frontend',
        'config_path' => 'page.function',
    ),

    // ========== 页面权限 ==========
    'page.ban_open_weixing' => array(
        'class'     => 'MaBox_Page_Ban_Open_WeiXing',
        'file'      => 'page/jurisdiction/ban_open_weixing.php',
        'option_key'=> 'page.jurisdiction.ban_open_weixing',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('谨慎', '仅前台'),
    ),
    'page.ban_open_qq' => array(
        'class'     => 'MaBox_Page_Ban_Open_QQ',
        'file'      => 'page/jurisdiction/ban_open_qq.php',
        'option_key'=> 'page.jurisdiction.ban_open_qq',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('谨慎', '仅前台'),
    ),
    'page.ban_copy' => array(
        'class'     => 'MaBox_Page_Ban_Copy',
        'file'      => 'page/jurisdiction/ban_copy.php',
        'option_key'=> 'page.jurisdiction.ban_copy',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('谨慎', '仅前台'),
    ),

    'page.hide_category' => array(
        'class'     => 'MaBox_Page_Hide_Category',
        'file'      => 'page/jurisdiction/hide_category.php',
        'option_key'=> 'page.jurisdiction.category_id',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('仅前台'),
    ),
    'page.hide_tag' => array(
        'class'     => 'MaBox_Page_Hide_Tag',
        'file'      => 'page/jurisdiction/hide_tag.php',
        'option_key'=> 'page.jurisdiction.tag_id',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('仅前台'),
    ),
    'page.hide_page' => array(
        'class'     => 'MaBox_Page_Hide_Page',
        'file'      => 'page/jurisdiction/hide_page.php',
        'option_key'=> 'page.jurisdiction.page_id',
        'category'  => 'page',
        'scope'     => 'frontend',
        'risk_tags' => array('仅前台'),
    ),

    // ========== SEO ==========
    'seo.seo_home' => array(
        'class'     => 'MaBox_Seo_Home',
        'file'      => 'function/seo/seo_home.php',
        'option_key'=> 'function.seo.seo_home',
        'category'  => 'function',
        'scope'     => 'frontend',
        'config_path' => 'function.seo',
        'risk_tags' => array('推荐', 'SEO'),
    ),
    'seo.seo_single' => array(
        'class'     => 'MaBox_Seo_Single',
        'file'      => 'function/seo/seo_single.php',
        'option_key'=> 'function.seo.seo_single',
        'category'  => 'function',
        'scope'     => 'frontend',
        'risk_tags' => array('推荐', 'SEO'),
    ),
    'seo.seo_category_add_meat' => array(
        'class'     => 'MaBox_Seo_Category_Add_Meat',
        'file'      => 'function/seo/seo_category_add_meat.php',
        'option_key'=> 'function.seo.seo_category',
        'category'  => 'function',
        'scope'     => 'frontend',
        'risk_tags' => array('SEO'),
    ),
    'seo.seo_category' => array(
        'class'     => 'MaBox_Seo_Category',
        'file'      => 'function/seo/seo_category.php',
        'option_key'=> 'function.seo.seo_category',
        'category'  => 'function',
        'scope'     => 'frontend',
        'risk_tags' => array('SEO'),
    ),
    'seo.seo_tag' => array(
        'class'     => 'MaBox_Seo_Tag',
        'file'      => 'function/seo/seo_tag.php',
        'option_key'=> 'function.seo.seo_category',
        'category'  => 'function',
        'scope'     => 'frontend',
        'risk_tags' => array('SEO'),
    ),

    // ========== 辅助功能 ==========
    'auxiliary.census_single' => array(
        'class'     => 'MaBox_Census_Single',
        'file'      => 'function/auxiliary/census-single.php',
        'option_key'=> 'function.auxiliary.single_count',
        'category'  => 'function',
        'scope'     => 'both',
    ),
    'auxiliary.ban_malice_search' => array(
        'class'     => 'MaBox_Ban_Malice_Search',
        'file'      => 'function/auxiliary/ban_malice_search.php',
        'option_key'=> 'function.auxiliary.no_malice_key',
        'category'  => 'function',
        'scope'     => 'frontend',
    ),
    'auxiliary.baidu_tonji' => array(
        'class'     => 'MaBox_Baidu_Tonji',
        'file'      => 'function/auxiliary/baidu_tonji.php',
        'option_key'=> 'function.auxiliary.baidu_tonji',
        'category'  => 'function',
        'scope'     => 'frontend',
    ),
    'auxiliary.google_tonji' => array(
        'class'     => 'MaBox_Google_Tonji',
        'file'      => 'function/auxiliary/google_tonji.php',
        'option_key'=> 'function.auxiliary.google_tonji',
        'category'  => 'function',
        'scope'     => 'frontend',
    ),
    'auxiliary.biying_tonji' => array(
        'class'     => 'MaBox_Biying_Tonji',
        'file'      => 'function/auxiliary/biying_tonji.php',
        'option_key'=> 'function.auxiliary.biying_tonji',
        'category'  => 'function',
        'scope'     => 'frontend',
    ),

    // ========== 登录页 ==========
    'login.change_login_logo_link' => array(
        'class'     => 'MaBox_Login_Change_Logo_Link',
        'file'      => 'login/beautify/change_login_logo_link.php',
        'option_key'=> 'login.beautify.modify_login_link',
        'category'  => 'login',
        'scope'     => 'admin',
        'risk_tags' => array('仅后台'),
    ),
    'login.remove_login_lang_select' => array(
        'class'     => 'MaBox_Login_Remove_Lang_Select',
        'file'      => 'login/beautify/remove_login_lang_select.php',
        'option_key'=> 'login.beautify.remove_langue',
        'category'  => 'login',
        'scope'     => 'admin',
        'risk_tags' => array('仅后台'),
    ),
    'login.custom_login_page' => array(
        'class'     => 'MaBox_Login_Custom_Page',
        'file'      => 'login/beautify/custom_login_page.php',
        'option_key'=> 'login.beautify.custom_login_page',
        'category'  => 'login',
        'scope'     => 'admin',
        'config_path' => 'login.beautify',
        'risk_tags' => array('仅后台'),
    ),
    'login.login_verify' => array(
        'class'     => 'MaBox_Login_Verify',
        'file'      => 'login/security/login_verify.php',
        'option_key'=> 'login.security.login_code',
        'category'  => 'login',
        'scope'     => 'admin',
        'risk_tags' => array('推荐', '安全', '仅后台'),
    ),

    // ========== 导入导出 ==========
    'function.config' => array(
        'class'     => 'MaBox_Config',
        'file'      => 'function/config/index.php',
        'option_key'=> 'function.config.pop_tips',
        'category'  => 'function',
        'scope'     => 'both',
        'config_path' => 'function.config',
    ),

    // ========== 页面 jurisdiction interface ==========
    'page.interface_category_data' => array(
        'class'     => 'MaBox_Interface_Category_Data',
        'file'      => 'page/jurisdiction/interface_category_data.php',
        'option_key'=> 'page.jurisdiction.category_id',
        'category'  => 'page',
        'scope'     => 'admin',
    ),

    // ========== 国内生态 - 备案与合规 ==========
    'domestic.compliance' => array(
        'class'     => 'MaBox_Domestic_Compliance',
        'file'      => 'domestic/compliance/index.php',
        'option_key'=> 'domestic.compliance.icp_enabled',
        'category'  => 'domestic',
        'scope'     => 'frontend',
        'config_path' => 'domestic.compliance',
        'risk_tags' => array('推荐'),
    ),

    // ========== 国内生态 - 百度推送 ==========
    'domestic.baidu_push' => array(
        'class'     => 'MaBox_Domestic_Baidu_Push',
        'file'      => 'domestic/baidu_push/index.php',
        'option_key'=> 'domestic.baidu_push.active_push_enabled',
        'category'  => 'domestic',
        'scope'     => 'both',
        'config_path' => 'domestic.baidu_push',
        'risk_tags' => array('推荐', 'SEO'),
    ),

    // ========== 国内生态 - 微信生态 ==========
    'domestic.wechat' => array(
        'class'     => 'MaBox_Domestic_Wechat',
        'file'      => 'domestic/wechat/index.php',
        'option_key'=> 'domestic.wechat.jssdk_enabled',
        'category'  => 'domestic',
        'scope'     => 'frontend',
        'config_path' => 'domestic.wechat',
        'risk_tags' => array('推荐'),
    ),

    // ========== 国内生态 - 评论安全 ==========
    'domestic.comment_security' => array(
        'class'     => 'MaBox_Domestic_Comment_Security',
        'file'      => 'domestic/comment_security/index.php',
        'option_key'=> 'domestic.comment_security.blacklist_enabled',
        'category'  => 'domestic',
        'scope'     => 'frontend',
        'config_path' => 'domestic.comment_security',
        'risk_tags' => array('推荐', '安全'),
    ),

    // ========== 国内生态 - 登录安全 ==========
    'domestic.login_security' => array(
        'class'     => 'MaBox_Domestic_Login_Security',
        'file'      => 'domestic/login_security/index.php',
        'option_key'=> 'domestic.login_security.fail_limit_enabled',
        'category'  => 'domestic',
        'scope'     => 'both',
        'config_path' => 'domestic.login_security',
        'risk_tags' => array('推荐', '安全'),
    ),

    // ========== 性能优化 - 对象存储 ==========
    'performance.oss' => array(
        'class'     => 'MaBox_Performance_Oss',
        'file'      => 'performance/oss/index.php',
        'option_key'=> 'performance.oss.enabled',
        'category'  => 'performance',
        'scope'     => 'admin',
        'config_path' => 'performance.oss',
        'risk_tags' => array('性能'),
    ),

    // ========== 性能优化 - SEO检查 ==========
    'performance.seo_checker' => array(
        'class'     => 'MaBox_Performance_Seo_Checker',
        'file'      => 'performance/seo_checker/index.php',
        'option_key'=> 'performance.seo_checker.enabled',
        'category'  => 'performance',
        'scope'     => 'admin',
        'config_path' => 'performance.seo_checker',
        'risk_tags' => array('SEO'),
    ),

    // ========== 性能优化 - 媒体库体检 ==========
    'performance.media_health' => array(
        'class'     => 'MaBox_Performance_Media_Health',
        'file'      => 'performance/media_health/index.php',
        'option_key'=> 'performance.media_health.enabled',
        'category'  => 'performance',
        'scope'     => 'admin',
        'config_path' => 'performance.media_health',
        'risk_tags' => array('推荐'),
    ),

    // ========== 性能优化 - 搜索增强 ==========
    'performance.search_enhance' => array(
        'class'     => 'MaBox_Performance_Search_Enhance',
        'file'      => 'performance/search_enhance/index.php',
        'option_key'=> 'performance.search_enhance.highlight_enabled',
        'category'  => 'performance',
        'scope'     => 'frontend',
        'config_path' => 'performance.search_enhance',
        'risk_tags' => array('推荐'),
    ),

    // ========== 性能优化 - 数据库清理 ==========
    'performance.db_clean' => array(
        'class'     => 'MaBox_Performance_Db_Clean',
        'file'      => 'performance/db_clean/index.php',
        'option_key'=> 'performance.db_clean.enabled',
        'category'  => 'performance',
        'scope'     => 'admin',
        'config_path' => 'performance.db_clean',
        'risk_tags' => array('高风险', '不可逆'),
    ),

    // ========== AI 审核引擎 ==========
    'ai_review.main' => array(
        'class'     => 'MaBox_Ai_Review',
        'file'      => 'ai_review/index.php',
        'option_key'=> 'ai_review.enabled',
        'category'  => 'ai_review',
        'scope'     => 'frontend',
        'config_path' => 'ai_review',
        'risk_tags' => array('推荐', '安全'),
    ),

);
