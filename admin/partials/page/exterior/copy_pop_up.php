<?php

/**
 * 效果：复制弹窗
 * 来源：
 */
if (!class_exists('Npcink_Page_Copy_Pop_Up')) {
    class Npcink_Page_Copy_Pop_Up
    {

        public static function run($config)
        {
            //通用圆角
            if ($config === "sweetalert") {
                add_action('wp_footer', array(__CLASS__, 'jiub'), 100);
            }
        }
        //复制成功提醒  
        public static function jiub()
        {
            echo '<link rel="stylesheet" type="text/css" href="https://cdn.bootcss.com/sweetalert/1.1.3/sweetalert.min.css" >';
            echo '<script src="https://cdn.bootcss.com/sweetalert/1.1.3/sweetalert.min.js"></script>';
            echo '<script>document.body.oncopy = function() { swal("复制成功！", "转载请务必保留原文链接，申明来源，谢谢合作！！","success");};</script>';
        }
    }
}
