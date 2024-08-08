<?php

/**
 * 用途：页面模版
 * 来源 ： https://www.huitheme.com/wordpress-search.html
 */

if (!class_exists('Npcink_Template')) {
    class Npcink_Template
    {
        //添加模版
        public static $add_template = array();
        //加载模版
        public static $load_template = array();
        public static function run()
        {
            //获取设置选项值
            $config = MaBox_Admin::get_seting('template');

            /**
             * 页面模版 - 静态页面
             */
            require_once plugin_dir_path(__FILE__) . 'static/index.php';
            $static =  MaBox_Admin::get_config($config, 'static');
            Npcink_Template_Static::runs($static);

            //添加
            add_filter('theme_page_templates', array(__CLASS__, 'custom_page_templates'));
            //加载
            add_filter('template_include', array(__CLASS__, 'load_custom_template'));
        }
        public static function custom_page_templates($templates)
        {
            $new_templates = self::$add_template;

            // 合并新的模板数组到现有的模板数组中
            $templates = array_merge($templates, $new_templates);

            return $templates;
        }


        // 根据选择的页面模板加载指定模板文件
        public static function load_custom_template($template)
        {
            global $post;

            // 如果 $post 对象不存在或者没有 ID 属性，则返回原始的 $template
            if (!isset($post->ID)) {
                return $template;
            }

            // 定义页面模板数组
            $custom_templates = self::$load_template;

            // 获取当前页面模板的文件路径
            $current_template_slug = get_page_template_slug($post->ID);
            $current_template_path = isset($custom_templates[$current_template_slug]) ? plugin_dir_path(__FILE__) . $custom_templates[$current_template_slug] : '';

            // 如果找到匹配的模板文件路径，则返回该路径
            if (!empty($current_template_path)) {
                return $current_template_path;
            }

            return $template;
        }
    }
}
