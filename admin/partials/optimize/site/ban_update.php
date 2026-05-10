<?php

/**
 * 效果：禁用WordPress更新
 * 来源：https://www.npc.ink/15932.html
 * 参考：https://m.wpjam.com/m/disable-wordpress-auto-update/
 */
if (!class_exists('MaBox_Ban_Update')) {
    class MaBox_Ban_Update
    {
        /**
         * 执行代码
         */
        public static  function run()
        {
            self::ban_update();
            // 禁止主题自动检查更新
            add_filter('http_request_args', array(__CLASS__, 'disable_theme_updates'), 10, 2);
            // 隐藏主题更新提示
            add_action('admin_init', array(__CLASS__, 'disable_theme_update_notification'));

            // 禁止插件自动检查更新
            add_filter('site_transient_update_plugins', array(__CLASS__, 'disable_plugin_updates'));
            // 隐藏插件更新提示
            add_action('admin_init', array(__CLASS__, 'disable_plugin_update_notification'));

            // 禁用 WordPress 更新检查
            add_filter('pre_site_transient_update_core', function ($a) {
                return null;
            });
        }

        /**
         * 功能代码
         */
        public static function ban_update()
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

        // 隐藏主题更新提示
        public static function disable_theme_updates($r, $url)
        {
            if (0 !== strpos($url, 'https://api.wordpress.org/themes/update-check')) {
                return $r; // 不是主题更新检查请求，直接返回
            }

            $themes = json_decode($r['body']['themes']);
            $active_theme = get_option('stylesheet');

            // 移除当前活动主题的更新信息
            unset($themes->themes->$active_theme);

            $r['body']['themes'] = json_encode($themes);
            return $r;
        }

        public static function disable_theme_update_notification()
        {
            remove_action('load-update-core.php', 'wp_update_themes');
            add_filter('pre_site_transient_update_themes', '__return_null');
        }

        // 隐藏插件更新提示
        public static function disable_plugin_updates($value)
        {
            unset($value->response); // 移除所有插件的更新信息
            return $value;
        }

        public static  function disable_plugin_update_notification()
        {
            remove_action('load-update-core.php', 'wp_update_plugins');
            add_filter('pre_site_transient_update_plugins', '__return_null');
        }
    }
}
