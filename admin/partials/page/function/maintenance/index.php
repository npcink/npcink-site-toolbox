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

//传来的值
//获取设置选项值
$config = MaBox_Admin::get_seting('page');
$function =  MaBox_Admin::get_config($config, 'function');
//标题
$countdown_title =  MaBox_Admin::get_config($function, 'countdown_title');

//图片
$countdown_image =  MaBox_Admin::get_config($function, 'countdown_image');
/*<img src="<?php echo $countdown_image; ?>"/>*/

//内容
$countdown_content =  MaBox_Admin::get_config($function, 'countdown_content');
