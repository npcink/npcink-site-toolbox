<?php

/**
 * 静态页面模版
 */
if (!class_exists('Npcink_Template_Static')) {
    class Npcink_Template_Static extends Npcink_Template
    {
        public static function runs($option)
        {
            //爱心页面
            $love = MaBox_Admin::get_config($option, 'love');
            if ($love === true) {
                self::$add_template['template-one.php'] = 'Custom Template one';
                self::$load_template['template-one.php'] = 'static/template-one.php';
                self::$add_template['template-two.php'] = 'Custom Template Two';
                self::$load_template['template-two.php'] = 'static/template-two.php';

                require_once plugin_dir_path(__FILE__) . 'special/index.php';
                Npcink_Template_Special::run();
                //下拉中添加短代码
                //这里需要进行转义，不然会丢失部分短代码内容

            }
        }
    } //end
}
