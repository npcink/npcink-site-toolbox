<?php

/**
 * 效果：从RSS源和网站中删除WordPress版本
 * 来源：https://rudrastyh.com/wordpress/11-security- steps.html
 * 验证：移除<meta name="generator" content="WordPress 6.5.3" />内容
 * 注：样式/脚本版本号通过 wp_enqueue_style/script 的 $ver 参数控制，
 *     如需全局移除需额外添加 script_loader_tag / style_loader_tag 过滤器
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
