<?php
/*
 暂停页模版 - 紫色期待
 */

include plugin_dir_path((__FILE__)) . '../index.php'; // 获取数据
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
  <meta charset="<?php bloginfo('charset'); ?>" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title><?php echo $page_title; ?></title>
</head>

<body <?php body_class(); ?>>
  <?php wp_body_open(); ?>


  <!--复制开始-->
  <!--
	版本：1.0
	整理：Muze
	帮助：https://www.npc.ink/17635.html
-->
  <!--载入CSS样式-->
  <link href="<?php echo $file_url . "purple/style.css" ?>" rel="stylesheet" type="text/css" />
  <div class="main">
    <div class="waitimg">
      <!--倒计时开始-->
      <div class="box">
        <?php include $file_path . 'countdown.php'; ?>
      </div>

      <!--倒计时结束-->
      <div class="beian"> <?php echo $countdown_content; ?></div>
      <style>
        .waitimg {

          background: url(<?php echo $file_url . '/purple/img/wait.png' ?>) center no-repeat;

        }

        @media screen and (min-width: 750px) {
          .waitimg {
            background: url(<?php echo $file_url . '/purple/img/wait1.png' ?>) center no-repeat;
          }
        }

        .box {
          display: flex;
          justify-content: center;
          align-items: center;
          padding-top: 60vh;
          color: #fff;
        }

        /** 时间颜色*/
        .countdown-desc {
          color: #fff;
        }

        .beian * {
          color: #fff;
        }
      </style>
    </div>
  </div>



</body>

</html>