<?php

/**
 * 效果：文章列表显示文章ID
 * 来源：https://blog.csdn.net/qq_39339179/article/details/119135050
 */
if (!class_exists('MaBox_Admin_Single_Show_ID')) {
    class MaBox_Admin_Single_Show_ID
    {
        //加载
        public static function run()
        {
            add_action('admin_init', array(__CLASS__, 'add_list_id_run'));
        }

        public static function add_list_id_run()
        {
            //ID 显示在第10行

            //添加样式
            add_action('admin_head', array(__CLASS__, 'ssid_css'));

            add_filter('manage_posts_columns', array(__CLASS__, 'ssid_column'));
            add_action('manage_posts_custom_column', array(__CLASS__, 'ssid_value'), 10, 2);

            add_filter('manage_pages_columns', array(__CLASS__, 'ssid_column'));
            add_action('manage_pages_custom_column', array(__CLASS__, 'ssid_value'), 10, 2);

            add_filter('manage_media_columns', array(__CLASS__, 'ssid_column'));
            add_action('manage_media_custom_column', array(__CLASS__, 'ssid_value'), 10, 2);

            add_filter('manage_link-manager_columns', array(__CLASS__, 'ssid_column'));
            add_action('manage_link_custom_column', array(__CLASS__, 'ssid_value'), 10, 2);

            add_action('manage_edit-link-categories_columns', array(__CLASS__, 'ssid_column'));
            add_filter('manage_link_categories_custom_column', array(__CLASS__, 'ssid_return_value'), 10, 3);

            foreach (get_taxonomies() as $taxonomy) {
                add_action("manage_edit-${taxonomy}_columns", array(__CLASS__, 'ssid_column'));
                add_filter("manage_${taxonomy}_custom_column", array(__CLASS__, 'ssid_return_value'), 10, 3);
            }

            add_action('manage_users_columns', array(__CLASS__, 'ssid_column'));
            add_filter('manage_users_custom_column', array(__CLASS__, 'ssid_return_value'), 10, 3);

            add_action('manage_edit-comments_columns', array(__CLASS__, 'ssid_column'));
            add_action('manage_comments_custom_column', array(__CLASS__, 'ssid_value'), 10, 2);
        }

        public static function ssid_column($cols)
        {
            $cols['ssid'] = 'ID';
            return $cols;
        }

        // 显示 ID
        public static function ssid_value($column_name, $id)
        {
            if ($column_name == 'ssid') {
                echo $id;
            }
        }

        public static function ssid_return_value($value, $column_name, $id)
        {
            if ($column_name == 'ssid') {
                $value = $id;
            }

            return $value;
        }

        // 为 ID 这列添加css
        public static function ssid_css()
        {
?>
            <style type="text/css">
                #ssid {
                    width: 50px;
                }

                /* Simply Show IDs */
            </style>
<?php
        }
    }
}
