<?php

defined('ABSPATH') || exit;

/**
 * 效果：后台文章管理中添加作者过滤器
 * 来源：https://rudrastyh.com/wordpress/filter-posts-by-author.html
 */
if (!class_exists('Npcink_Toolbox_Admin_Single_Add_User_Screen')) {
    class Npcink_Toolbox_Admin_Single_Add_User_Screen implements Npcink_Toolbox_Module_Interface
    {
        //加载
        public static function run($config = array())
        {
            add_action('restrict_manage_posts', array(__CLASS__, 'rudr_filter_by_the_author'));
        }

        public static function rudr_filter_by_the_author($post_type)
        {

            // 可以为特定的帖子类型添加条件
            // if( 'my_type' !== $post_type ) {
            //     return;
            // }

            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only value is unslashed here, then type-checked and converted below.
            $author = wp_unslash($_GET['author'] ?? '');
            $selected = is_string($author) ? absint($author) : 0;

            wp_dropdown_users(
                array(
                    'role__in' => array(
                        'administrator',
                        'editor',
                        'author',
                        'contributor',
                    ),
                    'name' => 'author',
                    'show_option_all' => __('全部作者', 'npcink-site-toolbox'),
                    'selected' => $selected,
                )
            );
        }
    }
}
