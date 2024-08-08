<?php

/**
 * 功能
 */
if (!class_exists('MaBox_Function')) {
    class MaBox_Function
    {
        public static function run()
        {

            //获取设置选项值
            $config = MaBox_Admin::get_seting('function');

            //下载指定数据库表内容
            require_once plugin_dir_path(__FILE__) . 'download-sql-table.php';
            MaBox_Download_SQL_Table::run();

            //辅助功能
            require_once plugin_dir_path(__FILE__) . 'auxiliary/index.php';
            $auxiliary =  MaBox_Admin::get_config($config, 'auxiliary');
            MaBox_Function_Auxiliary::run($auxiliary);

            //微信生成小程序跳转链接
            require_once plugin_dir_path(__FILE__) . 'wx_xcx_link/index.php';
            $wx_xcx =  MaBox_Admin::get_config($config, 'wx_xcx');
            MaBox_Function_Wx_Xcx_Link::run($wx_xcx);

            //B2 功能选项
            require_once plugin_dir_path(__FILE__) . 'b2/index.php';
            $b2 =  MaBox_Admin::get_config($config, 'b2');
            Npcink_B2_Shop::run($b2);

            //简单SEO
            require_once plugin_dir_path(__FILE__) . 'seo/index.php';
            $b2 =  MaBox_Admin::get_config($config, 'seo');
            Npcink_Easy_Seo::run($b2);

            //插件设置
            require_once plugin_dir_path(__FILE__) . 'config/index.php';
            $config_data =  MaBox_Admin::get_config($config, 'config');
            MaBox_Config::run($config_data);
        }
    } //end
}
