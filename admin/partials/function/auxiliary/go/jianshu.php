<?php
/*
 Go中间页跳转 - 简书
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
    <link rel="stylesheet" type="text/css" href=<?php echo $url . "jianshu.css" ?>>
</head>

<body>
    <div class="_3zKaPtMyr3HfhDiMWyCbjX_0"><!---->
        <div class="_-hCAGG-DBGnLqDZezXfbr_0">即将跳转到外部网站</div>
        <div class="_3ynK7cIQE6ZYP-OGjNuW5P_0">安全性未知，是否继续</div>
        <div class="vo0utWjxXmh0EJk1JpZEo_0">
            <div class="_2kSprqh0pEaoewQz3qpbVt_0"><i class="iconfont ic-PClink">🔗</i></div>
            <div title="<?php echo esc_url($external_url); ?>" class="_2VEbEOHfDtVWiQAJxSIrVi_0"><?php echo esc_url($external_url); ?></div>
        </div>
        <a href="<?php echo esc_url($external_url); ?>" target="_self">
            <div class="_2HKmCX5YkSpBY9XP4yY14K_0">
                <div class="_3OuyzjzFBDdQwRGk08HXHz_0">继续前往</div>
            </div>
        </a>

    </div>
</body>

</html>