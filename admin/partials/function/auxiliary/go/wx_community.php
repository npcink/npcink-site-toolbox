<?php
/*
 Go中间页跳转 - 微信小程序
 */

include plugin_dir_path((__FILE__)) . 'index.php'; // 获取数据
?>
<html lang="zh-CN">

<head>
    <meta charset="utf-8">
    <meta name="robots" content="noindex">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="renderer" content="webkit">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">

    <title><?php echo $site_name ?> - 安全中心</title>
    <link rel="shortcut icon" href="<?php echo $favicon_url ?>" type="image/x-icon">
    <link rel="stylesheet" type="text/css" href=<?php echo $url . "wx_community.css" ?>>
</head>

<body>
    <div class="app_container ">
        <div class="body">
            <div class="container_box">
                <div class=" container_unknow_link"><span class=" ic_unknow"></span>
                    <h1 class="text_area_title">网站安全性未知</h1>
                    <div class="text_area">
                        <p class="text_area_desc">该网站非官方网站，请谨慎访问。</p>
                        <p class="text_area_desc">继续访问：
                            <a href="<?php echo esc_url($external_url); ?>">
                                <?php echo esc_url($external_url); ?>
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</body>

</html>