<?php

/**
 * 效果：返回顶部挂件
 * 来源1：https://lishuma.com/connect
 * 来源2：https://www.shephe.com/website/
 */
if (!class_exists('Npcink_Page_Go_Top')) {
    class Npcink_Page_Go_Top
    {
        //选项值，进一步调用的值
        public static function run($config, $option)
        {
            //偷瞄猫猫
            if ($config === "cat") {
                add_action('wp_footer', array(__CLASS__, 'cat'), 100);
                //add_action('wp_enqueue_scripts', array(__CLASS__, 'cat_js'));//纯jS方案
            }
            //圆角箭头
            if ($config === "arrow") {

                add_action('wp_footer', array(__CLASS__, 'jiub'), 100);
            }
            //抓绳猫猫
            if ($config === "cord_cat") {
                require_once plugin_dir_path(__FILE__) . 'cord_cat/index.php';
                Npcink_Page_Back_Top_Cat::run($option);
            }
        }

        //偷瞄猫猫
        public static function cat()
        {
            //准备图片地址
            $cat_url = plugin_dir_url(__FILE__) . 'images/cat.png';
?>
            <div id="topcontrol" onclick="goTop()">
                <img src="<?php echo $cat_url ?>" alt="偷瞄猫猫" title="偷瞄猫猫">
            </div>
            <script>
                const goTop = () => {
                    window.scrollTo({
                        top: 0,
                        behavior: "smooth"
                    });
                }
                const topControl = document.getElementById('topcontrol');
                window.addEventListener('scroll', () => {
                    if (window.scrollY > 600) {
                        topControl.classList.add('npcShow');
                    } else {
                        topControl.classList.remove('npcShow');
                    }
                });
            </script>
            <style>
                #topcontrol {
                    position: fixed;
                    bottom: 20px;
                    right: 20px;
                    /* 修正位置使其不贴边 */
                    opacity: 0;
                    /* 初始状态隐藏 */
                    transition: opacity 0.3s ease;
                    /* 动画效果 */
                    cursor: pointer;
                }

                #topcontrol.npcShow {
                    opacity: 1;
                    /* 滚动到一定高度后显示 */
                }
            </style>
<?php
        }


        //加载资源
        public static function cat_js()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_go_top_cat',
                plugin_dir_url(__FILE__) . 'js/cat.js',
                array("jquery"),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }
    }
}
