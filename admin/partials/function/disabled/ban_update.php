<?php

/**
 * 效果：禁用WordPress更新
 * 来源：https://www.npc.ink/15932.html
 */
if (!class_exists('Npcink_Ban_Update')) {
    class Npcink_Ban_Update
    {
        /**
         * 执行代码
         */
        public static  function run()
        {
            self::ban_update();
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
    }
}
