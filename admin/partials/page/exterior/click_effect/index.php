<?php

/**
 * 效果：点击特效
 * 来源1：https://www.iowen.cn/canvas-click-effect-second-edition/
 * 来源2：https://blog.csdn.net/m0_58849641/article/details/126126951
 * 来源3：https://www.npc.ink/14512.html
 */
if (!class_exists('MaBox_Page_Add_Click_Effect')) {
    class MaBox_Page_Add_Click_Effect
    {
        public static function run($config)
        {
            switch ($config) {
                case "diffuse": //爆炸烟花
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'add_page_particle_js'));
                    add_action('wp_footer', array(__CLASS__, 'add_page_particle'));
                    break;
                case "text":
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'text'));
                    break;
                case "number":
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'number'));
                    break;
                case "love":
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'love'));
                    break;
                case "scatteredFireworks": //四散烟花
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'scattered_fireworks'));
                    break;
                case "starTrail": //星星拖尾
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'star_trail'));
                    add_action('wp_footer', array(__CLASS__, 'add_page_star_trail'));
                    break;
                case "loveWhirl": //爱心回旋
                    add_action('wp_footer', array(__CLASS__, 'add_page_circle_loveWhirl'));
                    break;
                case "circleFireworks": //圆圈烟花
                    add_action('wp_footer', array(__CLASS__, 'add_page_circle_fireworks'));
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'circle_fireworks'));
                    break;
            }
        }

        //添加四散粒子文件
        public static function add_page_particle()
        {
            echo '<div id="clickCanvas"  style=" position:fixed;left:0;top:0;z-index:999999999;pointer-events:none;"></div>';
        }
        //加载四散js
        public static function add_page_particle_js()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_particle',
                plugin_dir_url(__FILE__) . 'js/click_style_particle.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 文字
         */
        public static function text()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_text',
                plugin_dir_url(__FILE__) . 'js/click_style_text.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 数字
         */
        public static function number()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_style_number.js',
                plugin_dir_url(__FILE__) . 'js/click_style_number.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 爱心
         */
        public static function love()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_style_love.js',
                plugin_dir_url(__FILE__) . 'js/click_style_love.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 圆圈烟花
         */
        public static function scattered_fireworks()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_style_scattered_fireworks.js',
                plugin_dir_url(__FILE__) . 'js/click_style_scattered_fireworks.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 星星拖尾
         */
        public static function add_page_star_trail()
        {
            echo '<span class="js-cursor-container"></span>';
        }
        public static function star_trail()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_style_star_trail.js',
                plugin_dir_url(__FILE__) . 'js/click_style_star_trail.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }

        /**
         * 爱心回旋
         */
        public static function add_page_circle_loveWhirl()
        {
            wp_enqueue_style(MAGICK_MIXTURE_NAME . '_click_loveWhirl', '', array(), MAGICK_MIXTURE_VERSION);
            $css = ".heartWrap { position: absolute; z-index: 999; }
.heart { position: absolute; background-color: #faa; animation: heartMove 1s linear infinite; animation-iteration-count: 1; animation-delay: var(--delay, 0); animation-fill-mode: forwards; transform-origin: center; opacity: 0; }
.heart:before, .heart:after { position: absolute; content: ''; left: 6px; top: 0; width: 6px; height: 10px; background: inherit; border-radius: 15px 15px 0 0; transform-origin: 0 100%; transform: rotate(-45deg); }
.heart:after { left: 0; transform-origin: 100% 100%; transform: rotate(45deg); }
.late0 { --lateX: -0px; --delay: 0.2s; }
.late1 { --lateX: -10px; --delay: 0.1s; }
.late2 { --lateX: -20px; }
.late3 { --lateX: 10px; --delay: 0.3s; }
.late4 { --lateX: 20px; --delay: 0.4s; }
@keyframes heartMove { 0% { transform: scale(0.5); opacity: 0.1; } 150% { transform: translate(var(--lateX, 0px), -30px); } 50% { transform: scale(1) translate(var(--lateX, 0px), -100px); opacity: 1; } 100% { opacity: 0; } }";
            wp_add_inline_style(MAGICK_MIXTURE_NAME . '_click_loveWhirl', $css);

            wp_register_script(MAGICK_MIXTURE_NAME . '_click_loveWhirl_js', '', array(), MAGICK_MIXTURE_VERSION, true);
            $js = "document.addEventListener('click', function(e) {
    var vNode = document.createElement('div');
    vNode.className = 'heartWrap';
    Array.from(new Array(5), function(_, index) {
        var heart = document.createElement('div');
        heart.className = 'heart late' + index;
        heart.style.background = '#' + Math.random().toString(16).slice(-6);
        vNode.appendChild(heart);
    });
    document.body.appendChild(vNode);
    vNode.style.top = e.pageY - 20 + 'px';
    vNode.style.left = e.pageX - 10 + 'px';
    setTimeout(function() {
        document.body.removeChild(vNode);
    }, 2000);
});";
            wp_add_inline_script(MAGICK_MIXTURE_NAME . '_click_loveWhirl_js', $js);
        }

        /**
         * 圆圈烟花
         */
        public static function add_page_circle_fireworks()
        {
            echo '<canvas class="fireworks" style="position:fixed;left:0;top:0;z-index:99999999;pointer-events:none;"></canvas>
            <style>
              </style>
              ';
        }
        public static function circle_fireworks()
        {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_click_style_circle_fireworks.js',
                plugin_dir_url(__FILE__) . 'js/click_style_circle_fireworks.js',
                array('jquery'),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
