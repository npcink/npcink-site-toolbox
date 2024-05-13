<?php
/*
 Go中间页跳转 - 知乎
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
    <link rel="stylesheet" type="text/css" href=<?php echo $url . "zhihu.css" ?>>
</head>

<body>
    <!--
    <div class="logo">
        <a href="https://www.zhihu.com">
            <img src="//static.zhihu.com/static/img/logo_black_trans.png" srcset="//static.zhihu.com/static/img/logo_black_trans@2x.png 2x" alt="知乎">
        </a>
    </div>
    -->

    <div class="wrapper">
        <div class="content">
            <h1>即将离开<?php echo $site_name ?></h1>
            <p class="info">您即将离开<?php echo $site_name ?>，请注意您的帐号和财产安全。</p>
            <p class="link"><?php echo esc_url($external_url); ?></p>
        </div>
        <div class="actions">
            <a class="button" href="<?php echo esc_url($external_url); ?>" target="_self">继续访问</a>
        </div>
    </div>
</body>

</html>