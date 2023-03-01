<?php
/**
 * 效果：美化Wordpress登录页
 * 原文地址：https://www.iowen.cn/chundaimameihuawordpressmorendengluye/
 */
if (!class_exists('Magick_Mixtrue_Login')) {
    class Magick_Mixtrue_Login
    {
        public function __construct()
        {

        }
        public static function run()
        {
            add_action('init', array(__CLASS__, 'run_iowen'));
        }

        public static function run_iowen()
        {
            add_action('login_header', array(__CLASS__, 'io_login_header'));
            add_action('login_footer', array(__CLASS__, 'io_login_footer'));
            add_action('login_head', array(__CLASS__, 'custom_login_style'));
        }
        public static function io_login_header()
        {
            echo '<div class="login-container">
    <div class="login-body">
        <div class="login-img shadow-lg position-relative flex-fill">
            <div class="img-bg position-absolute">
                <div class="login-info">
                    <h2>' . get_bloginfo('name') . '</h2>
                    <p>' . get_bloginfo('description') . '</p>
                </div>
            </div>
        </div>';
        }
        public static function io_login_footer()
        {
            echo '</div><!--login-body END-->
    </div><!--login-container END-->
    <div class="footer-copyright position-absolute">
            <span>Copyright © <a href="' . esc_url(home_url()) . '" class="text-white-50" title="' . get_bloginfo('name') . '" rel="home">' . get_bloginfo('name') . '</a></span>
    </div>';
        }

        public static function custom_login_style()
        {
            //左下背景色
            $bg_left = carbon_get_theme_option('cmma_opt_login_bgcolor_left');
            //右上背景色
            $bg_right = carbon_get_theme_option('cmma_opt_login_bgcolor_right');
            //LOGO
            $logo_url = carbon_get_theme_option('cmma_opt_login_logo');
            //尺寸
            $logo_size = carbon_get_theme_option('cmma_opt_login_logo_size');
            //左边文字背景图
            $bg_img_left = carbon_get_theme_option('cmma_opt_login_bg_left');
            echo '<style type="text/css">
    body{
        background:-o-linear-gradient(45deg,' . $bg_left . ',' . $bg_right . ');
        background:linear-gradient(45deg,' . $bg_left . ',' . $bg_right . ');
        height:100vh;
    }
    .login h1 a{
        background-image:url(' . $logo_url . ' );
        width:180px;
        background-position:center center;
        background-size:' . $logo_size . 'px
    }

    .login-container{position:relative;display:flex;align-items:center;justify-content:center;height:100vh}
    .login-body{position:relative;display:flex;margin:0 1.5rem}
    .login-img{display:none}

    .img-bg{
        color:#fff;
        padding:2rem;
        bottom:-2rem;
        left:0;
        top:-2rem;
        right:0;
        border-radius:10px;
        background-image:url(' . $bg_img_left . ');
        background-repeat:no-repeat;background-position:center center;background-size:cover}
    .img-bg h2{font-size:2rem;margin-bottom:1.25rem}

    #login{position:relative;background:#fff;border-radius:10px;padding:28px;width:280px;box-shadow:0 1rem 3rem rgba(0,0,0,.175)}
    .flex-fill{flex:1 1 auto}
    .position-relative{position:relative}
    .position-absolute{position:absolute}
    .shadow-lg{box-shadow:0 1rem 3rem rgba(0,0,0,.175)!important}
    .footer-copyright{bottom:0;color:rgba(255,255,255,.6);text-align:center;margin:20px;left:0;right:0}
    .footer-copyright a{color:rgba(255,255,255,.6);text-decoration:none}
    #login form{-webkit-box-shadow:none;-moz-box-shadow:none;box-shadow:none;border-width:0;padding:0}
    #login form .forgetmenot{float:none}
    .login #login_error,.login .message,.login .success{border-left-color:#40b9f1;box-shadow:none;background:#d4eeff;border-radius:6px;color:#2e73b7}
    .login #login_error{border-left-color:#f1404b;background:#ffd4d6;color:#b72e37}
    #login form p.submit{padding:20px 0 0}
    #login form p.submit .button-primary{float:none;background-color:#f1404b;font-weight:bold;color:#fff;width:100%;height:40px;border-width:0;text-shadow:none!important;border-color:none;transition:.5s}
    #login form input{box-shadow:none!important;outline:none!important}
    #login form p.submit .button-primary:hover{background-color:#444}
    .login #backtoblog,.login #nav{padding:0}
    @media screen and (min-width:768px){.login-body{width:1200px}
    .login-img{display:block}
    #login{margin-left:-60px;padding:40px}
    }
    /*适配语言选择框*/
    .login-body {
        flex-wrap: wrap;
    }
    .language-switcher {
        min-width: 1100px;
        padding: 24px 0 24px;
    }
</style>';
        }
    }
}
