<?php
/**
 * 安全选项
 */
if (!class_exists('Magick_Mixtrue_Safe')) {
    class Magick_Mixtrue_Safe
    {

        public function __construct()
        {

        }

        //加载
        public static function run()
        {
            add_action('init', array(__CLASS__, 'load_run'));

        }

        //准备
        public static function load_run()
        {
            //统一登录错误信息
            if (carbon_get_theme_option('cmma_safe_login_errors')) {
                add_filter('login_errors', array(__CLASS__, 'remove_default_login_errors'));
            }

            //修改评论区样式中的管理员信息
            if (carbon_get_theme_option('cmma_safe_comment_style_name')) {
                add_filter('comment_class', array(__CLASS__, 'true_completely_remove_css_class'));
            }

            //从RSS源和网站中删除WordPress版本
            if (carbon_get_theme_option('cmma_safe_head_version')) {
                add_filter('the_generator', array(__CLASS__, 'remove_wp_version'));
            }

        }

        /**
         * 安全 - 登录安全
         */
        /**
         * 作用：覆盖默认登录错误提示信息
         * 来源：https://rudrastyh.com/wordpress/11-security-steps.html
         */
        public static function remove_default_login_errors()
        {
            return '<span class="dashicons dashicons-info-outline" style="
            color: #d63638;
            margin: 0 6px;
        "></span>用户名或密码不正确';
        }

        /**
         * 作用：修改评论区样式中的管理员信息
         * 来源：https://rudrastyh.com/wordpress/11-security-steps.html
         */
        public static function true_completely_remove_css_class($classes)
        {
            foreach ($classes as $key => $class) {
                if (strstr($class, "comment-author-")) {
                    unset($classes[$key]);
                }
            }
            return $classes;
        }

        /**
         * 作用：从RSS源和网站中删除WordPress版本
         * 来源：https://rudrastyh.com/wordpress/11-security-steps.html
         */
        public static function remove_wp_version()
        {
            return '';
        }

    }
}
