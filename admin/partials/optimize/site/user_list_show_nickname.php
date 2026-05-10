<?php

/**
 * 效果：用户列表展示昵称
 * 来源：https://www.huitheme.com/add_user_nickname_column.html
 */
if (!class_exists('MaBox_User_List_Show_Nickname')) {
    class MaBox_User_List_Show_Nickname
    {
        public static function run()
        {
            add_filter('manage_users_columns', array(__CLASS__, 'add_user_nickname_column'));
            add_action('manage_users_custom_column',  array(__CLASS__, 'show_user_nickname_column_content'), 20, 3);

            //add_action('pre_user_query', array(__CLASS__,'wpkj_extend_user_search'));
        }


        public static function add_user_nickname_column($columns)
        {
            $columns['user_nickname'] = '昵称';
            unset($columns['name']);
            return $columns;
        }

        public static function show_user_nickname_column_content($value, $column_name, $user_id)
        {
            $user = get_userdata($user_id);
            $user_nickname = $user->nickname;
            if ('user_nickname' == $column_name)
                return $user_nickname;
            return $value;
        }

        //支持后台昵称搜索
        public static function wpkj_extend_user_search($u_query)
        {
            // 确保代码仅应用于用户搜索
            if ($u_query->query_vars['search']) {
                $search_query = trim($u_query->query_vars['search'], '*');
                if ($_REQUEST['s'] == $search_query) {
                    global $wpdb;
                    // 添加昵称搜索查询语句
                    $u_query->query_from .= " JOIN {$wpdb->usermeta} fname ON fname.user_id = {$wpdb->users}.ID AND fname.meta_key = 'nickname'";
                    // 设置可搜索的字段
                    $search_by = array('user_login', 'user_email', 'fname.meta_value');
                    // 应用到搜索
                    $u_query->query_where = 'WHERE 1=1' . $u_query->get_search_sql($search_query, $search_by, 'both');
                }
            }
        }
    }
}
