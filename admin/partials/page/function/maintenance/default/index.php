<?php

defined('ABSPATH') || exit;

//默认带图
include plugin_dir_path((__FILE__)) . '../index.php'; // 获取数据

$npcink_site_toolbox_logo = $npcink_site_toolbox_file_url . 'default/tips.svg';
wp_die(
    '<div style="text-align:center">

    <img src="' . esc_url($npcink_site_toolbox_logo) . '" alt="' . esc_attr(self::$blogname) . '" /><br /><br />' . wp_kses_post($npcink_site_toolbox_countdown_content) . '</div>',
    esc_html($npcink_site_toolbox_page_title),
    array('response' => '503')
);
