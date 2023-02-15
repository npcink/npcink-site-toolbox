<?php

if (!class_exists('Magick_Mixtrue_Admin_Census')) {
    class Magick_Mixtrue_Admin_Census
    {

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
         * 添加钩子
         */
        public static function run()
        {
            //实例化一下，跑起来
            $Census_Single = new Magick_Mixtrue_Census_Single;
        }

        /**
         * 导入资源
         */
        public function load()
        {
            require_once plugin_dir_path(__FILE__) . 'census-single.php';
        }
        /**
         * 添加发文统计菜单
         */
        public function add_menu_single()
        {

            add_submenu_page('index.php', __('发文统计'), __('发文统计'), 'administrator', 'magick-census-single', array(__CLASS__, 'census_single_content'));

        }
        /**
         * 添加商城菜单
         */
        public function add_menu_shop()
        {
            add_submenu_page('index.php', __('销售统计'), __('销售统计'), 'administrator', 'magick-census-shop', array(__CLASS__, 'census_shop_content'));
        }
        /**
         * 发文统计内容
         */
        public function census_single_content()
        {
            $content = new Magick_Mixtrue_Census_Single;
            echo '<div class="wrap">';
            echo '<h2>';
            echo esc_html(get_admin_page_title());
            $content->load_echarts();
            $content->init_actions();
            echo '</h2>';
            echo '</div>';
        }

        /**
         * 商城统计内容
         */
        public function census_shop_content()
        {
            echo '<div class="wrap">';
            echo '<h2>';
            echo esc_html(get_admin_page_title());
            echo '</h2>';
            echo '</div>';
        }

        /**
         * 开始判断，若安装B2主题，则返回true
         */
        public function b2_theme_active()
        {
            $tool = new Magick_Mixtrue_Tool;
            $theme = 'Twenty Twenty';
            if ($tool->theme_active($theme)) {
                echo "启用了2020主题";
                return true;
            } else {
                echo "没有启用2020主题";
                return false;
            }
        }

        /**
         * 是否加载商城统计内容
         */
        public function load_b2_shop()
        {

            if ($this->b2_theme_active()) {
                add_action('admin_menu', array(__CLASS__, 'add_menu_shop'));
            } else {
                //啥也不做
            }

        }

    } //end Magick_Mixtrue_Census

}
