<?php

/**
 * 效果：禁止复制
 * 来源：https://juejin.cn/post/7153279899594129422
 */

if (!class_exists('Npcink_Page_Ban_Copy')) {
    class Npcink_Page_Ban_Copy
    {
        public static function run()
        {
            add_action('wp_footer', array(__CLASS__, 'js_code'), 1);
        }
        public static function js_code()
        {
?>
            <script>
                document.onselectstart = function() {
                    return false;
                }
            </script>
<?php
        }
    }
}
