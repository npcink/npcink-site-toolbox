<?php

/**
 * 权限 辅助
 */
if (!class_exists('MaMi_Auxiliary')) {
    class MaMi_Auxiliary
    {
        //加载
        public static function run($config)
        {
            //获取选项 - 禁用
            $option =  MaMi_Admin::get_config($config, 'disable');

            //禁用更新
            $renew = MaMi_Admin::get_config($option, 'renew');
            if ($renew) {
                self::run_ban_update();
            }
            //未登录模糊文章内图片
            $no_login_img = MaMi_Admin::get_config($option, 'no_login_img');
            if ($no_login_img) {
                //判断，没有登录
                if (!is_user_logged_in()) {
                    add_action('wp_footer', array(__CLASS__, 'n_yingcang_css'));
                }
            }

            //获取选项 - 功能
            $auxiliary =  MaMi_Admin::get_config($config, 'auxiliary');
            $single_count = MaMi_Admin::get_config($auxiliary, 'single_count');
            if ($single_count) {
                //文章统计页面
                require_once plugin_dir_path(__FILE__) . 'census-single.php';
                //加载文章统计
                Magick_Mixtrue_Census_Single::run();
            }

            //加载商城统计
            $b2_count = MaMi_Admin::get_config($auxiliary, 'b2_count');
            if ($b2_count) {
                //文章统计页面
                require_once plugin_dir_path(__FILE__) . 'census-shop.php';
                Magick_Mixtrue_Census_Shop::run();
            }

            //屏蔽恶意关键词搜索
            $no_malice_key = MaMi_Admin::get_config($auxiliary, 'no_malice_key');

            if ($no_malice_key) {
                $malice_keu_content = MaMi_Admin::get_config($auxiliary, 'malice_keu_content');

                //add_action('template_redirect', array(__CLASS__, 'ytkah_search_ban'));
                add_action('template_redirect', function () use ($malice_keu_content) {
                    return self::ytkah_search_ban($malice_keu_content);
                },);
            }
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

        //屏蔽恶意关键词搜索
        public static function ytkah_search_ban($malice_keu_content)
        {

            if (is_search()) {
                global $wp_query;
                //拿到输入的值
                $ytkah_search_key = $malice_keu_content;
                //$ytkah_search_key =json_decode('"' . $malice_keu_content . '"', false, 512, JSON_UNESCAPED_UNICODE);
                if ($ytkah_search_key) {
                    $ytkah_search_key = str_replace("\r\n", "|", $ytkah_search_key);
                    $BanKey = explode('|', $ytkah_search_key);
                    $S_Key = $wp_query->query_vars;
                    foreach ($BanKey as $Key) {
                        if (stristr($S_Key['s'], $Key) != false) {
                            wp_die('好像搜索了什么不宜展示的东西呢');
                        }
                    }
                }
            }
        }
    } //end
}
