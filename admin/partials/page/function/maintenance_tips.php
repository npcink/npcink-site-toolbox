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
            self::$configs = $config; //展示类型
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
                    case "default": //默认
                        wp_die(self::$blogname . ' 升级维护中，过一会再来吧！');
                        break;
                    case "default_img": //默认带图
                        self::default_img();
                        break;
                    case "red": //红色纯粹
                        include(self::$path . 'red.php');
                        exit; // 重定向后立即退出
                        break;

                    case "purple": //紫色期待
                        include(self::$path . 'purple/index.php');
                        exit;
                        break;

                    case "lighting": //灯光聚焦
                        include(self::$path . 'lighting/index.php');
                        exit;
                        break;
                    case "masking": //大气遮罩
                        include(self::$path . 'masking/index.php');
                        exit;
                        break;
                    case "rotate": //旋转时钟
                        include(self::$path . 'rotate/index.php');
                        exit;
                        break;
                    default:
                        break;
                }
            }
        }

        //默认带图
        public static function default_img()
        {
            $logo = self::$url . 'default/tips.svg';
            wp_die('<div style="text-align:center">
            
            <img src="' . $logo . '" alt="' . self::$blogname . '" /><br /><br />' . self::$blogname . ' 正在例行维护中，请稍候...</div>', '站点维护中 - ' . self::$blogname . ' - ' . self::$blogdescription, array('response' => '503'));
        }
    }
}
