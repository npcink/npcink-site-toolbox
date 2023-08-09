<?php
//风格 特效
if (!class_exists('MaMi_Style_Page')) {
    class MaMi_Style_Page
    {
        //加载
        public static function run($config)
        {
            //获取选项
            $option =  MaMi_Admin::get_config($config, 'page');

            //圆角彩色背景标签云
            $color_tag = MaMi_Admin::get_config($option, 'color_tag');
            if ($color_tag) {
                add_filter('wp_tag_cloud', array(__CLASS__, 'colorCloud'), 1);
            }

            //评论区添加表情
            $comment_emote = MaMi_Admin::get_config($option, 'comment_emote');
            if ($comment_emote) {
                //判断当前页面是否加载评论区
                if (comments_open()) {
                    add_action('wp', array(__CLASS__, 'run_owo'));
                }
            }

            //烟花粒子特效
            $particle = MaMi_Admin::get_config($option, 'particle');
            if ($particle) {
                //手机端不加载
                if (!wp_is_mobile()) {
                    add_action('wp_enqueue_scripts', array(__CLASS__, 'add_page_particle_js'));
                    add_action('wp_footer', array(__CLASS__, 'add_page_particle'));
                }
            }


            //自定义登录页外观
            $custom_login_page = MaMi_Admin::get_config($option, 'custom_login_page');


            if ($custom_login_page) {

                //左下角颜色
                //右上角颜色
                //LOGO尺寸
                //顶部LOGO
                //文字背景图
                $background_left = MaMi_Admin::get_config($option, 'background_left', '#0073aa');
                $background_right = MaMi_Admin::get_config($option, 'background_right', '#0073aa');
                $logo_size = MaMi_Admin::get_config($option, 'logo_size', '1');
                $top_logo = MaMi_Admin::get_config($option, 'top_logo', '1');
                $background_img = MaMi_Admin::get_config($option, 'background_img', '1');

                add_action('login_header', array(__CLASS__, 'io_login_header'));
                add_action('login_footer', array(__CLASS__, 'io_login_footer'));
                //样式配置
                //add_action('login_head', array(__CLASS__, 'custom_login_style'));
                add_filter('login_head', function () use ($background_left, $background_right, $logo_size, $top_logo, $background_img) {
                    return self::custom_login_style($background_left, $background_right, $logo_size, $top_logo, $background_img);
                }, 10, 3);

                //加载css
                add_action('login_enqueue_scripts', array(__CLASS__, 'load_css'));
            }
            //self::custom_login_style($background_left, $background_right, $logo_size, $top_logo, $background_img);

        }
        /**
         * 添加彩色标签云
         */
        public static function colorCloud($text)
        {
            $text = preg_replace_callback('|<a (.+?)>|i', array(__CLASS__, 'colorCloudCallback'), $text);
            return $text;
        }
        public static function colorCloudCallback($matches)
        {
            $text = $matches[1];
            $colors = array('F99', 'C9C', 'F96', '6CC', '6C9', '37A7FF', 'B0D686', 'E6CC6E');
            $color = $colors[dechex(rand(0, 7))];
            $pattern = '/style=(\'|\")(.*)(\'|\")/i';
            $text = preg_replace($pattern, "style=\"display: inline-block; *display: inline; *zoom: 1; color: #fff; padding: 1px 5px; margin: 0 5px 5px 0; background-color: #{$color}; border-radius: 3px; -webkit-transition: background-color .4s linear; -moz-transition: background-color .4s linear; transition: background-color .4s linear;\"", $text);
            $pattern = '/style=(\'|\")(.*)(\'|\")/i';
            return "<a $text>";
        }



        /**
         * 效果：评论区加载表情包
         * 来源：https://github.com/DIYgod/OwO
         */
        public static function run_owo()
        {
            //加载js和css资源
            add_action('wp_enqueue_scripts', array(__CLASS__, 'load_owo_resouce'));
            //加载配置js
            add_action('wp_footer', array(__CLASS__, 'load_owo_comment_js'));
            //加载表情包位置
            add_filter('comment_form_defaults', array(__CLASS__, 'load_owo_content'));
        }

        /**
         * 加载表情用资源
         */
        public static function load_owo_resouce()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_OwO-js',
                plugin_dir_url(dirname(__DIR__)) . 'js/OwO.min.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );

            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_OwO-css',
                plugin_dir_url(dirname(__DIR__)) . 'css/OwO.min.css',
                array(),
                MAGICK_MIXTURE_VERSION,
                'all'
            );
        }

        /**
         * 加载表情用JS
         */
        public static function load_owo_comment_js()
        {
            //输入框定位
            $target_id = 'comment';

            //拿到表情包用js地址
            $json_src = plugin_dir_url(dirname(__DIR__)) . 'json/OwO.json';
?>
            <script>
                let $src = '<?php echo $json_src ?>';
                let $target = '<?php echo $target_id ?>'
                var OwO_demo = new OwO({
                    logo: 'OωO表情',
                    container: document.getElementsByClassName('OwO')[0],
                    target: document.getElementById($target),
                    api: $src,
                    position: 'down',
                    width: '100%',
                    maxHeight: '250px'
                });
            </script>
<?php
        }

        /**
         * 加载表情用文件内容
         */
        public static function load_owo_content($default)
        {
            //$commenter = wp_get_current_commenter();
            $default['comment_field'] .= '<div class="OwO"></div>
        <style>
        .OwO {
            padding: 0 0 20px 0;
        }
        .OwO .OwO-body {
            position: initial!important;
        }
        </style>
        ';

            return $default;
        }

        /**
         * 效果：页面添加烟花粒子
         * 来源：https://www.iowen.cn/canvas-click-effect-second-edition/
         */
        //添加文件
        public static function add_page_particle()
        {

            echo '<div id="clickCanvas" style=" position:fixed;left:0;top:0;z-index:999999999;pointer-events:none;"></div>';
        }
        //加载js
        public static function add_page_particle_js()
        {
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_particle-js',
                plugin_dir_url(dirname(__DIR__)) . 'js/style-click-particle.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                true
            );
        }


        /**
         * 效果：美化Wordpress登录页
         * 原文地址：https://www.iowen.cn/chundaimameihuawordpressmorendengluye/
         */


        /**
         * 加载css
         */
        public static function load_css()
        {
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_style-login-css',
                plugin_dir_url(\dirname(__FILE__)) . 'css/style-login.css',
                array(),
                MAGICK_MIXTURE_VERSION,
                'all'
            );
        }
        public static function io_login_header()
        {
            echo '<div class="login-container">
                  <div class="login-body">
                      <div class="login-img shadow-lg position-relative flex-fill">
                          <div class="img-bg position-absolute">
                              <div class="login-info">
                                  <h2>' . get_bloginfo('name') . '</h2>
                                  <p>' . get_bloginfo('description') . '</p>
                              </div>
                          </div>
                      </div>';
        }
        public static function io_login_footer()
        {
            echo '</div><!--login-body END-->
             </div><!--login-container END-->
             <div class="footer-copyright position-absolute">
                     <span>Copyright © <a href="' . esc_url(home_url()) . '" class="text-white-50" title="' . get_bloginfo('name') . '" rel="home">' . get_bloginfo('name') . '</a></span>
             </div>';
        }

        public static function custom_login_style($background_left, $background_right, $logo_size, $top_logo, $background_img)
        {
            //左下背景色
            $bg_left = $background_left;
            //右上背景色
            $bg_right = $background_right;
            //LOGO
            $logo_url = $top_logo;
            //尺寸
            $logo_size = $logo_size;
            //左边文字背景图
            $bg_img_left = $background_img;
            echo '<style type="text/css">
             body{
                 background:-o-linear-gradient(45deg,' . $bg_left . ',' . $bg_right . ');
                 background:linear-gradient(45deg,' . $bg_left . ',' . $bg_right . ');
                 height:100vh;
             }
             .login h1 a{
                 background-image:url(' . $logo_url . ' );
                 width:180px;
                 background-position:center center;
                 background-size:' . $logo_size . 'px;
             }
             .img-bg{
                 color: #fff;
                 padding: 2rem;
                 bottom: -2rem;
                 left: 0;
                 top: -2rem;
                 right: 0;
                 border-radius: 10px;
                 background-repeat: no-repeat;
                 background-position: center center;
                 background-size: cover;
                 background-image:url(' . $bg_img_left . ');
                }
 
                </style>';
        }
    } //end
}
