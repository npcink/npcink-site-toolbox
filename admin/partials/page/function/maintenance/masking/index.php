<?php
/*
 暂停页模版 - 高级遮罩 zaxu 
 */

include plugin_dir_path((__FILE__)) . '../index.php'; // 获取数据

//准备图片 1920 1080
$img_url = $countdown_image ? $countdown_image : $file_url . './masking/masking_1920.jpg';

?>
<!doctype html>
<html <?php language_attributes(); ?>>

<head>
    <meta charset="<?php bloginfo('charset'); ?>" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php echo $page_title; ?></title>
</head>

<body class="maintenance-page" <?php body_class(); ?>>
    <?php wp_body_open(); ?>



    <link href="<?php echo $file_url . "masking/style.css" ?>" rel="stylesheet" type="text/css" />
    <script type="text/javascript" src="<?php echo $file_url . "Countdown.js" ?>"></script>
    <picture class="pending-bg-img">
        <img src="<?php echo $img_url ?>" alt="<?php echo $countdown_title ?>">
    </picture>
    <section class="site-main-container">
        <div class="site-carry">
            <div class="site-content">
                <div>
                    <main>
                        <article>
                            <div>
                                <section class="pending-caption ">
                                    <h1><?php echo $countdown_title ?></h1>
                                    <h2><?php echo $countdown_content ?></h2>
                                </section>
                                <?php include $file_path . 'countdown.php'; ?>
                            </div>
                        </article>
                    </main>
                </div>
            </div>
        </div>
    </section>


</body>

</html>