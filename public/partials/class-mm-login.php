<?php

if (!class_exists('Magick_Mixtrue_Login')) {
    class Magick_Mixtrue_Login
    {
        public function __construct()
        {

        }
        public static function run()
        {

            add_action('login_init', array(__CLASS__, 'load'));

        }

        public static function load()
        {
            //自定义登录页外观
            if (carbon_get_theme_option('cmma_abt_style_login') === "yes") {
                self::run_iowen();
            }
            //添加腾讯验证码
            if (carbon_get_theme_option('cmma_login_verify_tx') === "tx_vcode") {
                self::login_verify_tx_run();
            }

            //添加数字运算验证码
            if (carbon_get_theme_option('cmma_login_verify_tx') === "math_results") {
                self::run_math();
            }
        }

        /**
         * 效果：美化Wordpress登录页
         * 原文地址：https://www.iowen.cn/chundaimameihuawordpressmorendengluye/
         */

        public static function run_iowen()
        {

            add_action('login_header', array(__CLASS__, 'io_login_header'));
            add_action('login_footer', array(__CLASS__, 'io_login_footer'));
            add_action('login_head', array(__CLASS__, 'custom_login_style'));
            //加载css
            add_action('login_enqueue_scripts', array(__CLASS__, 'load_css'));

        }
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

        public static function custom_login_style()
        {
            //左下背景色
            $bg_left = carbon_get_theme_option('cmma_opt_login_bgcolor_left');
            //右上背景色
            $bg_right = carbon_get_theme_option('cmma_opt_login_bgcolor_right');
            //LOGO
            $logo_url = carbon_get_theme_option('cmma_opt_login_logo');
            //尺寸
            $logo_size = carbon_get_theme_option('cmma_opt_login_logo_size');
            //左边文字背景图
            $bg_img_left = carbon_get_theme_option('cmma_opt_login_bg_left');
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

        /**
         * 效果：添加数学验证码
         * 来源：https://blog.csdn.net/qq_39339179/article/details/119183143
         */
        //后台登陆数学验证码开始
        public static function run_math()
        {
            add_action('login_form', array(__CLASS__, 'myplugin_add_login_fields'));
            add_action('login_form_login', array(__CLASS__, 'login_val'));
        }

        public static function myplugin_add_login_fields()
        {
            //获取两个随机数, 范围0~100
            $num1 = rand(0, 20);
            $num2 = rand(0, 20);
            //最终网页中的具体内容
            echo "<p><label for='math' class='small'>验证码： $num1 + $num2 = ?<input type='text' name='sum' class='input' value='' size='20' tabindex='4'>"
                . "<input type='hidden' name='num1' value='$num1'>"
                . "<input type='hidden' name='num2' value='$num2'></label></p>";
        }

        public static function login_val()
        {
            //初始化
            $_POST['sum'] = isset($_POST['sum']) ? $_POST['sum'] : 0;
            $_POST['num1'] = isset($_POST['num1']) ? $_POST['num1'] : 0;
            $_POST['num2'] = isset($_POST['num2']) ? $_POST['num2'] : 0;
            $sum = $_POST['sum']; //用户提交的计算结果
            switch ($sum) {
                //得到正确的计算结果则直接跳出
                case $_POST['num1'] + $_POST['num2']:break;
                //未填写结果时的错误讯息
                case null:wp_die('提示: 请输入验证码.');
                    break;
                //计算错误时的错误讯息
                default:wp_die('提示: 验证码错误,请重试.');

            }
        }

/**
 * WordPress 接入腾讯防水墙，给网站登录加上验证功能
 * 原文地址：https://www.iowen.cn/wordpress-tencent-waterproof-wall/
 * 一为忆
 * swallow 主题
 */
        public static function login_verify_tx_run()
        {

            add_action('login_head', array(__CLASS__, 'add_login_head'));
            add_action('login_form', array(__CLASS__, 'add_captcha_body'));
            add_filter('wp_authenticate_user', array(__CLASS__, 'validate_tcaptcha_login'), 100, 1);

        }

        public static function add_login_head()
        {
            echo '<script src="https://ssl.captcha.qq.com/TCaptcha.js"></script>';
            echo '<style type="text/css">.login_button {line-height:38px;border-radius:3px;cursor:pointer;color:#555;background:#eee;border:2px solid #a5a5a5;font-size:14px;margin-bottom:10px;text-align:center;transition:.5s;}.login_button:hover{color:#fff;background:#444;border-color:#444;}</style>';
        }
        public static function add_captcha_body()
        {
            $appid = carbon_get_theme_option('cmma_login_verify_tx_id'); //拿到 ID

            ?>
              <input type="hidden" id="wp007_tcaptcha" name="tcaptcha_007" value="" />
              <input type="hidden" id="wp007_ticket" name="syz_ticket" value="" />
              <input type="hidden" id="wp007_randstr" name="syz_randstr" value="" />
              <!-- 修改下面的 data-appid 值 -->
              <div id="TencentCaptcha" data-appid="<?php echo $appid; ?>" data-cbfn="callback" class="login_button">验证</div>
              <script>
                  window.callback = function(res){
                      if(res.ret === 0){
                          var but = document.getElementById("TencentCaptcha");
                          document.getElementById("wp007_ticket").value = res.ticket;
                          document.getElementById("wp007_randstr").value = res.randstr;
                          document.getElementById("wp007_tcaptcha").value = 1;
                          but.style.cssText = "color:#fff;background:#4fb845;border-color:#4fb845;pointer-events:none";
                          but.innerHTML = "验证成功";
                      }
                  }
              </script>
            <?php
         }

/**
 * 处理登录二次验证
 */
        public static function validate_tcaptcha_login($user)
        {
            $slide = $_POST['tcaptcha_007'];
            if ($slide == '') {
                return new WP_Error('broke', __("请先进行真人验证！！！"));
            } else {
                $result = validate_login($_POST['syz_ticket'], $_POST['syz_randstr']);
                if ($result['result']) {
                    return $user;
                } else {
                    return new WP_Error('broke', $result['message']);
                }
            }

        }

/**
 * 请求服务器验证
 */
        public static function validate_login($Ticket, $Randstr)
        {
            $appid = carbon_get_theme_option('cmma_login_verify_tx_id'); //修改App ID
            $AppSecretKey = carbon_get_theme_option('cmma_login_verify_tx_key'); //修改App Secret Key
            $UserIP = $_SERVER["REMOTE_ADDR"];

            $url = "https://ssl.captcha.qq.com/ticket/verify";
            $params = array(
                "aid" => $appid,
                "AppSecretKey" => $AppSecretKey,
                "Ticket" => $Ticket,
                "Randstr" => $Randstr,
                "UserIP" => $UserIP,
            );
            $paramstring = http_build_query($params);
            $content = txcurl($url, $paramstring);
            $result = json_decode($content, true);
            if ($result) {
                if ($result['response'] == 1) {
                    return array(
                        'result' => 1,
                        'message' => '',
                    );
                } else {
                    return array(
                        'result' => 0,
                        'message' => $result['err_msg'],
                    );
                }
            } else {
                return array(
                    'result' => 0,
                    'message' => '请求失败,请再试一次！',
                );
            }
        }

        /**
         * 请求接口返回内容
         * @param  string $url [请求的URL地址]
         * @param  string $params [请求的参数]
         * @param  int $ipost [是否采用POST形式]
         * @return  string
         */
        public static function txcurl($url, $params = false, $ispost = 0)
        {
            $httpInfo = array();
            $ch = curl_init();

            curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
            curl_setopt($ch, CURLOPT_USERAGENT, 'JuheData');
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
            curl_setopt($ch, CURLOPT_TIMEOUT, 60);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            if ($ispost) {
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                curl_setopt($ch, CURLOPT_URL, $url);
            } else {
                if ($params) {
                    curl_setopt($ch, CURLOPT_URL, $url . '?' . $params);
                } else {
                    curl_setopt($ch, CURLOPT_URL, $url);
                }
            }
            $response = curl_exec($ch);
            if ($response === false) {
                //echo "cURL Error: " . curl_error($ch);
                return false;
            }
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $httpInfo = array_merge($httpInfo, curl_getinfo($ch));
            curl_close($ch);
            return $response;
        }

    }
}
