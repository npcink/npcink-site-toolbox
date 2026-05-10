<?php

/**
 * 效果：从RSS源和网站中删除WordPress版本
 * 来源：https://rudrastyh.com/wordpress/11-security-steps.html
 * 验证：移除<meta name="generator" content="WordPress 6.5.3" />内容
 * TODO:怎么移除加载的样式中的版本号信息
 */
if (!class_exists('MaBox_Remove_WP_Version')) {
    class MaBox_Remove_WP_Version
    {
        /**
         * 执行代码
         */
        public static  function run()
        {
            add_filter('the_generator', array(__CLASS__, 'remove_wp_version'));
        }

        public static function remove_wp_version()
        {
            return '';
        }
    }
}
