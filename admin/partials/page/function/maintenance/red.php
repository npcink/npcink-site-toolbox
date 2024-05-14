<?php
/*
 暂停页模版
 */

include plugin_dir_path((__FILE__)) . 'index.php'; // 获取数据
?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
    <?php wp_body_open(); ?>

    <body>


        <!--复制开始-->
        <!--
	版本：1.0
	整理：Muze
	帮助：https://www.npc.ink
-->



<!--
        <h2 class="n-title main"></h2>
-->
        <p class="n-meat main">升级维护中，过一会再来吧！</p>
        <p class="n-description main"><span><?php echo $site_name; ?></span><br><?php echo $description; ?></p>

        <style type="text/css">
            body {
                background-color: #b52424;
                margin-top: 20vh;
            }

            .main {
                text-align: center;
                padding-top: 10px;
                color: #fff;
                letter-spacing: 20px;
            }

            .n-title {
                font-size: 4em;
                margin-bottom: 5px;
            }

            .n-meat {
                font-size: 2em;
            }

            .n-description {
                line-height: 2em;
                margin-top: 150px;
            }

            .n-description span {
                font-size: 32px;
                font-weight: bold;
            }
        </style>


    </body>

</html>