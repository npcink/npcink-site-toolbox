<?php
//添加菜单
if (!class_exists('MaBox_B2_Shop_Add_Menu')) {
    class MaBox_B2_Shop_Add_Menu extends MaBox_B2_Shop
    {

        //接收表格数据
        public static function run_add_menu()
        {

            //加载菜单
            add_action('admin_menu', array(__CLASS__, 'add_menu_shop'));

            //加载前端资源并传值
            add_action('admin_enqueue_scripts', array(__CLASS__, 'load_enqueue_admin_script'));

            //引入每天销售额
            require_once plugin_dir_path(__FILE__) . 'day.php';
        }

        /**
         * 添加商城菜单
         */
        public static function add_menu_shop()
        {
            add_submenu_page(
                'index.php',
                __('销售统计'),
                __('销售统计'),
                'administrator',
                'magick-census-shop',
                array(__CLASS__, 'load_content')
            );
        }

        //菜单显示内容
        public static function load_content()
        {
            $message = "<!-- 在默认WordPress“包装”容器中创建标题 -->";
            $message .= '<div class="wrap">';
            $message .= '<!--标题-->';
            $message .= '<h1><?php echo esc_html(get_admin_page_title()); ?></h1>';

            $message .= '<!--准备节点-->';
            $message .= '<div id="MaBox_b2_shop_count"></div>';
            echo $message;
        }

        //页面加载图标用css和js
        public static function load_enqueue_admin_script($hook)
        {
            //判断下，是否在当前页面
            if ('dashboard_page_magick-census-shop' != $hook) {
                return;
            }

            //准备打包后的数据
            $build_css = plugin_dir_url(dirname(__DIR__)) . 'count/dist/index.css';
            $build_css = str_replace('admin/partials', 'vite/',  $build_css);

            $build_js = plugin_dir_url(dirname(__DIR__)) . 'count/dist/index.js';
            $build_js = str_replace('admin/partials', 'vite/',  $build_js);
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_index_css',
                $build_css,
                array(),
                MAGICK_MIXTURE_VERSION,
                'all'
            );
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_index_js',
                $build_js,
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );

            //传输数据给JS
            $MaBox_array = array(
                'countData' => self::deliver_data(), //统计的数据信息
                'day_data' => MaBox_B2_Shop_Day::run(),
            );
            wp_localize_script(MAGICK_MIXTURE_NAME . '_index_js', 'dataLocal', $MaBox_array); //传给vite项目
        }
    }
}
