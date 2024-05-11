<?php

/**
 * 优化选项
 */

if (!class_exists('MaMi_Optimize')) {
    class MaMi_Optimize
    {
        public static function run()
        {
            //获取设置选项值
            $config = MaMi_Admin::get_seting('optimize');

            /**
             * 优化 - 站点
             */
            require_once plugin_dir_path(__FILE__) . 'optimize/site.php';
            MaMi_Optimize_Site::run($config);

            /**
             * 优化 - 媒体
             */
            require_once plugin_dir_path(__FILE__) . 'optimize/medium.php';
            MaMi_Optimize_Medium::run($config);


            /**
             * 优化 - 安全
             */
            require_once plugin_dir_path(__FILE__) . 'optimize/secure.php';
            MaMi_Optimize_Secure::run($config);


            /**
             * 优化 - 其他
             */
            require_once plugin_dir_path(__FILE__) . 'optimize/other.php';
            MaMi_Optimize_Other::run($config);
        }
    }
}
