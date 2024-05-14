<?php
//暂停维护页
//网站名：
$site_name = get_bloginfo('name');
// 获取网站描述
$description = get_bloginfo('description');

//ico图标
$favicon_url = get_site_icon_url();

//准备路径
$url_css = plugin_dir_url(__FILE__) . "css/";
$url_image = plugin_dir_url(__FILE__) . "image/";
