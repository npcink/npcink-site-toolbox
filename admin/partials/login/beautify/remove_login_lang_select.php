<?php

/**
 * 作用：移除登录页语言选择器
 * 来源：https://www.iowen.cn/yichuwordpress59dengluyemianzhongdeyuyanqiehuankuang/
 * */
if (!class_exists('MaBox_Login_Remove_Lang_Select')) {
    class MaBox_Login_Remove_Lang_Select
    {
        public static function run()
        {
            add_filter('login_display_language_dropdown', '__return_false');
        }
    }
}
