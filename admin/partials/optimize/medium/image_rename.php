<?php

defined('ABSPATH') || exit;

/**
 * 功能：图片重命名
 * 来源：
 */
if (!class_exists('Npcink_Toolbox_Medium_Image_Rename')) {
    class Npcink_Toolbox_Medium_Image_Rename implements Npcink_Toolbox_Module_Interface
    {
        //加载
        public static function run($config = array())
        {
            $upload_auto_name = Npcink_Toolbox_Admin::get_config($config, 'upload_auto_name', 'false');

            switch ($upload_auto_name) {
                    //时间
                case 'math':
                    add_filter('wp_handle_upload_prefilter', array(__CLASS__, 'custom_upload_filter_time'));
                    add_filter('wp_handle_sideload_prefilter', array(__CLASS__, 'custom_upload_filter_time'));
                    break;
                    //md5重命名
                case 'md5':
                    add_filter('wp_handle_upload_prefilter', array(__CLASS__, 'custom_upload_filter_md5'));
                    add_filter('wp_handle_sideload_prefilter', array(__CLASS__, 'custom_upload_filter_md5'));
                    break;
                    //默认值
                default:
                    return;
            }
        }
        /**
         * 重命名
         */

        /*图片按时间自动重命名*/
        public static function custom_upload_filter_time($file)
        {
            $info         = pathinfo($file['name']);
            $ext          = $info['extension'];
            $filedate     = wp_date('YmdHis') . wp_rand(10, 99); //为了避免时间重复，再加一段2位的随机数
            $file['name'] = $filedate . '.' . $ext;
            return $file;
        }

        /*使用md5转码重命名媒体文件名*/
        public static function custom_upload_filter_md5($file)
        {
            $info         = pathinfo($file['name']);
            $ext          = '.' . $info['extension'];
            $md5          = md5($file['name']);
            $file['name'] = $md5 . $ext;
            return $file;
        }
    }
}
