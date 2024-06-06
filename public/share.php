<?php
//添加分享按钮

if (!class_exists('Npcink_Public_Add_Share')) {
    class Npcink_Public_Add_Share
    {
        private static $config; //分类数组
        public static function run()
        {
            //self::$config = $option;
            //加载HTML
            add_action('wp_footer', array(__CLASS__, 'add_share_html'));

            //加载公共样式
            add_action('wp_enqueue_scripts', array(__CLASS__, 'public_dist'));
        }





        //添加HTML
        public static function add_share_html()
        {

            echo '
            <p id="react_public"></p>
            ';
        }
        //添加公共前端资源
        public static function public_dist()
        {
            //判断下，是否在前端页中
            if (is_admin()) {
                return;
            }

            //准备打包后的数据
            $build_css = plugin_dir_url(dirname(__FILE__)) . 'vite/public/dist/index.css';
            $build_js = plugin_dir_url(dirname(__FILE__)) . 'vite/public/dist/index.js';


            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_public_index_css',
                $build_css,
                array(),
                MAGICK_MIXTURE_VERSION,
                'all'
            );
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_public_index_js',
                $build_js,
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );
            //
            //  //传输数据给JS
            //  $MaBox_array = array(
            //      'countData' => self::deliver_data(), //统计的数据信息
            //  );

            // wp_localize_script(MAGICK_MIXTURE_NAME . '_index_js', 'dataLocal', $MaBox_array); //传给vite项目
        }
    }
}
