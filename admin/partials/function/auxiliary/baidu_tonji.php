<?php
defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Baidu_Tonji')) {
    class Npcink_Toolbox_Baidu_Tonji implements Npcink_Toolbox_Module_Interface
    {
        private static $option;

        public static function run($config = array())
        {
            self::$option = isset($config['baidu_tonji']) && is_string($config['baidu_tonji'])
                ? $config['baidu_tonji']
                : '';
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_script'));
        }

        public static function enqueue_script()
        {
            if (!empty(self::$option)) {
                wp_enqueue_script(
                    'npcink-site-toolbox-baidu-tongji',
                    'https://hm.baidu.com/hm.js?' . rawurlencode(self::$option),
                    array(),
                    NPCINK_SITE_TOOLBOX_VERSION,
                    true
                );
            }
        }
    }
}
