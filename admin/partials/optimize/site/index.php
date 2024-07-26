<?php
//优化 站点
if (!class_exists('MaBox_Optimize_Site')) {
    class MaBox_Optimize_Site
    {
        //加载
        public static function run($config)
        {

            //获取选项
            $option =  MaBox_Admin::get_config($config, 'site');

            //禁止网站title中的 “-” 被转义
            $no_escape = MaBox_Admin::get_config($option, 'no_escape');
            if ($no_escape === true) {
                add_filter('run_wptexturize', '__return_false');
            };

            //禁用自动更新
            $renew = MaBox_Admin::get_config($option, 'renew');
            if ($renew === true) {
                require_once plugin_dir_path(__FILE__) . 'ban_update.php';
                Npcink_Ban_Update::run();
            }


            //从RSS源和网站中删除WordPress版本
            $remove_RSS_version = MaBox_Admin::get_config($option, 'remove_RSS_version');
            if ($remove_RSS_version === true) {
                require_once plugin_dir_path(__FILE__) . 'remove_wp_version.php';
                Npcink_Remove_WP_Version::run();
            }

            //分类链接去除 category
            $category_link_simplify = MaBox_Admin::get_config($option, 'category_link_simplify');
            if ($category_link_simplify === true) {
                require_once plugin_dir_path(__FILE__) . 'category_link_simplify.php';
                Npcink_Category_Link_Simplify::run();
            }

            //搜索链接优化
            $search_link_simplify = MaBox_Admin::get_config($option, 'search_link_simplify');
            if ($search_link_simplify === true) {
                require_once plugin_dir_path(__FILE__) . 'search_link_simplify.php';
                Npcink_Search_Link_Simplify::run();
            }

            //移除站点地图中的用户信息部分
            $remove_sitemap_users = MaBox_Admin::get_config($option, 'remove_sitemap_users');
            if ($remove_sitemap_users === true) {
                require_once plugin_dir_path(__FILE__) . 'remove_sitemap_users.php';
                Npcink_Remove_Sitemap_Users::run();
            }
            //用户列表展示昵称
            $user_list_show_nickname = MaBox_Admin::get_config($option, 'user_list_show_nickname');
            if ($user_list_show_nickname === true) {
                require_once plugin_dir_path(__FILE__) . 'user_list_show_nickname.php';
                Npcink_User_List_Show_Nickname::run();
            }
        }
    }
}
