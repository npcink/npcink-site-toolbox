<?php

defined('ABSPATH') || exit;

//暂停维护页

//网站名：
$npcink_site_toolbox_site_name = get_bloginfo('name');

//准备资源链接
$npcink_site_toolbox_file_url = plugin_dir_url(__FILE__);

//传来的值

//获取设置选项值
$npcink_site_toolbox_config = Npcink_Toolbox_Admin::get_seting('page');
$npcink_site_toolbox_function = Npcink_Toolbox_Admin::get_config($npcink_site_toolbox_config, 'function');

//时间
$npcink_site_toolbox_countdown_data = Npcink_Toolbox_Admin::get_config($npcink_site_toolbox_function, 'countdown', array());

//组合成结束时间
$npcink_site_toolbox_countdown_end = is_array($npcink_site_toolbox_countdown_data)
    && isset($npcink_site_toolbox_countdown_data[1])
    && is_string($npcink_site_toolbox_countdown_data[1])
        ? trim($npcink_site_toolbox_countdown_data[1])
        : '';
$npcink_site_toolbox_countdown = '' !== $npcink_site_toolbox_countdown_end ? $npcink_site_toolbox_countdown_end . ':00' : '';

//标题
$npcink_site_toolbox_countdown_title = Npcink_Toolbox_Admin::get_config($npcink_site_toolbox_function, 'countdown_title');

//标题默认值
// $npcink_site_toolbox_countdown_title = isset($npcink_site_toolbox_countdown_title) && !empty($npcink_site_toolbox_countdown_title) ? $npcink_site_toolbox_countdown_title : "升级维护中";
if (isset($npcink_site_toolbox_countdown_title) && empty($npcink_site_toolbox_countdown_title)) {
    $npcink_site_toolbox_countdown_title = '升级维护中';
}

//网页标题
$npcink_site_toolbox_page_title = $npcink_site_toolbox_countdown_title . ' - ' . $npcink_site_toolbox_site_name;

//内容
$npcink_site_toolbox_countdown_content_data = Npcink_Toolbox_Admin::get_config($npcink_site_toolbox_function, 'countdown_content');

//转义
$npcink_site_toolbox_countdown_content = html_entity_decode($npcink_site_toolbox_countdown_content_data);

//内容默认值
if (empty($npcink_site_toolbox_countdown_content)) {
    $npcink_site_toolbox_countdown_content = '
    <h5> 抱歉，我们的网站正在维护中...</h5> 
    <p> 
    请倒计时结束后再回来，我们准备了全新的内容哦！
    </p>
    ';
}
