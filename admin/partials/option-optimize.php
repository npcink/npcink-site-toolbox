<?php

/**
 * 优化选项
 */
if (!class_exists('Magick_Mixtrue_Optimize')) {
    class Magick_Mixtrue_Optimize
    {

        //加载
        public static function run()
        {
            add_action('init', array(__CLASS__, 'load'));
        }
        //准备
        public static function load()
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
             * 优化 - 评论
             */
            require_once plugin_dir_path(__FILE__) . 'optimize/comment.php';
            MaMi_Optimize_Comment::run($config);

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


            //获取设置选项值
            $authority = MaMi_Admin::get_seting('authority');
            /**
             * 权限 - 功能
             */

            require_once plugin_dir_path(__FILE__) . 'auxiliary.php';
            MaMi_Auxiliary::run($authority);

          
        }





       
    }
}
