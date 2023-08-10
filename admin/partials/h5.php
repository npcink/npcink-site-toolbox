<?php

/**
 * 权限 辅助
 */
if (!class_exists('MaMi_H5')) {
    class MaMi_H5
    {
        private static $home;
        private static $contact;
        //加载
        public static function run()
        {
            //获取设置选项值
            $config = MaMi_Admin::get_seting('h5');

            //获取选项 - 首页
            $home =  MaMi_Admin::get_config($config, 'home');
            self::$home = $home;

            //获取选项 - 联系
            $contact =  MaMi_Admin::get_config($config, 'contact');
            self::$contact = $contact;

            //获取开关
            $switch =  MaMi_Admin::get_config($home, 'switch');
            if (!$switch) {
                // 定义 REST API 端点（Endpoint）
                add_action('rest_api_init', array(__CLASS__, 'mytheme_register_rest_endpoints'));
            }
        }


        public static function mytheme_register_rest_endpoints()
        {
            $request = 1;
            // Get theme options
            register_rest_route('carbon-fields/v1', 'h5-options', array(
                'methods' => 'GET',
                'callback' => self::mytheme_get_theme_options(),
                //'callback' => array(__CLASS__, 'mytheme_get_theme_options'),
                // 权限控制
                // 'permission_callback' => function () {
                //     return current_user_can('manage_options');
                // },
            ));
        }

        //返回选项值
        public static function mytheme_get_theme_options()
        {
            $fields = [
                'comm_h5_index_tone',
                'comm_h5_index_tone_cat',
                'comm_h5_index_category',
                'comm_h5_single_contact_title',
                'comm_h5_single_contact_one_title',
                'comm_h5_single_contact_one_content',
                'comm_h5_single_contact_two_title',
                'comm_h5_single_contact_two_content',
                'comm_h5_singel_featured_link',
                'comm_h5_singel_featured_logo',
                'comm_h5_singel_featured_msg',
            ];

            $options = [];
            foreach ($fields as $field) {
                $options[$field] = carbon_get_theme_option($field);
                //处理数组
                if ($field == "comm_h5_index_tone") {
                    //创建数组存储数据
                    $arr = [];
                    $sum = carbon_get_theme_option($field);
                    $arr = array_map(function ($obj) {
                        return $obj['id'];
                    }, $sum);
                    //转成数组
                    $intArray = array_map('intval', $arr);
                    $options[$field] = $intArray;
                }
            }
            return $options;
        }

        //返回选项值
        public static function get_h5_options($request)
        {
            $home = self::$home;
            $contact = self::$contact;

            $slide =  MaMi_Admin::get_config($home, 'slide');
            $slide_all =  MaMi_Admin::get_config($home, 'slide_all');
            $more =  MaMi_Admin::get_config($home, 'more');

            $title =  MaMi_Admin::get_config($contact, 'title');
            $title_one =  MaMi_Admin::get_config($contact, 'title_one');
            $content_one =  MaMi_Admin::get_config($contact, 'content_one');
            $title_two =  MaMi_Admin::get_config($contact, 'title_two');
            $content_two =  MaMi_Admin::get_config($contact, 'content_two');
            $brand_link =  MaMi_Admin::get_config($contact, 'brand_link');
            $brand_logo =  MaMi_Admin::get_config($contact, 'brand_logo');
            $introduce =  MaMi_Admin::get_config($contact, 'introduce');
        }
    } //end
}
