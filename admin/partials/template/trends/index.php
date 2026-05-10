<?php

/**
 * 动态页面模版
 */
if (!class_exists('MaBox_Template_Trends')) {
    class MaBox_Template_Trends extends MaBox_Template
    {
        public static function runs($option)
        {
            //专题页面
            $special = MaBox_Admin::get_config($option, 'special');
            if ($special === true) {
                self::$add_template['template-special.php'] = '专题页面';
                self::$load_template['template-special.php'] = 'trends/special/template-special.php';

                require_once plugin_dir_path(__FILE__) . 'special/index.php';
                MaBox_Template_Special::run();
                //下拉中添加短代码
                //这里需要进行转义，不然会丢失部分短代码内容

            }
        }
    } //end
}
