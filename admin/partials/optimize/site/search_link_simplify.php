<?php

defined('ABSPATH') || exit;

/**
 * 效果：修改WordPress搜索结果的链接样式
 * 来源：https://www.huitheme.com/wordpress-search.html
 */
if (!class_exists('Npcink_Toolbox_Search_Link_Simplify')) {
    class Npcink_Toolbox_Search_Link_Simplify implements Npcink_Toolbox_Module_Interface
    {
        public static function run($config = array())
        {
            add_action('template_redirect', array(__CLASS__, 'redirect_search'));
        }

        //修改搜索结果的链接
        public static function redirect_search()
        {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only search query; no state is changed.
            $search_term = isset($_GET['s']) && is_string($_GET['s']) ? sanitize_text_field(wp_unslash($_GET['s'])) : '';
            if (!is_search() || '' === $search_term) {
                return;
            }

            // 纯链接站点没有 /search/ 重写规则，重定向过去只会得到 404；
            // 这种结构下原生 ?s= 链接本就可用，直接跳过
            if ('' === (string) get_option('permalink_structure', '')) {
                return;
            }

            $target = home_url('/search/' . rawurlencode($search_term));

            // 透传搜索上下文参数（白名单制），避免筛选搜索被静默丢弃
            $passthrough_keys = array('post_type', 'cat', 'category_name', 'tag', 'tag_id', 'author', 'paged');
            $passthrough      = array();
            foreach ($passthrough_keys as $key) {
                // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only search query; no state is changed.
                if (isset($_GET[ $key ]) && is_string($_GET[ $key ]) && $_GET[ $key ] !== '') {
                    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Public read-only search query; no state is changed.
                    $passthrough[ $key ] = sanitize_text_field(wp_unslash($_GET[ $key ]));
                }
            }
            if (!empty($passthrough)) {
                $target = add_query_arg($passthrough, $target);
            }

            wp_safe_redirect($target);
            exit();
        }
    }
}
