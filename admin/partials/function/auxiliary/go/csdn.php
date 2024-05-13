<?php
/*
 Go中间页跳转 - CSDN
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
    <link rel="stylesheet" type="text/css" href=<?php echo $url . "csdn.css" ?>>
</head>

<body>
    <div id="linkPage" class="link-page">
        <div class="content">
            <div class="loading-item ">
                <div class="flex loading-tip tip2">
                    <!--
                     <img class="loading-img" src="https://csdnimg.cn/release/link/img/warning20201108.png" alt="">
                   -->
                    <div class="loading-img">⚠️</div>
                    <div class="loading-text">请注意您的账号和财产安全</div>
                </div>
                <div class="loading-topic"><span>您即将离开<?php echo $site_name ?>，去往：</span>
                    <a class="loading-color2"><?php echo esc_url($external_url); ?></a>
                </div>
                <div class="flex-end">
                    <a class="loading-btn"  href="<?php echo esc_url($external_url); ?>" target="_self">继续</a>
                </div>
            </div>
        </div>

    </div>
</body>

</html>