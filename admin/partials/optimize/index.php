<?php

/**
 * 优化选项
 */

if (!class_exists('MaBox_Optimize')) {
    class MaBox_Optimize
    {
        public static function run()
        {
            //获取设置选项值
            $config = MaBox_Admin::get_seting('optimize');

            /**
             * 优化 - 站点
             */
            require_once plugin_dir_path(__FILE__) . 'site/index.php';
            MaBox_Optimize_Site::run($config);

            /**
             * 优化 - 媒体
             */
            require_once plugin_dir_path(__FILE__) . 'medium/index.php';
            MaBox_Optimize_Medium::run($config);

            /**
             * 优化 - 后台
             */
            require_once plugin_dir_path(__FILE__) . 'admin/index.php';
            MaBox_Optimize_Admin::run($config);
        }
    }
}
