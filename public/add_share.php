<?php
//添加分享按钮


if (!class_exists('Npcink_Public_Add_Share')) {
    class Npcink_Public_Add_Share
    {
        private static $config; //分类数组
        public static function run()
        {
            //self::$config = $option;
            //加载HTML
            add_action('wp_footer', array(__CLASS__, 'add_share_html'));

            //加载css和jS
            //使用动作钩子，加载这个函数到前台
            add_action('wp_enqueue_scripts', array(__CLASS__, 'magick_load_vue'));
        }
        public static function magick_load_vue()
        {

            wp_enqueue_style('唯一CSS名', plugin_dir_url(__FILE__) . 'share/share.css', array(), '1.0.0', 'all');
            //注册
            wp_register_script('唯一js名', plugin_dir_url(__FILE__) . 'share/share.js', array(), '1.0.0', true);
            //加载
            wp_enqueue_script('唯一js名');
        }




        //添加HTML
        public static function add_share_html()
        {
            $url = plugin_dir_url(__FILE__).'share/';
            echo '
            <!--
            侧边
        -->
        <div class="elevator_item ">
            <div class=" medium ">
                <button class="btn" onclick="activeType()">展开</button>
            </div>
    
        </div>
        <!--
            弹窗内容
        -->
        <section class="site-sharing-container site-overlay">
            <div class="site-sharing-content">
                <span class="title">分享</span>
                <ul>
                    <li>
                        <span class="icon">
                            <img src="' . $url . 'image/画报.svg" />
                        </span>
                        <span class="title">创建画报</span>
                    </li>
                    <li>
                        <span class="icon" onclick="copyLink()"> <img src="' . $url . 'image/链接.svg" /></span>
                        <span class="title">复制链接</span>
    
                    </li>
                    <li>
                        <span class="icon"> <img src="' . $url . 'image/微信.svg" /></span>
                        <span class="title">微信</span>
                    </li>
                    <li>
                        <span class="icon"> <img src="' . $url . 'image/邮件.svg" /></span>
                        <span class="title">邮件</span>
                    </li>
                    <li>
                    <span class="icon">
                        <img src="' . $url . 'image/微博.svg" />
                    </span>
                    <span class="title">微博</span>
                </li>
                <li>
                    <span class="icon"> <img src="' . $url . 'image/QQ 空间.svg" /></span>
                    <span class="title">QQ 空间</span>
                </li>
                <li>
                    <span class="icon"> <img src="' . $url . 'image/Facebook.svg" /></span>
                    <span class="title">Facebook</span>
                </li>
                <li>
                    <span class="icon"> <img src="' . $url . 'image/X.svg" /></span>
                    <span class="title">X</span>
                </li>
    
                </ul>
            </div>
        </section>
            ';
        }
    }
}
