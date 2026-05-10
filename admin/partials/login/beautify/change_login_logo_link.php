<?php

/**
 * 作用：登录页LOGO改为首页链接
 * 来源：https://www.iowen.cn/chundaimameihuawordpressmorendengluye/
 * */
if (!class_exists('MaBox_Login_Change_Logo_Link')) {
    class MaBox_Login_Change_Logo_Link
    {
        public static function run()
        {
            add_filter('login_headerurl', array(__CLASS__, 'admin_logo_home'));
        }


        public static function admin_logo_home()
        {
            return esc_url(home_url());
        }
    }
}
