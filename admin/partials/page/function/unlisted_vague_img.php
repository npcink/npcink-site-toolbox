<?php

defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Unlisted_Vague_Img')) {
    class Npcink_Toolbox_Unlisted_Vague_Img implements Npcink_Toolbox_Module_Interface
    {
        public static function run($config = array())
        {
            add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_style'));
        }

        public static function enqueue_style()
        {
            if (!Npcink_Toolbox_Helpers::is_logged_in()) {
                wp_register_style('npcink-site-toolbox-unlisted-images', false, array(), NPCINK_SITE_TOOLBOX_VERSION);
                wp_enqueue_style('npcink-site-toolbox-unlisted-images');
                wp_add_inline_style(
                    'npcink-site-toolbox-unlisted-images',
                    '.entry-content img{-webkit-filter:blur(10px)!important;-moz-filter:blur(10px)!important;-ms-filter:blur(10px)!important;filter:blur(6px)!important}.entry-content img:before{content:"登录可见"}'
                );
            }
        }
    }
}
