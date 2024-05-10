
<?php
/**
 * 添加登录验证码
 */
if (!class_exists('Npcink_Login_Replace_Error_Message')) {
    class Npcink_Login_Replace_Error_Message
    {
        public static function run()
        {
            add_filter('login_errors', array(__CLASS__, 'remove_default_login_errors'));
        }

        /**
         * 作用：覆盖默认登录错误提示信息
         * 来源：https://rudrastyh.com/wordpress/11-security-steps.html
         */
        public static function remove_default_login_errors()
        {
            return '<span class="dashicons dashicons-info-outline" style="
            color: #d63638;
            margin: 0 6px;
        "></span>用户名或密码不正确';
        }
    }
}
