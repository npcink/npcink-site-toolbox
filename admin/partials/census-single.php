<?php
//文章统计菜单

//如何在当前页面加载js
if (!class_exists('Magick_Mixtrue_Census_Single')) {
    class Magick_Mixtrue_Census_Single extends Magick_Mixtrue
    {

        public function __construct()
        {
            self::init_actions();
        }

        public static function init_actions()
        {

            add_action('admin_enqueue_scripts', array(__CLASS__, 'load_block_js'));

        }

        public static function load_echarts()
        {
            echo "简单有趣的文本";
        }
        //加载echarts 用于图标绘制
        public static function load_block_js()
        {
            wp_enqueue_style('插件名', plugin_dir_url(dirname(__FILE__)) . 'js/echarts_v5.4.0.js', array(), '版本号', 'all');

        }
        //开始判断，在文章统计页则加载
        //public static function current_page_hook($hook)
        //{
        //    if ('dashboard_page_magick-census-single' == $hook) {
        //        //是指定页
        //        return true;
        //    }
        //}

        //public function 

    } //end class
}

//激活插件时运行
//add_action('plugins_loaded', array('Magick_Mixtrue_Census_Single', 'init_actions'));
//add_action('admin_enqueue_scripts', array('Magick_Mixtrue_Census_Single', 'load_block_js'));
