<?php

/**
 * 权限 辅助
 */
if (!class_exists('MaMi_Auxiliary_Index')) {
    class MaMi_Auxiliary_Index
    {
        private static $auxiliary; //辅助功能
        //加载

        public static function run($auxiliary)
        {
            //加载文件
            self::load();

            //获取选项
            self::$auxiliary = $auxiliary;

            //加载文章统计
            $single_count = MaMi_Admin::get_config($auxiliary, 'single_count');
            if ($single_count) {
                Magick_Mixtrue_Census_Single::run();
            }



            //屏蔽恶意关键词搜索
            $no_malice_key = MaMi_Admin::get_config($auxiliary, 'no_malice_key');
            if ($no_malice_key) {
                add_action('template_redirect', array(__CLASS__, 'ytkah_search_ban'));
            }

            //登录验证码
            $login_code = MaMi_Admin::get_config($auxiliary, 'login_code');
            if ($login_code !== "false") {
                MaMi_Login_Verify::run($login_code);
            }

            //跳转中间页
            $go_middle = MaMi_Admin::get_config($auxiliary, 'go_middle');
            if ($go_middle !== "false") {
                add_action('init', array(__CLASS__, 'go_to_new_link'));
            }
        }
        //加载文件
        public static function load()
        {
            //文章统计页面
            require_once plugin_dir_path(__FILE__) . '/block/census-single.php';

            //登录验证码
            require_once plugin_dir_path(__FILE__) . '/block/login_verify.php';
        }

        //屏蔽恶意关键词搜索
        public static function ytkah_search_ban()
        {
            $malice_keu_content = MaMi_Admin::get_config(self::$auxiliary, 'malice_keu_content');

            if (is_search()) {
                global $wp_query;
                //拿到输入的值
                $ytkah_search_key = $malice_keu_content;
                if ($ytkah_search_key) {
                    $ytkah_search_key = str_replace("\n", "|", $ytkah_search_key);
                    $BanKey = explode('|', $ytkah_search_key);
                    $S_Key = $wp_query->query_vars;
                    foreach ($BanKey as $Key) {
                        if (stristr($S_Key['s'], $Key) != false) {
                            $message = '搜索内容包含敏感词，请换个方式搜索';
                            $message = $message . MaMi_Admin::blank_button();
                            wp_die($message);
                        }
                    }
                }
            }
        }

        /**
         * 跳转中间页
         */
        public static function go_to_new_link()
        {
            // 检查是否已经存在自定义页面
            $page_slug = 'gotos'; //链接
            $config = 'my_custom_plugin_page_zhihu-aa'; //唯一标识
            $existing_page_id = get_option($config);

            if ($existing_page_id) {
                return; // 页面已经存在，不执行后续操作
            }

            // 创建新页面
            $page_title = '禁止删除：外链跳转中间页专用 - 知乎(编辑此页无效果)';
            $page_content = 'hello';

            $page = array(
                'post_title'   => $page_title,
                'post_content' => $page_content,
                'post_status'  => 'publish',
                'post_type'    => 'page',
                'post_name'    => $page_slug
            );

            // 添加页面，并获取页面ID
            $page_id = wp_insert_post($page);

            // 设置页面模板为无效模板，以避免外部访问该页面
            update_post_meta($page_id, '_wp_page_template', 'invalid-template.php');

            // 隐藏页面在页面管理中的显示选项
            $page_data = array(
                'ID'          => $page_id,
                'post_type'   => 'page',
                'post_status' => 'publish'
            );
            wp_update_post($page_data);

            // 存储页面ID
            update_option($config, $page_id);
        }
    }
}
