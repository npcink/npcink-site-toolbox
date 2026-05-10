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
                <option value="[past_posts_display ids=&quot;文章ID-1,文章ID-2,文章ID-3&quot; limit=&quot;10&quot;]">文章列表</option>
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
             //在线运行代码的短代码
             $runcode = MaBox_Admin::get_config($option, 'runcode');
             if ($runcode === true) {
                 require_once plugin_dir_path(__FILE__) . 'runcode/index.php';
                 MaBox_Page_Runcode::run();
                 self::$option_list .= '
                 <option value="<runcode>">在线运行代码-开始</option>
                 <option value="</runcode>">在线运行代码-结束</option>
               ';
             }
             //Bilibili视频嵌入
             $bilibili = MaBox_Admin::get_config($option, 'bilibili');
             if ($bilibili === true) {
                 require_once plugin_dir_path(__FILE__) . 'bilibili/index.php';
                 MaBox_ShortCode_Bilibili::run();
                 self::$option_list .= '
                 <option value="[mabox_bilibili bvid=&quot;BV号&quot;]">Bilibili视频</option>
               ';
             }
             //公众号解锁
             $wx_unlock = MaBox_Admin::get_config($option, 'wx_unlock');
             if ($wx_unlock === true) {
                 require_once plugin_dir_path(__FILE__) . 'wx_unlock/index.php';
                 MaBox_ShortCode_Wx_Unlock::run();
                 self::$option_list .= '
                 <option value="[mabox_wx_unlock]隐藏内容[/mabox_wx_unlock]">公众号解锁</option>
               ';
             }
             //打赏模块
             $reward = MaBox_Admin::get_config($option, 'reward');
             if ($reward === true) {
                 require_once plugin_dir_path(__FILE__) . 'reward/index.php';
                 MaBox_ShortCode_Reward::run();
                 self::$option_list .= '
                 <option value="[mabox_reward]">打赏按钮</option>
               ';
             }
        }
    } //end
}
