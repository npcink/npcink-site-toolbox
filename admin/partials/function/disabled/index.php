<?php

/**
 * 禁用
 */
if (!class_exists('MaMi_Function_Disabled')) {
    class MaMi_Function_Disabled
    {
        public static function run($disable)
        {
            //加载文件
            self::load();
            //禁用更新
            $renew = MaMi_Admin::get_config($disable, 'renew');
            if ($renew === true) {
                Npcink_Ban_Update::run();
            }
            //未登录模糊文章内图片
            $no_login_img = MaMi_Admin::get_config($disable, 'no_login_img');
            if ($no_login_img === true) {
                Npcink_Unlisted_Vague_Img::run();
            }
        }
        /**
         * 加载所需文件
         */
        static function load()
        {
            //禁用自动更新
            require_once plugin_dir_path(__FILE__) . 'ban_update.php';

            //未登录模糊文章内图片
            require_once plugin_dir_path(__FILE__) . 'unlisted_vague_img.php';
        }
    }
}
