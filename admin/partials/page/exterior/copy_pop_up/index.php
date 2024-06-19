<?php

/**
 * 效果：复制时进行弹窗提示
 * 来源1：https://www.dqzboy.com/4672.html
 */
if (!class_exists('Npcink_Page_Copy_Pop_Up')) {
    class Npcink_Page_Copy_Pop_Up
    {

        public static function run($config)
        {
            //原生
            if ($config === "concise") {
                add_action('wp_footer', array(__CLASS__, 'concise'), 100);
            }
            //通用圆角
            if ($config === "sweetalert") {
                add_action('wp_enqueue_scripts', array(__CLASS__, 'add_page_sweetalert'));
                add_action('wp_footer', array(__CLASS__, 'jiub'), 100);
            }
        }

        //原生
        public static function concise()
        {
?>

            <script type="text/javascript">
                document.body.oncopy = function() {
                    alert('复制成功！若要转载请务必保留原文链接，谢谢合作！');
                }
            </script>

<?php
        }
        //复制成功提醒  
        public static function jiub()
        {
            ?>
            <script>
            document.addEventListener('copy', function (event) {
                // 获取复制的文本内容
                var copiedText = window.getSelection().toString();
    
                // 在页面上显示提示信息
                swal("复制成功！", "转载请务必保留原文链接，申明来源，谢谢合作！！", "success");
    
                // 将复制的文本内容写入剪贴板
                navigator.clipboard.writeText(copiedText)
                    .then(function () {
                        //console.log('已成功将文本写入剪贴板！');
                    })
                    .catch(function (error) {
                        console.error('写入剪贴板失败:', error);
                    });
    
                // 阻止默认的复制行为，可以选择阻止或者允许，默认为允许
                // event.preventDefault();
            });
            </script>
            <?php
        }

        //加载资源
        public static function add_page_sweetalert()
        {
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_sweetalert',
                plugin_dir_url(__FILE__) . 'sweetalert/sweetalert.min.css',
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_sweetalert',
                plugin_dir_url(__FILE__) . 'sweetalert/sweetalert.min.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
