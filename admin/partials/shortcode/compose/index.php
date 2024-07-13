<?php

/**
 * 功能：短代码 板式
 */
if (!class_exists('MaBox_ShortCode_Compose')) {
    class MaBox_ShortCode_Compose  extends MaBox_ShortCode
    {
        public static function runs($option)
        {
            //文章列表
            $single_list = MaBox_Admin::get_config($option, 'single_list');
            if ($single_list === true) {
                require_once plugin_dir_path(__FILE__) . 'single_list/index.php';
                MaBox_ShortCode_Single_List::run();
                //下拉中添加短代码
                //这里需要进行转义，不然会丢失部分短代码内容
                self::$option_list .= '
                <option value="[past_posts_display ids=&quot;1,2,3&quot; limit=&quot;10&quot;]">文章列表</option>
              ';
            }

            //复制按钮
            $single_copy = MaBox_Admin::get_config($option, 'single_copy');
            if ($single_copy === true) {
                require_once plugin_dir_path(__FILE__) . 'single_copy/index.php';
                MaBox_ShortCode_Single_Copy::run();
                //下拉中添加短代码
                //这里需要进行转义，不然会丢失部分短代码内容
                self::$option_list .= '
                 <option value="[mabox_copy_btn name=&quot;按钮名称&quot; alert=&quot;复制成功&quot; link=&quot;#&quot;]待复制内容[/mabox_copy_btn]">复制按钮</option>
               ';
            }
        }
    } //end
}
