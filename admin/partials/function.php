<?php

/**
 * 功能
 */
if (!class_exists('MaMi_Function')) {
    class MaMi_Function
    {
        public static function run()
        {
            //加载文件
            self::load();
            //获取设置选项值
            $config = MaMi_Admin::get_seting('authority');

            //下载指定数据库表内容
            MaMi_Download_SQL_Table::run();

            //禁用
            $disable =  MaMi_Admin::get_config($config, 'disable');
            MaMi_Function_Disabled::run($disable);

            //辅助功能
            $auxiliary =  MaMi_Admin::get_config($config, 'auxiliary');
            MaMi_Auxiliary_Index::run($auxiliary);

            //微信生成小程序跳转链接
            $wx_xcx =  MaMi_Admin::get_config($config, 'wx_xcx');
            MaMi_Wx_Xcx::run($wx_xcx);

            //B2 功能选项
            $b2 =  MaMi_Admin::get_config($config, 'b2');
            Magick_Mixtrue_Census_Shop::run($b2);
        }

        //加载文件
        public static function load()
        {
            //下载指定数据库表内容
            require_once plugin_dir_path(__FILE__) . 'function/download-sql-table.php';

            //禁用
            require_once plugin_dir_path(__FILE__) . 'function/disabled/index.php';

            //商城统计页面
            require_once plugin_dir_path(__FILE__) . 'other/block/census-shop.php';



            //加载微信小程序链接生成
            require_once plugin_dir_path(__FILE__) . 'other/wx-xcx.php';

            //辅助功能
            require_once plugin_dir_path(__FILE__) . 'other/auxiliary.php';
        }
    } //end
}
