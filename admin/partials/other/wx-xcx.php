<?php

/**
 * 小程序生成链接
 */
if (!class_exists('MaMi_Wx_Xcx')) {
    class MaMi_Wx_Xcx
    {
        //选项值
        private static $option;
        public static function run($wx_xcx)
        {
            self::$option = $wx_xcx;
            $active = MaMi_Admin::get_config(self::$option, 'active'); //状态
            if ($active) {
                add_action('wp_head', array(__CLASS__, 'add_hello_header'));

                //注册页面模版
                add_filter('theme_page_templates', array(__CLASS__, 'add_custom_page_template'));

                //置顶模版路径
                add_filter('template_include', array(__CLASS__, 'get_custom_template'));
            }
        }

        public static function add_hello_header()
        {
            $appid = MaMi_Admin::get_config(self::$option, 'appid');
            $secret = MaMi_Admin::get_config(self::$option, 'secret');
            $path = MaMi_Admin::get_config(self::$option, 'path'); //页面参数
            $query = MaMi_Admin::get_config(self::$option, 'query'); //查询参数

            $token = self::wx_json_token($appid, $secret);
            $link = self::get_link($token, $path, $query);
            //TODO:使用缓存技术，缓存token
            // return $link;
            echo $link;
        }
        /**
         * 构造获取token的链接
         */
        public static  function wx_json_token($appid, $secret)
        {
            //构造链接
            $link = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=" . $appid . "&secret=" . $secret;

            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $link,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
            ]);


            // 抓取URL并把它传递给浏览器
            $response = curl_exec($curl);
            //$err = curl_error($curl);

            // 关闭cURL资源，并且释放系统资源
            curl_close($curl);

            //返回函数生成的内容
            //获取token文件
            //将数组变为变量
            $json_token = json_decode($response, true);

            //拿到需要的值  expires_in为有效期2小时
            $wx_token =  $json_token['access_token'];


            return $wx_token;
        }

        /**
         * 获取跳转小程序的链接
         */
        public static function get_link($token, $path, $query)
        {
            //获取小程序链接
            $xcx_url = 'https://api.weixin.qq.com/wxa/generatescheme?access_token=' . $token;


            $params = array(
                "jump_wxa" => array(
                    "path" => $path,
                    "query" => $query,
                    //"env_version"=>''
                ),

            );

            $data = json_encode($params);


            $curl = curl_init();

            curl_setopt_array($curl, [
                CURLOPT_URL => $xcx_url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => $data,
            ]);

            $response = curl_exec($curl);
            $err = curl_error($curl);

            curl_close($curl);

            if ($err) {
                echo "cURL Error #:" . $err;
            }

            //拿到小程序链接相关信息
            $json_url = json_decode($response, true);


            //拿到需要的值
            $wx_url =  $json_url['openlink'];
            return $wx_url;
        }
        /**
         * 添加单页
         */
        // 注册自定义页面模板
        public static function add_custom_page_template($templates)
        {
            $templates['custom-template.php'] = '微信小程序引导页';
            return $templates;
        }

        // 指定自定义页面模板的路径
        public static  function get_custom_template($template)
        {
            if (!is_singular() || !$template) {
                return $template;
            }

            $custom_template = get_post_meta(get_queried_object_id(), '_wp_page_template', true);
            if ('custom-template.php' === basename($custom_template)) {
                $template = plugin_dir_path(__FILE__) . '../block/custom-template.php';
            }

            return $template;
        }
    } //end
}
