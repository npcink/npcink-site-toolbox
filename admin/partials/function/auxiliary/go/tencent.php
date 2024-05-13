<?php
/*
 Go中间页跳转 - 腾讯
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
  <link rel="stylesheet" type="text/css" href=<?php echo $url . "tencent.css" ?>>
</head>

<body>
  <div class="cdc-external-link-page">
    <div class="mod-external-link">
      <div class="mod-external-link-logo"></div>
      <div class="mod-external-link-content">
        <div class="mod-external-link-main">
          <div class="mod-external-link-title">您即将离开<?php echo $site_name ?>，请注意您的账号财产安全</div>
          <div class="mod-external-link-address"><?php echo esc_url($external_url); ?></div>
        </div>
        <div class="mod-external-link-btn"><a href="<?php echo esc_url($external_url); ?>" target="_self">继续访问</a></div>
      </div>
    </div>
  </div>
</body>

</html>