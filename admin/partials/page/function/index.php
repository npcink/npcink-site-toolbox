<?php

/**
 * 页面 功能
 */

if (!class_exists('Npcink_Page_Function')) {
    class Npcink_Page_Function
    {
        public static function run($option)
        {
            //首图作特色图
            $first_picture = MaBox_Admin::get_config($option, 'first_picture');
            if ($first_picture === true) {
                require_once plugin_dir_path(__FILE__) . 'first_picture.php';
                Npcink_Single_First_Picture::run();
            }
            //文章关键词自动添加内链链接代码
            $add_inks = MaBox_Admin::get_config($option, 'add_inks');
            if ($add_inks === true) {
                require_once plugin_dir_path(__FILE__) . 'single_keyword_add_link.php';
                Npcink_Single_Keyword_Add_Link::run();
            }
            //去除文章内的超链接，可复原
            $remove_single_link = MaBox_Admin::get_config($option, 'remove_single_link');
            if ($remove_single_link === true) {
                require_once plugin_dir_path(__FILE__) . 'single_remove_link.php';
                Npcink_Single_Remove_Link::run();
            }

            //圆角彩色背景标签云
            $color_tag = MaBox_Admin::get_config($option, 'color_tag');
            if ($color_tag === true) {
                require_once plugin_dir_path(__FILE__) . 'color_tags.php';
                Npcink_Page_Color_Tags::run();
            }

            //文章末尾添加最后更新时间
            $add_last_update = MaBox_Admin::get_config($option, 'add_last_update');
            if ($add_last_update === true) {
                require_once plugin_dir_path(__FILE__) . 'add_article_update_time.php';
                Npcink_Single_Add_Last_Updated_Date::run();
            }

            //未登录模糊文章内图片
            $no_login_img = MaBox_Admin::get_config($option, 'no_login_img');
            if ($no_login_img === true) {
                //未登录模糊文章内图片
                require_once plugin_dir_path(__FILE__) . 'unlisted_vague_img.php';
                Npcink_Unlisted_Vague_Img::run();
            }

            //跳转中间页
            $go_middle = MaBox_Admin::get_config($option, 'go_middle');
            if ($go_middle !== false) {
                require_once plugin_dir_path(__FILE__) . 'jump_middle_page.php';
                Npcink_Jump_Middle_Page::run($go_middle);
            }




            //维护提示
            $maintenance_tips = MaBox_Admin::get_config($option, 'maintenance_tips');

            //倒计时时间段
            $countdown = MaBox_Admin::get_config($option, 'countdown');

            //选项非关闭
            if ($maintenance_tips !== false) {
                //若时间段不存在，给默认值
                if (count($countdown) !== 2) {
                    $countdown = array('2024-01-01 00:00:00', '2024-01-01 23:59:59');
                }

                //判断当前时间，是否在时间段中
                $result = self::isCurrentTimeInRange($countdown);

                //当前时间不在此时间段，则跳过
                if ($result === true) {
                    require_once plugin_dir_path(__FILE__) . 'maintenance_tips.php';
                    Npcink_Maintenance_Tips::run($maintenance_tips);
                }
            }

            //添加分享按钮
            $share = MaBox_Admin::get_config($option, 'share');
            if ($share !== false) {
                require_once plugin_dir_path(__FILE__) . 'share/index.php';
                Npcink_Public_Add_Share::run($option);
            }

           

            //添加简体繁体切换按钮
            $switch_lang_jf = MaBox_Admin::get_config($option, 'switch_lang_jf');
            if ($switch_lang_jf !== false) {
                require_once plugin_dir_path(__FILE__) . 'lang_jf/index.php';
                Npcink_Single_Lang_Jf::run();
            }

           
        }

        //计算时间
        public static function isCurrentTimeInRange($range)
        {
            $start = strtotime($range[0]);
            $end = strtotime($range[1]);
            $now = time();

            return ($now >= $start && $now <= $end);
        }
    }
}
