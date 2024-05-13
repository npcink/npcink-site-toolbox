<?php
//优化 站点
if (!class_exists('MaMi_Optimize_Site')) {
    class MaMi_Optimize_Site
    {
        //加载
        public static function run($config)
        {

            //获取选项
            $option =  MaMi_Admin::get_config($config, 'site');

            //禁止网站title中的 “-” 被转义
            $no_escape = MaMi_Admin::get_config($option, 'no_escape');
            if ($no_escape) {
                add_filter('run_wptexturize', '__return_false');
            };

            //禁用自动更新
            $renew = MaMi_Admin::get_config($option, 'renew');
            if ($renew === true) {
                //禁用自动更新
                require_once plugin_dir_path(__FILE__) . 'ban_update.php';
                Npcink_Ban_Update::run();
            }


            //从RSS源和网站中删除WordPress版本
            $remove_RSS_version = MaMi_Admin::get_config($option, 'remove_RSS_version');
            if ($remove_RSS_version) {
                add_filter('the_generator', array(__CLASS__, 'remove_wp_version'));
            }
        }
        /**
         * 作用：从RSS源和网站中删除WordPress版本
         * 来源：https://rudrastyh.com/wordpress/11-security-steps.html
         * 验证：移除<meta name="generator" content="WordPress 6.5.3" />内容
         * TODO:怎么移除加载的样式中的版本号信息
         */
        public static function remove_wp_version()
        {
            return '';
        }
    }
}
