<?php
//外链跳转中间页
//拿到的链接：
$external_url = isset($_GET['url']) ? $_GET['url'] : '暂无';
//网站名：
$site_name = get_bloginfo('name');


//ico图标
$favicon_url = get_site_icon_url();

//准备路径
$url = plugin_dir_url(__FILE__) ;
