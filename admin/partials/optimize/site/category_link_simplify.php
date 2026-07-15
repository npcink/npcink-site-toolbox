<?php

/**
 * 效果：去除分类链接中的category字符串
 * 来源： No Category Base (WPML) v1.3
 */
if (!class_exists('MaBox_Category_Link_Simplify')) {
    class MaBox_Category_Link_Simplify implements MaBox_Module_Interface
    {
        /**
         * 执行代码
         */
        public static function run($config = array())
        {
            self::register_runtime_hooks();
        }

        /**
         * Apply rewrite rules on plugin activation only when the feature is enabled.
         */
        public static function activate()
        {
            $config = get_option(MAGICK_MIXTURE_OPTION_OPTIMIZE, array());
            if (!self::is_enabled($config)) {
                return;
            }

            self::enable();
        }

        /**
         * Restore WordPress category rewrite behavior before plugin deactivation.
         */
        public static function deactivate()
        {
            self::disable();
        }

        /**
         * Refresh rewrite rules after a successful Optimize Option update only
         * when category_link_simplify changes between boolean false and true.
         *
         * @param mixed $old_value Previous Optimize Option value.
         * @param mixed $new_value Updated Optimize Option value.
         */
        public static function handle_optimize_option_update($old_value, $new_value)
        {
            $was_enabled = self::get_enabled_value($old_value);
            $is_enabled = self::get_enabled_value($new_value);

            if (!is_bool($was_enabled) || !is_bool($is_enabled) || $was_enabled === $is_enabled) {
                return;
            }

            if ($is_enabled) {
                self::enable();
                return;
            }

            self::disable();
        }

        private static function enable()
        {
            self::register_runtime_hooks();
            self::no_category_base_permastruct();
            self::no_category_base_refresh_rules();
        }

        private static function disable()
        {
            self::unregister_runtime_hooks();
            self::restore_category_permastruct();
            self::no_category_base_refresh_rules();
        }

        private static function register_runtime_hooks()
        {

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

        private static function unregister_runtime_hooks()
        {
            remove_action('created_category', array(__CLASS__, 'no_category_base_refresh_rules'));
            remove_action('delete_category', array(__CLASS__, 'no_category_base_refresh_rules'));
            remove_action('edited_category', array(__CLASS__, 'no_category_base_refresh_rules'));
            remove_action('init', array(__CLASS__, 'no_category_base_permastruct'));

            remove_filter('category_rewrite_rules', array(__CLASS__, 'no_category_base_rewrite_rules'));
            remove_filter('query_vars', array(__CLASS__, 'no_category_base_query_vars'));
            remove_filter('request', array(__CLASS__, 'no_category_base_request'));
        }

        private static function restore_category_permastruct()
        {
            $category_taxonomy = get_taxonomy('category');
            if ($category_taxonomy && is_callable(array($category_taxonomy, 'add_rewrite_rules'))) {
                call_user_func(array($category_taxonomy, 'add_rewrite_rules'));
            }
        }

        private static function is_enabled($config)
        {
            return self::get_enabled_value($config) === true;
        }

        private static function get_enabled_value($config)
        {
            return is_array($config)
                && isset($config['site'])
                && is_array($config['site'])
                && array_key_exists('category_link_simplify', $config['site'])
                && is_bool($config['site']['category_link_simplify'])
                ? $config['site']['category_link_simplify']
                : null;
        }

        public static function no_category_base_refresh_rules()
        {
            global $wp_rewrite;
            $wp_rewrite->flush_rules();
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

                if ($category->parent == $category->term_id) {
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
