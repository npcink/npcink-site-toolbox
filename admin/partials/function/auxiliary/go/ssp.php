<?php
/*
 Go中间页跳转 - 少数派
 */

include plugin_dir_path((__FILE__)) . 'index.php'; // 获取数据
$url = $url . "ssp.css";

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
    <link rel="stylesheet" type="text/css" href=<?php echo $url ?>>
</head>

<body>
    <!--
        来源：https://sspai.com/link?target=https%3A%2F%2Fgithub.com%2Fkkkgo%2FLTSC-Add-MicrosoftStore
    -->
    <div class="page__link__wrapper">
        <div class="page__link">

            <?php
            if (!empty($favicon_url)) {
            ?>
                <div class="page__header">
                    <img src="<?php echo $favicon_url ?>" alt="即将离开<?php echo $site_name ?>" width="128">
                </div>
            <?php
            }
            ?>

            <div class="page__title">即将离开<?php echo $site_name ?></div>
            <p class="page__desc">你访问的网站可能包含未知的安全风险，如需继续访问，请手动复制链接访问，并注意保护帐号和隐私信息</p>
            <div id="target" class="page__target">
                <span>
                    <a href="<?php echo esc_url($external_url); ?>" target="_self">
                        <?php echo  $external_url ?>
                    </a>
                </span>
            </div>
            <div class="btn__wrapper">
                <a href="<?php echo esc_url($external_url); ?>" target="_self">
                    <button class="btn">继续前往</button>
                </a>
            </div>
        </div>
</body>

</html>