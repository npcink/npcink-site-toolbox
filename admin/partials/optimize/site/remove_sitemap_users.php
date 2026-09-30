<?php

defined('ABSPATH') || exit;

/**
 * 效果：从原生站点地图中移除用户信息部分
 * 来源：https://www.huitheme.com/wp-sitemap-users.html
 */
if (!class_exists('Npcink_Toolbox_Remove_Sitemap_Users')) {
    class Npcink_Toolbox_Remove_Sitemap_Users implements Npcink_Toolbox_Module_Interface
    {

        public static function run($config = array())
        {
            /**
             * Sitemap xml 禁止 wp-sitemap-users-1.xml
             * https://www.huitheme.com/wp-sitemap-users.html
             */
            add_filter('wp_sitemaps_add_provider', function ($provider, $name) {
                // phpcs:ignore Universal.Operators.StrictComparisons.LooseEqual -- 既有宽松比较语义已人工核实
                return ($name == 'users') ? false : $provider;
            }, 10, 2);
        }
    }
}
