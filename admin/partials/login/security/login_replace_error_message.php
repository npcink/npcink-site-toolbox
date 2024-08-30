
<?php
/**
 * 作用：覆盖默认登录错误提示信息
 * 来源：https://rudrastyh.com/wordpress/11-security-steps.html
 */
if (!class_exists('Npcink_Login_Replace_Error_Message')) {
    class Npcink_Login_Replace_Error_Message
    {
        public static function run()
        {

            add_filter('wp_login_errors', array(__CLASS__, 'remove_default_login_errors'));
        }


        public static function remove_default_login_errors()
        {
            return '<span class="dashicons dashicons-info-outline" style="
            color: #d63638;
            margin: 0 6px;
        "></span><strong>错误</strong>：您输入的信息不正确，请检查后输入';
        }
    }
}

/*
function custom_login_error_message($errors) {
    if (isset($errors->errors['invalid_username'])) {
        $errors->errors['invalid_username'][0] = '用户名无效，请检查并重试。';
    }
    if (isset($errors->errors['incorrect_password'])) {
        $errors->errors['incorrect_password'][0] = '密码错误，请重新输入。';
    }
    return $errors;
}

add_filter('wp_login_errors', 'custom_login_error_message');
*/