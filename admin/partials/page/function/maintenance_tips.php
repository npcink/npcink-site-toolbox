<?php

/**
 * 效果：维护提示
 * 来源：
 */

if (!class_exists('Npcink_Maintenance_Tips')) {
    class Npcink_Maintenance_Tips
    {

        private static $configs; //配置
        private static $blogname; //博客名
        private static $blogdescription; //博客描述
        private static $url; //网址
        private static $path; //路径
        /**
         * 传来的页面类型
         */
        public static function run($config)
        {
            self::$configs = $config;//展示类型
            self::$blogname =  get_bloginfo('name');
            self::$blogdescription = get_bloginfo('description');
            self::$url = plugin_dir_url((__FILE__)) . 'maintenance/';
            self::$path = plugin_dir_path((__FILE__)) . 'maintenance/';
            //检查
            add_action('template_redirect', array(__CLASS__, 'check_administrator_permission'));
        }
        public static  function check_administrator_permission()
        {
            //不是管理员
            // if (!current_user_can('edit_themes') || !is_user_logged_in()) {
            if (!current_user_can('administrator')) {
                switch (self::$configs) {
                    case "default":
                        wp_die(self::$blogname . ' 升级维护中，过一会再来吧！');
                        break;
                    case "default_img":
                        add_action('get_header', array(__CLASS__, 'lxtx_wp_maintenance_mode'));
                        break;
                    case "red":
                        add_action('get_header', array(__CLASS__, 'red'));
                        break;
                    case "purple":
                        add_action('get_header', array(__CLASS__, 'purple'));
                        break;
                    case "lighting":
                        add_action('get_header', array(__CLASS__, 'lighting'));//灯光聚焦
                        break;
                    case "masking": //大气遮罩
                        add_action('get_header', array(__CLASS__, 'masking'));
                        break;
                    case "rotate": //时钟
                        add_action('get_header', array(__CLASS__, 'rotate'));
                        break;
                    default:
                        break;
                }
            }
        }

        public static   function lxtx_wp_maintenance_mode()
        {
            $logo = self::$url . 'image/tips.svg';
            wp_die('<div style="text-align:center">
            
            <img src="' . $logo . '" alt="' . self::$blogname . '" /><br /><br />' . self::$blogname . '正在例行维护中，请稍候...</div>', '站点维护中 - ' . self::$blogname . ' - ' . self::$blogdescription, array('response' => '503'));
        }

        //红色纯粹
        public static  function red()
        {
            // 检查条件，如果满足则执行跳转
            $php_page_path =  self::$path . 'red.php';
            include($php_page_path);
            exit; // 重定向后立即退出
        }

        //紫色期待
        public static  function purple()
        {
            // 检查条件，如果满足则执行跳转
            $php_page_path =  self::$path . 'purple.php';
            include($php_page_path);
            exit; // 重定向后立即退出
        }

        //灯光聚焦
        public static  function lighting()
        {
            // 检查条件，如果满足则执行跳转
            $php_page_path =  self::$path . 'lighting.php';
            include($php_page_path);
            exit; // 重定向后立即退出
        }

        //背景遮罩
        public static  function masking()
        {
            // 检查条件，如果满足则执行跳转
            $php_page_path =  self::$path . 'masking.php';
            include($php_page_path);
            exit; // 重定向后立即退出
        }

        //旋转时钟
        public static  function rotate()
        {
            // 检查条件，如果满足则执行跳转
            $php_page_path =  self::$path . 'rotate.php';
            include($php_page_path);
            exit; // 重定向后立即退出
        }
    }
}
