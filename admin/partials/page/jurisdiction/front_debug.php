<?php

/**
 * 效果：禁用前端调试
 * 来源：https://juejin.cn/post/7337188759055663119
 */

if (!class_exists('Npcink_Page_Front_Debug')) {
    class Npcink_Page_Front_Debug
    {
        public static function run()
        {
            add_filter('wp_footer', array(__CLASS__, 'js_code'), 1);
        }
        public static function js_code()
        {
?>
            <script>
                setInterval(function() {

                    const startTime = performance.now();
                    // 设置断点
                    debugger;
                    const endTime = performance.now();
                    // 设置一个阈值，例如100毫秒
                    if (endTime - startTime > 100) {
                        window.location.href = 'about:blank';
                    }

                }, 100);
            </script>
<?php
        }
    }
}
