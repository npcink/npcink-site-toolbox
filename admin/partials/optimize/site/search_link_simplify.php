<?php

/**
 * 效果：修改WordPress搜索结果的链接样式
 * 来源：https://www.huitheme.com/wordpress-search.html
 */
if (!class_exists('MaBox_Search_Link_Simplify')) {
    class MaBox_Search_Link_Simplify
    {
        public static  function run()
        {
            add_action('template_redirect', array(__CLASS__, 'redirect_search'));
        }
        
        //修改搜索结果的链接
        public static function redirect_search()
        {
            if (is_search() && !empty($_GET['s'])) {
                wp_redirect(home_url("/search/") . urlencode(get_query_var('s')));
                exit();
            }
        }
    }
}
