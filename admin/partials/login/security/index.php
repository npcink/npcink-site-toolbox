<?php

/**
 * 登录页 安全
 */
if (!class_exists('Npcink_Login_Security')) {
    class Npcink_Login_Security
    {
        public static function run($security)
        {
            $option = $security;

            //统一登录错误信息
           //$replace_login_error = MaBox_Admin::get_config($option, 'replace_login_error');
           //if ($replace_login_error === true) {
           //    require_once plugin_dir_path(__FILE__) . 'login_replace_error_message.php';
           //    Npcink_Login_Replace_Error_Message::run();
           //}

            //登录页验证码
            $login_code = MaBox_Admin::get_config($option, 'login_code');
            if ($login_code !== 'false') {
                //登录添加验证码
                require_once plugin_dir_path(__FILE__) . 'login_verify.php';
                Npcink_Login_Verify::run($login_code);
            }
        }
    }
}
