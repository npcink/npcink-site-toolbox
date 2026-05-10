<?php

/**
 * 效果：后台文章管理中添加作者过滤器
 * 来源：https://rudrastyh.com/wordpress/filter-posts-by-author.html
 */
if (!class_exists('MaBox_Admin_Single_Add_User_Screen')) {
    class MaBox_Admin_Single_Add_User_Screen
    {
        //加载
        public static function run()
        {
            add_action('restrict_manage_posts', array(__CLASS__, 'rudr_filter_by_the_author'));
        }

        public static function rudr_filter_by_the_author($post_type)
        {

            // 可以为特定的帖子类型添加条件
            // if( 'my_type' !== $post_type ) {
            //     return;
            // }

            $selected = isset($_GET['user']) && $_GET['user'] ? $_GET['user'] : '';

            wp_dropdown_users(
                array(
                    'role__in' => array(
                        'administrator',
                        'editor',
                        'author',
                        'contributor',
                    ),
                    'name' => 'author',
                    'show_option_all' => '全部作者',
                    'selected' => $selected,
                )
            );
        }
    }
}
