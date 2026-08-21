<?php

defined('ABSPATH') || exit;

/**
 * 效果：用户列表展示昵称
 * 来源：https://www.huitheme.com/add_user_nickname_column.html
 */
if (!class_exists('Npcink_Toolbox_User_List_Show_Nickname')) {
    class Npcink_Toolbox_User_List_Show_Nickname implements Npcink_Toolbox_Module_Interface
    {
        public static function run($config = array())
        {
            add_filter('manage_users_columns', array(__CLASS__, 'add_user_nickname_column'));
            add_action('manage_users_custom_column',  array(__CLASS__, 'show_user_nickname_column_content'), 20, 3);
        }


        public static function add_user_nickname_column($columns)
        {
            $columns['user_nickname'] = __('昵称', 'npcink-site-toolbox');
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

    }
}
