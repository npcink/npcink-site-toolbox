<?php

/**
 * 功能：媒体库支持SVG格式
 * 来源：
 */
if (!class_exists('MaBox_Medium_Svg_Support')) {
    class MaBox_Medium_Svg_Support
    {
        //加载
        public static function run()
        {
            self::run_add_svg();
        }
        //添加媒体库 SVG 图标支持
        public static function run_add_svg()
        {
            add_filter('upload_mimes', array(__CLASS__, 'salong_mime_types'));
            add_action('admin_head', array(__CLASS__, 'salong_admin_svg_css'));
        }

        //添加媒体库 SVG 图标支持
        public static function salong_mime_types($mimes)
        {
            $mimes['svg'] = 'image/svg+xml';
            return $mimes;
        }

        //在媒体库显示 SVG 图标
        public static function salong_admin_svg_css()
        {
            echo "
             <style>
             table.media .column-title .media-icon img[src*='.svg']{
              width: 100%;
              height: auto;
                     }
         </style>";
        }
    }
}
