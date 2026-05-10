<?php

/**
 * 效果：去除分类链接中的category字符串
 * 来源： No Category Base (WPML) v1.3
 */
if (!class_exists('MaBox_Category_Link_Simplify')) {
    class MaBox_Category_Link_Simplify
    {
        /**
         * 执行代码
         */
        public static function run()
        {
            /* hooks */
            register_activation_hook(__FILE__,    array(__CLASS__, 'no_category_base_refresh_rules'));
            register_deactivation_hook(__FILE__,  array(__CLASS__, 'no_category_base_deactivate'));

            /* actions */
            add_action('created_category',   array(__CLASS__, 'no_category_base_refresh_rules'));
            add_action('delete_category',    array(__CLASS__, 'no_category_base_refresh_rules'));
            add_action('edited_category',   array(__CLASS__, 'no_category_base_refresh_rules'));
            add_action('init',              array(__CLASS__, 'no_category_base_permastruct'));

            /* filters */
            add_filter('category_rewrite_rules', array(__CLASS__, 'no_category_base_rewrite_rules'));
            add_filter('query_vars',             array(__CLASS__, 'no_category_base_query_vars'));    // Adds 'category_redirect' query variable
            add_filter('request',                array(__CLASS__, 'no_category_base_request'));       // Redirects if 'category_redirect' is set
        }

        public static function no_category_base_refresh_rules()
        {
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
        }

        public static function no_category_base_deactivate()
        {
            remove_filter('category_rewrite_rules', 'no_category_base_rewrite_rules'); // We don't want to insert our custom rules again
            no_category_base_refresh_rules();
        }

        /**
         * Removes category base.
         *
         * @return void
         */
        public static function no_category_base_permastruct()
        {
            global $wp_rewrite;
            global $wp_version;

            if ($wp_version >= 3.4) {
                $wp_rewrite->extra_permastructs['category']['struct'] = '%category%';
            } else {
                $wp_rewrite->extra_permastructs['category'][0] = '%category%';
            }
        }

        /**
         * Adds our custom category rewrite rules.
         *
         * @param  array $category_rewrite Category rewrite rules.
         *
         * @return array
         */
        public static function no_category_base_rewrite_rules($category_rewrite)
        {
            global $wp_rewrite;
            $category_rewrite = array();

            /* WPML is present: temporary disable terms_clauses filter to get all categories for rewrite */
            if (class_exists('Sitepress')) {
                global $sitepress;

                remove_filter('terms_clauses', array($sitepress, 'terms_clauses'));
                $categories = get_categories(array('hide_empty' => false));
                //Fix provided by Albin here https://wordpress.org/support/topic/bug-with-wpml-2/#post-8362218
                //add_filter( 'terms_clauses', array( $sitepress, 'terms_clauses' ) );
                add_filter('terms_clauses', array($sitepress, 'terms_clauses'), 10, 4);
            } else {
                $categories = get_categories(array('hide_empty' => false));
            }

            foreach ($categories as $category) {
                $category_nicename = $category->slug;

                if ($category->parent == $category->cat_ID) {
                    $category->parent = 0;
                } elseif ($category->parent != 0) {
                    $category_nicename = get_category_parents($category->parent, false, '/', true) . $category_nicename;
                }

                $category_rewrite['(' . $category_nicename . ')/(?:feed/)?(feed|rdf|rss|rss2|atom)/?$'] = 'index.php?category_name=$matches[1]&feed=$matches[2]';
                $category_rewrite["({$category_nicename})/{$wp_rewrite->pagination_base}/?([0-9]{1,})/?$"] = 'index.php?category_name=$matches[1]&paged=$matches[2]';
                $category_rewrite['(' . $category_nicename . ')/?$'] = 'index.php?category_name=$matches[1]';
            }

            // Redirect support from Old Category Base
            $old_category_base = get_option('category_base') ? get_option('category_base') : 'category';
            $old_category_base = trim($old_category_base, '/');
            $category_rewrite[$old_category_base . '/(.*)$'] = 'index.php?category_redirect=$matches[1]';

            return $category_rewrite;
        }

        public static function no_category_base_query_vars($public_query_vars)
        {
            $public_query_vars[] = 'category_redirect';
            return $public_query_vars;
        }

        /**
         * Handles category redirects.
         *
         * @param $query_vars Current query vars.
         *
         * @return array $query_vars, or void if category_redirect is present.
         */
        public static function no_category_base_request($query_vars)
        {
            if (isset($query_vars['category_redirect'])) {
                $catlink = trailingslashit(get_option('home')) . user_trailingslashit($query_vars['category_redirect'], 'category');
                wp_safe_redirect($catlink, 301);
                exit();
            }

            return $query_vars;
        }
    }
}
