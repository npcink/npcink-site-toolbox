<?php

/**
 * 静态页面模版
 */
if (!class_exists('MaBox_Template_Static')) {
    class MaBox_Template_Static extends MaBox_Template
    {
        public static function runs($option)
        {
            //立体三角
            $triangle = MaBox_Admin::get_config($option, 'triangle');
            if ($triangle === true) {
                self::$add_template['template-triangle.php'] = '立体三角';
                self::$load_template['template-triangle.php'] = 'static/triangle/template-triangle.php';

                require_once plugin_dir_path(__FILE__) . 'triangle/index.php';
                MaBox_Template_Triangle::run();
                //下拉中添加短代码
                //这里需要进行转义，不然会丢失部分短代码内容

            }
        }
    } //end
}
