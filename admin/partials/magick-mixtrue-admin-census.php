<?php

if (!class_exists('Magick_Mixtrue_Admin_Census')) {
    class Magick_Mixtrue_Admin_Census
    {

        protected $loader_option;

        public function __construct()
        {

            /**
             * 加载所需的依赖项
             */
            $this->load();
            /*
            加载钩子*/
            $this->run();
            //添加发文统计菜单
            add_action('admin_menu', array(__CLASS__, 'add_menu_single'));
            $this->load_b2_shop();

        }

        /**
         * 导入资源
         */
        public function load()
        {
            //文章统计
            require_once plugin_dir_path(__FILE__) . 'census-single.php';
            //商城统计
            require_once plugin_dir_path(__FILE__) . 'census-shop.php';
            //菜单统计
            //require_once plugin_dir_path(__FILE__) . 'option-menu.php';

        }

        /**
         * 添加钩子
         */
        public static function run()
        {

            //实例化一下，跑起来
            $Census_Single = new Magick_Mixtrue_Census_Single;

            //添加选项
            //$option = new Magick_Mixtrue_Option();

        }

        /**
         * 若安装指定主题，则加载商城统计内容
         */
        public function load_b2_shop()
        {
            $tool = new Magick_Mixtrue_Tool;
            //$theme = 'Twenty Twenty';
            $theme = 'B2 PRO';

            if ($tool->theme_active($theme)) {
                //安装了B2 PRO主题
                add_action('admin_menu', array(__CLASS__, 'add_menu_shop'));
                //实例化一下
                $a = new Magick_Mixtrue_Census_Shop();
            } else {
                //啥也不做
            }

        }
        /**
         * 添加发文统计菜单
         */
        public static function add_menu_single()
        {

            add_submenu_page('index.php', __('发文统计'), __('发文统计'), 'administrator', 'magick-census-single', array(__CLASS__, 'census_single_content'));

        }
        /**
         * 添加商城菜单
         */
        public static function add_menu_shop()
        {
            add_submenu_page('index.php', __('销售统计'), __('销售统计'), 'administrator', 'magick-census-shop', array(__CLASS__, 'census_shop_content'));
        }
        /**
         * 发文统计内容
         */
        public static function census_single_content()
        {
            Magick_Mixtrue_Census_Single::load_content();
        }

        /**
         * 商城统计内容
         */
        public static function census_shop_content()
        {
            Magick_Mixtrue_Census_Shop::load_content();
        }

    } //end Magick_Mixtrue_Census

}
