<?php

/**
 * 微信小程序链接引导模版
 */
$c5_file = 'image/icon_c5_wx.png';
$c5 = plugin_dir_url(__FILE__) . $c5_file;

$logo_file = 'image/icon_wx_logo.png';
$logo = plugin_dir_url(__FILE__) . $logo_file;

$css = plugin_dir_url(__FILE__) . 'template_page.css';

//小程序链接
$link = MaMi_Function_Wx_Xcx_Link::add_hello_header();

//当前页面链接
$page_url = get_permalink();

//$site_url = home_url(); // 获取当前网站的URL

//选项中的网址
$site = MaMi_Function_Wx_Xcx_Link::get_h5_options_site();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo  get_the_title(); ?></title>

</head>

<body>

    <link rel="stylesheet" type="text/css" href="<?php echo  $css ?>">
    <div id="pc" class="content">
        <?php

        echo apply_filters('the_content', get_post_field('post_content', get_the_ID()));
        ?>

    </div>
    <div id="mobile" class="mobile">
        <div class="layout">
            <div class="img-box">
                <img class="heand_img" src="<?php echo $logo ?>">
            </div>
            <a class="title"></a>
            <div style="margin-top: 4px;flex-direction: row; margin-top: 8px; overflow:hidden; display: flex;justify-content: center; align-items: center;">
                <img class="icom_img" src="<?php echo $c5 ?>">
                <a class="icon_text">微信</a>
            </div>
        </div>
        <div class="layout1">
            <button class="jumpBtn" type="button" onclick="onJumpWxBtn()">前往微信打开</button>
            <div style="margin-top: 24px; margin-bottom: 100px;overflow:hidden">
                <a class="text_g">无法打开时，可使用默认浏览器打开。</a>
                <a id="copyButton" data-text=<?php echo $page_url ?> class="text_link">复制链接</a>
            </div>
        </div>
    </div>
    <script>

        //判断是否是手机
        function isMobile() {
            const userAgent = navigator.userAgent.toLowerCase();
            const mobileKeywords = ['android', 'iphone', 'ipod', 'ipad', 'windows phone'];

            for (let keyword of mobileKeywords) {
                if (userAgent.indexOf(keyword) !== -1) {
                    return true;
                }
            }

            return false;
        }

        /*
        复制按钮
        */
        const copyButton = document.getElementById('copyButton');
        copyButton.addEventListener('click', function() {
            const textToCopy = copyButton.getAttribute('data-text');
            navigator.clipboard.writeText(textToCopy)
                .then(function() {
                    alert("复制成功");
                })
                .catch(function(error) {
                    console.log('复制文本失败:', error);
                });
        });


        //获取位置
        const pcElement = document.querySelector('#pc');
        const mobileElement = document.querySelector('#mobile');

        //判断是否是微信小程序
        const switchXcx = () => {
            if (window.__wxjs_environment === 'miniprogram') {
                //选项中的网址
                let site = <?php echo json_encode($site); ?>;
                // 当前环境是微信小程序
                console.log('当前环境是微信小程序');
                //TODO:如何在小程序中打开小程序的页面
                window.location.href = site; //跳转外部文章页面
            } else {
                // 当前环境不是微信小程序
                //onJumpWxBtn(); //跳转微信小程序
                //选项中的网址
                let link = <?php echo json_encode($link); ?>;
                window.location.href = link; //跳转外部文章页面
            }
        }

        //判断
        if (isMobile()) {
            console.log('当前是手机');
            pcElement.style.display = 'none';
            //判断是否是微信小程序
            switchXcx();

        } else {
            console.log('当前是电脑');
            mobileElement.style.display = 'none';


        }
    </script>

</body>

</html>