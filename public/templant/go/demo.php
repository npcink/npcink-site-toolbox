<?php
/*
 Go中间页跳转 - 演示
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
    <style>
        .box {
            text-align: center;
            margin-top: 30px;
            margin-bottom: 30px;
            font-weight: 400;
        }
    </style>
</head>

<body>
    <div class="box">
        您即将离开<?php echo $site_name ?>，
        <a href="<?php echo esc_url($external_url); ?>" target="_self">继续前往</a>
    </div>
</body>

</html>