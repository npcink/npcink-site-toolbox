<?php

if (!class_exists('MaBox_Page_Front_Debug')) {
    class MaBox_Page_Front_Debug
    {
        public static function run()
        {
            add_action('wp_footer', array(__CLASS__, 'render'), 999);
        }

        public static function render()
        {
            echo '<script>setInterval(function(){var s=performance.now();debugger;if(performance.now()-s>100)window.location.href="about:blank"},100)</script>' . "\n";
        }
    }
}
