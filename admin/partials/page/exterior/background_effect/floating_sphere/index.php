<?php

/**
 * 效果：质感圆球
 * 来源：https://www.wkun.com/studio/?nav
 */

if (!class_exists('MaBox_Page_Floating_Sphere')) {
    class MaBox_Page_Floating_Sphere
    {
        public static function run()
        {
            //加载jS
            add_action('wp_enqueue_scripts', array(__CLASS__, 'add_css'));

            //加载节点
            add_action('wp_footer', array(__CLASS__, 'add_node'));
        }

        public static function add_node()
        {
?>
            <div id="stage">
                <div id="bg" class="" style="transform: translateX(-200px) translateY(300px) rotateZ(-60deg); opacity: 1; z-index:-1;">
                    <div class="row1">
                        <div class="orb1">
                            <div></div>
                        </div>
                        <div class="orb2">
                            <div></div>
                        </div>
                        <div class="orb1c">
                            <div></div>
                        </div>
                        <div class="orb2c">
                            <div></div>
                        </div>
                    </div>
                    <div class="row2">
                        <div class="orb1">
                            <div></div>
                        </div>
                        <div class="orb2">
                            <div></div>
                        </div>
                        <div class="orb1c">
                            <div></div>
                        </div>
                        <div class="orb2c">
                            <div></div>
                        </div>
                    </div>
                    <div class="row3">
                        <div class="orb1">
                            <div></div>
                        </div>
                        <div class="orb2">
                            <div></div>
                        </div>
                        <div class="orb1c">
                            <div></div>
                        </div>
                        <div class="orb2c">
                            <div></div>
                        </div>
                    </div>
                    <div class="row1c">
                        <div class="orb1">
                            <div></div>
                        </div>
                        <div class="orb2">
                            <div></div>
                        </div>
                        <div class="orb1c">
                            <div></div>
                        </div>
                        <div class="orb2c">
                            <div></div>
                        </div>
                    </div>
                    <div class="row2c">
                        <div class="orb1">
                            <div></div>
                        </div>
                        <div class="orb2">
                            <div></div>
                        </div>
                        <div class="orb1c">
                            <div></div>
                        </div>
                        <div class="orb2c">
                            <div></div>
                        </div>
                    </div>
                    <div class="row3c">
                        <div class="orb1">
                            <div></div>
                        </div>
                        <div class="orb2">
                            <div></div>
                        </div>
                        <div class="orb1c">
                            <div></div>
                        </div>
                        <div class="orb2c">
                            <div></div>
                        </div>
                    </div>
                </div>
            </div>
<?php
        }
        /**
         * 添加js
         */
        public static function add_css()
        {
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_floating_sphere',
                plugin_dir_url(__FILE__) . 'floating.css',
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );
        }
    }
}
