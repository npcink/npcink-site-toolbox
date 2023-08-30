<?php

/**
 * 权限 辅助
 */
if (!class_exists('MaMi_Auxiliary')) {
    class MaMi_Auxiliary
    {

        private static $auxiliary; //辅助功能
        //加载
        public static function run()
        {
            //加载文件
            self::load();
            //获取设置选项值
            $config = MaMi_Admin::get_seting('authority');

            //禁用
            $disable =  MaMi_Admin::get_config($config, 'disable');

            //辅助功能
            $auxiliary =  MaMi_Admin::get_config($config, 'auxiliary');

            //微信生成小程序跳转链接
            $wx_xcx =  MaMi_Admin::get_config($config, 'wx_xcx');
            MaMi_Wx_Xcx::run($wx_xcx);

            //B2 功能选项
            $b2 =  MaMi_Admin::get_config($config, 'b2');
            Magick_Mixtrue_Census_Shop::run($b2);


            //禁用更新
            $renew = MaMi_Admin::get_config($disable, 'renew');
            if ($renew) {
                self::run_ban_update();
            }
            //未登录模糊文章内图片
            $no_login_img = MaMi_Admin::get_config($disable, 'no_login_img');
            if ($no_login_img) {
                add_action('wp_footer', array(__CLASS__, 'n_yingcang_css'));
            }

            //获取选项 - 功能
            $auxiliary =  MaMi_Admin::get_config($config, 'auxiliary');
            self::$auxiliary = $auxiliary;

            $single_count = MaMi_Admin::get_config($auxiliary, 'single_count');
            if ($single_count) {

                //加载文章统计
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
        }

        //加载文件
        public static function load()
        {
            //文章统计页面
            require_once plugin_dir_path(__FILE__) . 'other/census-single.php';
            //登录验证码
            require_once plugin_dir_path(__FILE__) . 'other/login_verify.php';
            //商城统计页面
            require_once plugin_dir_path(__FILE__) . 'other/census-shop.php';
            //加载微信小程序链接生成
            require_once plugin_dir_path(__FILE__) . 'other/wx-xcx.php';
        }

        /**
         * 效果：禁用更新
         * 来源：https://www.npc.ink/15932.html
         */
        public static function run_ban_update()
        {
            remove_action('init', 'wp_schedule_update_checks'); // 关闭更新检查定时作业
            wp_clear_scheduled_hook('wp_version_check'); // 移除已有的版本检查定时作业
            wp_clear_scheduled_hook('wp_update_plugins'); // 移除已有的插件更新定时作业
            wp_clear_scheduled_hook('wp_update_themes'); // 移除已有的主题更新定时作业
            wp_clear_scheduled_hook('wp_maybe_auto_update'); // 移除已有的自动更新定时作业
            add_filter('automatic_updater_disabled', '__return_true'); // 彻底关闭自动更新
            remove_action('admin_init', '_maybe_update_core'); // 移除后台内核更新检查
            remove_action('load-plugins.php', 'wp_update_plugins'); // 移除后台插件更新检查
            remove_action('load-update.php', 'wp_update_plugins');
            remove_action('load-update-core.php', 'wp_update_plugins');
            remove_action('admin_init', '_maybe_update_plugins');
            remove_action('load-themes.php', 'wp_update_themes'); // 移除后台主题更新检查
            remove_action('load-update.php', 'wp_update_themes');
            remove_action('load-update-core.php', 'wp_update_themes');
            remove_action('admin_init', '_maybe_update_themes');
        }



        /**
         * 未登录模糊文章内图片
         */
        public static function n_yingcang_css()
        {
            //判断是否登录
            if (!is_user_logged_in()) {
                echo '<style>
                /*仅模糊文章内图片*/
                .entry-content img {
                -webkit-filter: blur(10px)!important;
                  -moz-filter: blur(10px)!important;
                  -ms-filter: blur(10px)!important;
                  filter: blur(6px)!important;}
                  .entry-content img:before{
                    content:"登录可见";
                  }
                  </style>';
            }
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

       
       
    } //end
}
