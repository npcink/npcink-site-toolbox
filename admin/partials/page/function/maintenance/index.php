<?php
//暂停维护页

//网站名：
$site_name = get_bloginfo('name');

// 获取网站描述
$description = get_bloginfo('description');



//ico图标
$favicon_url = get_site_icon_url();

//准备资源路径
$file_path = plugin_dir_path((__FILE__));

//准备资源链接
$file_url = plugin_dir_url(__FILE__);

//传来的值

//获取设置选项值
$config = MaBox_Admin::get_seting('page');
$function =  MaBox_Admin::get_config($config, 'function');

//时间
$countdown_data = MaBox_Admin::get_config($function, 'countdown');

//组合成结束时间
$countdown = $countdown_data[1] . ":00";

//标题
$countdown_title =  MaBox_Admin::get_config($function, 'countdown_title');

//标题默认值
// $countdown_title = isset($countdown_title) && !empty($countdown_title) ? $countdown_title : "升级维护中";
if (isset($countdown_title) && empty($countdown_title)) {
    $countdown_title = '升级维护中';
}

//网页标题
$page_title = $countdown_title . ' - ' . $site_name;

//图片
$countdown_image =  MaBox_Admin::get_config($function, 'countdown_image');
/*<img src="<?php echo $countdown_image; ?>"/>*/

//内容
$countdown_content_data =  MaBox_Admin::get_config($function, 'countdown_content');

//转义
$countdown_content = html_entity_decode($countdown_content_data);

//内容默认值
if (empty($countdown_content)) {
    $countdown_content = '
    <h5> 抱歉，我们的网站正在维护中...</h5> 
    <p> 
    请倒计时结束后再回来，我们准备了全新的内容哦！
    </p>
    ';
}
