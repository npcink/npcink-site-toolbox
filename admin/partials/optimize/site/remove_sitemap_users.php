<?php

/**
 * 效果：从原生站点地图中移除用户信息部分
 * 来源：https://www.huitheme.com/wp-sitemap-users.html
 */
if (!class_exists('MaBox_Remove_Sitemap_Users')) {
    class MaBox_Remove_Sitemap_Users
    {

        public static  function run()
        {
            /**
             * Sitemap xml 禁止 wp-sitemap-users-1.xml
             * https://www.huitheme.com/wp-sitemap-users.html
             */
            add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
                return ($name == 'users') ? false : $provider;
            }, 10, 2);
        }
    }
}
