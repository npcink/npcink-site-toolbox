<?php

/**
 * 权限 辅助
 */
if (!class_exists('MaMi_Auxiliary_Index')) {
    class MaMi_Auxiliary_Index
    {
        private static $auxiliary; //辅助功能
        //加载

        public static function run($auxiliary)
        {
            //加载文件
            self::load();

            //获取选项
            self::$auxiliary = $auxiliary;

            //加载文章统计
            $single_count = MaMi_Admin::get_config($auxiliary, 'single_count');
            if ($single_count) {
                Magick_Mixtrue_Census_Single::run();
            }



            //屏蔽恶意关键词搜索
            $no_malice_key = MaMi_Admin::get_config($auxiliary, 'no_malice_key');
            if ($no_malice_key) {
                add_action('template_redirect', array(__CLASS__, 'ytkah_search_ban'));
            }

            //登录验证码
            $login_code = MaMi_Admin::get_config($auxiliary, 'login_code');
            if ($login_code !== "false") {
                MaMi_Login_Verify::run($login_code);
            }

            //跳转中间页
            $go_middle = MaMi_Admin::get_config($auxiliary, 'go_middle');
            if ($go_middle !== "false") {
                //改造文章中的链接
                add_filter('the_content', array(__CLASS__, 'the_content_nofollowss'), 999);

                //改造评论中的链接
                add_filter('get_comment_text', array(__CLASS__, 'the_content_nofollowss'), 999);

                //添加重定向
                register_activation_hook(__FILE__, array(__CLASS__, 'go_new_link'));
                add_action('init', array(__CLASS__, 'go_new_link'));

                //行动
                add_action('template_redirect', array(__CLASS__, 'go_new_link_move'));
            }
        }
        //加载文件
        public static function load()
        {
            //文章统计页面
            require_once plugin_dir_path(__FILE__) . '/block/census-single.php';

            //登录验证码
            require_once plugin_dir_path(__FILE__) . '/block/login_verify.php';
        }

        //屏蔽恶意关键词搜索
        public static function ytkah_search_ban()
        {
            $malice_keu_content = MaMi_Admin::get_config(self::$auxiliary, 'malice_keu_content');

            if (is_search()) {
                global $wp_query;
                //拿到输入的值
                $ytkah_search_key = $malice_keu_content;
                if ($ytkah_search_key) {
                    $ytkah_search_key = str_replace("\n", "|", $ytkah_search_key);
                    $BanKey = explode('|', $ytkah_search_key);
                    $S_Key = $wp_query->query_vars;
                    foreach ($BanKey as $Key) {
                        if (stristr($S_Key['s'], $Key) != false) {
                            $message = '搜索内容包含敏感词，请换个方式搜索';
                            $message = $message . MaMi_Admin::blank_button();
                            wp_die($message);
                        }
                    }
                }
            }
        }

        /**
         * 跳转中间页
         */
        /**
         * WordPress外链新窗口打开并使用php页面go跳转 - 替换文章中的链接内容
         * https://www.dujin.org/12762.html
         */
        public static function the_content_nofollowss($content)
        {
            $pattern = '/<a(.*?)href="(.*?)"(.*?)>(.*?)<\/a>/';
            $content = preg_replace_callback($pattern, function ($matches) {
                $url = $matches[2];
                if (strpos($url, '://') !== false && strpos($url, home_url()) === false && !preg_match('/\.(jpg|jpeg|png|ico|bmp|gif|tiff)/i', $url)) {
                    $new_link = home_url('/go_to/?url=' . urlencode($url));
                    $replacement = '<a' . $matches[1] . 'href="' . $new_link . '"' . $matches[3] . ' rel="external nofollow" target="_blank">' . $matches[4] . '</a>';
                    return $replacement;
                }
                return $matches[0];
            }, $content);

            // 处理纯链接内容 - 有问题
            //$content = preg_replace_callback('/(https?:\/\/[^\s]+)/i', function ($matches) {
            //    $url = $matches[1];
            //    if (strpos($url, home_url()) === false && !preg_match('/\.(jpg|jpeg|png|ico|bmp|gif|tiff)/i', $url)) {
            //        $new_link = home_url('/go_to/?url=' . urlencode($url));
            //        $replacement = '<a href="' . $new_link . '" rel="external nofollow" target="_blank">' . $url . '</a>';
            //        return $replacement;
            //    }
            //    return $matches[0];
            //}, $content);

            // 替换评论者填写了网站地址的链接TODO:未完成


            return $content;
        }
        //注册

        public static function go_new_link()
        {
            add_rewrite_rule(
                'go_to', // 设置你的链接格式，例如 /too/
                '', // 空字符串表示不指定自定义模板文件的路径
                'top'
            );
            //刷新规则
            flush_rewrite_rules();
        }

        /**
         * 行动
         */
        public static  function go_new_link_move()
        {
            global $wp;
            //跳转中间页
            $go_middle = MaMi_Admin::get_config(self::$auxiliary, 'go_middle');

            if ($wp->request === 'go_to') {
                $path = plugin_dir_path(dirname(dirname(dirname(__FILE__))));

                switch ($go_middle) {
                    case 'zhihu':
                        include $path . 'public/templant/go/zhihu.php'; // 知乎
                        break;
                    case 'tencent':
                        include $path . 'public/templant/go/tencent.php'; // 腾讯
                        break;
                    case 'shimo':
                        include $path . 'public/templant/go/shimo.php'; // 石墨文档
                        break;
                    case 'jianshu':
                        include $path . 'public/templant/go/jianshu.php'; // 简书
                        break;
                    case 'csdn':
                        include $path . 'public/templant/go/csdn.php'; // CSDN
                        break;
                    case 'wx_community':
                        include $path . 'public/templant/go/wx_community.php'; // 微信公众号社群
                        break;
                    default:
                        // 默认操作（如果 $go_middle 的值不匹配上述任意一种情况）
                        include $path . 'public/templant/go/demo.php'; // 微信公众号社群
                }



                exit();
            }
        }
    }
}
