<?php
/*
 暂停页模版 灯光聚焦
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

    <!--
        来源：https://www.npc.ink/5239.html
    -->

    <div class="container">
        <div class="lamp-holder"></div>
        <div class="lamp"></div>
        <div class="light"></div>
        <div class="wall"></div>
        <div class="desk"></div>
        <div class="screen">
            <div class="sidebar">
                <div class="h1"></div>
                <div class="h2"></div>
            </div>
            <div class="main">
                <div class="search"></div>
                <div class="pen pen1"></div>
                <div class="pen pen2"></div>
                <div class="pen pen3"></div>
                <div class="pen pen4"></div>
                <div class="pen pen5"></div>
                <div class="pen pen6"></div>
                <div class="pen pen7"></div>
                <div class="pen pen8"></div>
                <div class="pen pen9"></div>
            </div>
        </div>
        <div class="mouse"></div>
        <div class="keyboard"></div>
        <div class="cup">
            <div class="tea"></div>
            <div class="steam"></div>
        </div>
        <div class="plant">
            <div class="leaf1"></div>
            <div class="leaf2"></div>
            <div class="leaf3"></div>
        </div>
        <div class="text">
            <h3>
                <?php echo $countdown_title; ?>
            </h3>

            <div class="content"> <?php echo $countdown_content; ?></div>

            <?php include $file_path . 'countdown/index.php'; ?>
            <style>
                body {
                    height: 70vh !important;
                }

                .countdown-container {
                    margin-top: 20rem;
                }

                .text {
                    padding-top: 20px;
                }

                .text p {
                    margin-top: .2em !important;
                }

                .text .content {
                    margin-top: 1rem;
                }
            </style>
            <!--载入倒计时结束-->
        </div>
    </div>

    <!--载入CSS样式-->
    <link href="<?php echo $file_url  . "lighting/style.css" ?>" rel="stylesheet" type="text/css" />

</body>

</html>