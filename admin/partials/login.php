<?php

/**
 * 登录页优化
 */
if (!class_exists('Npcink_Login')) {
    class Npcink_Login
    {
        public static function run()
        {
            //获取设置选项值
            $config = MaMi_Admin::get_seting('login');

            //登录页美化
            require_once plugin_dir_path(__FILE__) . 'login/beautify/index.php';//加载文件
            $beautify =  MaMi_Admin::get_config($config, 'beautify');//获取设置选项值
            Npcink_Login_Beautify::run($beautify);//传值

             //登录页安全
             require_once plugin_dir_path(__FILE__) . 'login/security/index.php';//加载文件
             $security =  MaMi_Admin::get_config($config, 'security');//获取设置选项值
             Npcink_Login_Security::run($security);//传值
        }
    }
}
