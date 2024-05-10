<?php
//外观特效
if (!class_exists('MaMi_Style_Aspect')) {
    class MaMi_Style_Aspect
    {
        //选项值
        private static $option;
        //加载
        public static function run($config)
        {
            //获取选项
            $option =  MaMi_Admin::get_config($config, 'aspect');

            //传值
            self::$option = $option;


         



          
           
                
            

            //细线联结
            $coupling = MaMi_Admin::get_config($option, 'coupling');
            if ($coupling) {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'coupling'));
            }


            //屏幕上有根毛
            $screen_hair = MaMi_Admin::get_config($option, 'screen_hair');
            if ($screen_hair) {

                add_action('wp_enqueue_scripts', array(__CLASS__, 'screen_hair'));
            }

            /**
             * 网页整体变灰
             */
            $site_grey =  MaMi_Admin::get_config($option, 'site_grey');
            if ($site_grey) {
                add_action('wp_footer', array(__CLASS__, 'site_grey'));
            }

            /**
             * 添加灯笼
             */
            $lantern =  MaMi_Admin::get_config($option, 'lantern');
            if ($lantern) {
                //移动端不展示
                if (!wp_is_mobile()) {
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'lantern_css'));
                    add_action('wp_footer', array(__CLASS__, 'lantern'));
                }
            }

            /**
             * 添加樱花
             */
            $sakura =  MaMi_Admin::get_config($option, 'sakura');
            if ($sakura) {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'sakura'));
            }
        }

       

        

       

        /**
         * 细线联结
         */
        public static function coupling()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_canvas-nest',
                plugin_dir_url(dirname(__DIR__)) . 'js/canvas-nest.min.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 屏幕上有根毛
         */
        public static function screen_hair()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_hair',
                plugin_dir_url(dirname(__DIR__)) . 'js/hair.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
            // 获取上一层的 image 文件夹路径
            $image_folder_path =  plugin_dir_url(dirname(__DIR__)) . 'image/';

            //传递路径
            wp_localize_script(
                MAGICK_MIXTURE_NAME . '_hair-js',
                'image_folder',
                $image_folder_path,
            );
        }


        //网站变灰
        public static function site_grey()
        {

            echo '<style type="text/css">
            /*网站整体灰白 - Npcink*/
            html {
                -webkit-filter: grayscale(0.95); /* webkit */
                -moz-filter: grayscale(0.95); /*firefox*/
                -ms-filter: grayscale(0.95); /*ie9*/
                -o-filter: grayscale(0.95); /*opera*/
                filter: grayscale(0.95);
            }
            </>';
        }

        /**
         * 添加灯笼css
         */
        public static function lantern_css()
        {
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_lantern',
                plugin_dir_url(dirname(__DIR__)) . 'css/lantern.css',
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );
        }
        /**
         * 添加灯笼节点
         */
        public static function lantern()
        {
            $lantern_left =  MaMi_Admin::get_config(self::$option, 'lantern_left', "春");
            $lantern_right =  MaMi_Admin::get_config(self::$option, 'lantern_right', "节");
            echo '
<div id="lantern">
<div class="deng-box">
<div class="deng">
    <div class="xian"></div>
    <div class="deng-a">
        <div class="deng-b"><div class="deng-t">' . $lantern_right . '</div></div>
    </div>
    <div class="shui shui-a"><div class="shui-c"></div><div class="shui-b"></div></div>
</div>
</div>

<!-- 灯笼2 -->
<div class="deng-box1">
<div class="deng">
    <div class="xian"></div>
    <div class="deng-a">
        <div class="deng-b"><div class="deng-t">' . $lantern_left . '</div></div>
    </div>
    <div class="shui shui-a"><div class="shui-c"></div><div class="shui-b"></div></div>
</div>
</div>
</div>
<!--结束包裹我-->';
        }

        /**
         * 添加樱花
         */
        public static function sakura()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_sakura',
                plugin_dir_url(dirname(__DIR__)) . 'js/sakuraPlus.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
