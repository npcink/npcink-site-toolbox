<?php

/**
 * 登录页 美化
 */
if (!class_exists('Npcink_Login_Beautify')) {
    class Npcink_Login_Beautify
    {
        public static function run($beautify)
        {

            //自定义登录页
            $custom_login_page = MaMi_Admin::get_config($beautify, 'custom_login_page');
            if ($custom_login_page === true) {
                //自定义登录页
                require_once plugin_dir_path(__FILE__) . '/custom_login_page.php';
                Npcink_Login_Custom_Page::run($beautify);
            }
        }
    }
}
