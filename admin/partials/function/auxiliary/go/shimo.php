<?php
/*
 Go中间页跳转 - 石墨文档
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
    <link rel="stylesheet" type="text/css" href=<?php echo $url . "shimo.css" ?>>
</head>

<body>
    <div id="root">

        <div class=" gtGAjh">
            <p class="logo"></p>
            <div class="modal">
                <h2 class="title">你即将离开<?php echo $site_name ?>，跳转到外部链接</h2>
                <p class="subtitle">请谨慎评估风险并注意保护你的隐私及财产安全</p>
                <p class="link"><?php echo esc_url($external_url); ?></p><br>
                <a href="<?php echo esc_url($external_url); ?>" target="_self">
                    <button class=" gKgaxE  button" type="default">继续访问</button>
                </a>

            </div>
        </div>
    </div>
</body>

</html>