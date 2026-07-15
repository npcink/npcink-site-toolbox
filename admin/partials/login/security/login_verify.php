<?php
/**
 * 登录验证码。
 *
 * 仅保留本地数学与随机验证，不加载第三方验证运行时。
 */
if (!class_exists('MaBox_Login_Verify')) {
    class MaBox_Login_Verify
    {
        public static function run($login_code)
        {
            switch ($login_code) {
                case 'math':
                    self::run_math();
                    break;
                case 'random':
                    self::run_random();
                    break;
                default:
                    return;
            }
        }

        public static function run_math()
        {
            add_action('login_form', array(__CLASS__, 'myplugin_add_login_fields'));
            add_action('login_form_login', array(__CLASS__, 'login_val'));
        }

        public static function myplugin_add_login_fields()
        {
            $num1 = rand(5, 20);
            $num2 = rand(5, 20);
            echo "<p><label for='math' class='small'>数学验证码：（ $num1 + $num2 = ?）
            <input type='text' name='sum' class='input' value='' size='20' tabindex='4'>"
                . "<input type='hidden' name='num1' value='$num1'>"
                . "<input type='hidden' name='num2' value='$num2'></label></p>";
        }

        public static function login_val()
        {
            $sum = isset($_POST['sum']) ? (int) $_POST['sum'] : 0;
            $num1 = isset($_POST['num1']) ? (int) $_POST['num1'] : 0;
            $num2 = isset($_POST['num2']) ? (int) $_POST['num2'] : 0;

            switch ($sum) {
                case $num1 + $num2:
                    break;
                case 0:
                    function empty_captcha_math()
                    {
                        return new WP_Error('empty_captcha', __('<strong>错误</strong>: 请输入数学验证码.', 'magick-toolbox'));
                    }
                    add_filter('wp_authenticate_user', 'empty_captcha_math', 10, 2);
                    break;
                default:
                    function incorrect_captcha_math()
                    {
                        return new WP_Error('incorrect_captcha', __('<strong>错误</strong>: 验证码错误，请重新输入.', 'magick-toolbox'));
                    }
                    add_filter('wp_authenticate_user', 'incorrect_captcha_math', 10, 2);
            }
        }

        public static function run_random()
        {
            add_action('login_form', array(__CLASS__, 'loper_login_english_figures'));
            add_action('login_form_login', array(__CLASS__, 'loper_login_calculation'));
        }

        public static function loper_login_english_figures()
        {
            $num1 = substr(md5(mt_rand(0, 99)), 0, 5);
            echo "<p>
            <label for='math' class='small'>验证码：$num1 </label>
            <input id='math' type='text' name='sum' class='input' value='' size='25'>
            <input type='hidden' name='num1' value='$num1'></p>";
        }

        public static function loper_login_calculation()
        {
            $sum = isset($_POST['sum']) ? (int) $_POST['sum'] : 0;
            $num1 = isset($_POST['num1']) ? (int) $_POST['num1'] : 0;

            switch ($sum) {
                case $num1:
                    break;
                case 0:
                    function empty_captcha_random()
                    {
                        return new WP_Error('empty_captcha', __('<strong>错误</strong>: 请输入验证码.', 'magick-toolbox'));
                    }
                    add_filter('wp_authenticate_user', 'empty_captcha_random', 10, 2);
                    break;
                default:
                    function incorrect_captcha_random()
                    {
                        return new WP_Error('incorrect_captcha', __('<strong>错误</strong>: 验证码错误，请重新输入.', 'magick-toolbox'));
                    }
                    add_filter('wp_authenticate_user', 'incorrect_captcha_random', 10, 2);
            }
        }
    }
}
