<?php
/**
 * 一些公共函数
 */
if (!class_exists('Magick_Mixtrue_Tool')) {
    class Magick_Mixtrue_Tool
    {
        private static $time;
        public function __construct()
        {

            $time = self::get_time();

        }

        /**
         * 判断指定主题是否启用，若使用了该主题则返回true
         * 期待传入主题名  'Twenty Twenty'
         */
        public static function theme_active($theme_name)
        {
            $theme = wp_get_theme(); // 获取当前主题
            if ($theme_name == $theme->name || $theme_name == $theme->parent_theme) {
                //启用该主题
                return true;
            } else {
                //没有启用该主题
                return false;
            }
        }

        /**
         * 判断指定插件是否启用，若该插件启用则返回true
         * 期待传入插件目录，例如'advanced-custom-fields-pro/acf.php'
         */
        public static function plugin_active($plugin_position)
        {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
            if (is_plugin_active($plugin_position)) {
                //已启用
                return true;

            } else {
                //没有启用该插件
                return false;
            }
        }

        /**
         * 调试用，打印各种数据
         *
         */
        public static function p($data)
        {
            echo '<pre>';
            print_r($data);
            echo '</pre>';
        }

        /**
         * 调试用，打印当前页面的$hook参数
         */
        public static function display_page_hook($hook)
        {
            echo '<h1 style="color: crimson;text-align: center;">' . esc_html($hook) . '</h1>';
        }
        /**
         * 调试
         * 查看页面参数
         */

        //add_action('admin_enqueue_scripts', array(&$this, 'display_page_hook'));

        /**
         * 创建一个方法，在后台顶部显示一个通知
         */
        public static function magick_admin_notice_acfs($content)
        {
            ?>
        <div class = 'notice notice-error '>
        <p><?php _e($content, 'sample-text-domain');
            ?></p>
        </div>
        <?php
}

        /**
         * 时间很重要
         */
        public static function get_time()
        {
            date_default_timezone_set("Asia/Shanghai");
            $a = strtotime(date("Y-m-d H:i:s")); //当前时间戳
            $todaytime = strtotime("today"); //今日起始时间戳

            return array(
                'a' => array(
                    date("Y-m-d", $todaytime),
                    // date("Y-m-d H:i:s", $todaytime),
                    date("Y-m-d", $todaytime - 24 * 60 * 60 * 1),
                    date("Y-m-d", $todaytime - 24 * 60 * 60 * 2),
                    date("Y-m-d", $todaytime - 24 * 60 * 60 * 3),
                    date("Y-m-d", $todaytime - 24 * 60 * 60 * 4),
                    date("Y-m-d", $todaytime - 24 * 60 * 60 * 5),
                    date("Y-m-d", $todaytime - 24 * 60 * 60 * 6),
                ),
                'b' => array(
                    date("Y-m-d H:i:s", $todaytime - 8 * 60 * 60),
                    date("Y-m-d H:i:s", $todaytime - 24 * 60 * 60 * 1 - 8 * 60 * 60),
                    date("Y-m-d H:i:s", $todaytime - 24 * 60 * 60 * 2 - 8 * 60 * 60),
                    date("Y-m-d H:i:s", $todaytime - 24 * 60 * 60 * 3 - 8 * 60 * 60),
                    date("Y-m-d H:i:s", $todaytime - 24 * 60 * 60 * 4 - 8 * 60 * 60),
                    date("Y-m-d H:i:s", $todaytime - 24 * 60 * 60 * 5 - 8 * 60 * 60),
                    date("Y-m-d H:i:s", $todaytime - 24 * 60 * 60 * 6 - 8 * 60 * 60),
                ),
            );
        }

        /**
         * 处理时间用
         */
        public function getDateFromRange($startdate, $enddate)
        {
            $stimestamp = strtotime($startdate);
            $etimestamp = strtotime($enddate);
            // 计算日期段内有多少天
            $days = ($etimestamp - $stimestamp) / 86400 + 1;
            // 保存每天日期
            $date = array();
            for ($i = 0; $i < $days; $i++) {
                $date[] = date('Y-m-d', $stimestamp + (86400 * $i));
            }
            return $date;
        }
        /**
         * 输出本周、上周、本月、上月时间数组
         */
        public static function get_time_long($type = "this_week")
        {

            /**
             *输出本周数组
             */
            if ($type == "this_week") {
                //本周开始时间戳
                $startTime = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('y')));
                //本周结束时间戳
                $overTime = date("Y-m-d H:i:s", mktime(23, 59, 59, date('m'), date('d') - date('w') + 7, date('y')));
                $date = self::getDateFromRange($startTime, $overTime);
                return $date;
            };
            /**
             *输出上周数组
             */
            if ($type == "last_week") {
                //本周开始时间戳
                $startTime = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y')));
                //本周结束时间戳
                $overTime = date("Y-m-d H:i:s", mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y')));
                $date = self::getDateFromRange($startTime, $overTime);
                return $date;
            }
            /**
             *输出本月数组
             */
            if ($type == "this_month") {
                //本月起始时间日期格式
                $startTime = date("Y-m-d ", mktime(0, 0, 0, date('m'), 1, date('Y')));
                //本月结束时间日期格式
                $overTime = date("Y-m-d", mktime(23, 59, 59, date('m'), date('t'), date('Y')));
                $date = self::getDateFromRange($startTime, $overTime);
                return $date;
            }
            /**
             * 输出上一个月的数组
             */
            if ($type == "last_month") {
                $month = 1;
                // 1代表上个月，可以增加数字追溯前几个月的时间
                $startTime = date("Y-m-d", mktime(0, 0, 0, date("m") - 1 * $month, 1, date("Y")));
                $overTime = date("Y-m-d", mktime(23, 59, 59, date("m") - ($month - 1), 0, date("Y")));
                $date = self::getDateFromRange($startTime, $overTime);
                return $date;
            }
            $msg = "参数错误！";
            return $msg;

        }

        /**
         *输出上周数组
         */
        public function last_week()
        {
            //本周开始时间戳
            $startTime = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d') - date('w') + 1 - 7, date('Y')));
            //本周结束时间戳
            $overTime = date("Y-m-d H:i:s", mktime(23, 59, 59, date('m'), date('d') - date('w') + 7 - 7, date('Y')));
            $date = self::getDateFromRange($startTime, $overTime);
            return $date;
        }
        /**
         *输出本周数组
         */

        public function this_week()
        {
            //本周开始时间戳
            $startTime = date("Y-m-d H:i:s", mktime(0, 0, 0, date('m'), date('d') - date('w') + 1, date('y')));
            //本周结束时间戳
            $overTime = date("Y-m-d H:i:s", mktime(23, 59, 59, date('m'), date('d') - date('w') + 7, date('y')));
            $date = self::getDateFromRange($startTime, $overTime);
            return $date;
        }

        /**
         *输出本月数组
         */
        public function this_month()
        {
            //本月起始时间日期格式
            $startTime = date("Y-m-d ", mktime(0, 0, 0, date('m'), 1, date('Y')));
            //本月结束时间日期格式
            $overTime = date("Y-m-d", mktime(23, 59, 59, date('m'), date('t'), date('Y')));
            $date = self::getDateFromRange($startTime, $overTime);
            return $date;
        }

        /**
         * 输出上一个月的数组
         */
        public function last_month()
        {
            $month = 1;
            // 1代表上个月，可以增加数字追溯前几个月的时间
            $startTime = date("Y-m-d", mktime(0, 0, 0, date("m") - 1 * $month, 1, date("Y")));
            $overTime = date("Y-m-d", mktime(23, 59, 59, date("m") - ($month - 1), 0, date("Y")));
            $date = self::getDateFromRange($startTime, $overTime);
            return $date;
        }

        /**
         * 本日发文数量
         * 查询：https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
         * 描述：仅统计已发布的公开内容和密码保护内容
         * 类型：默认为post,可为page
         */
        public static function get_publish_count_today()
        {
            $today = getdate();
            $args = array(
                'post_type' => 'post', //类型
                'post_status' => 'publish', //状态
                'post__not_in' => get_option('sticky_posts'), //排除置顶文章
                'date_query' => array( //时间
                    array(
                        'year' => $today['year'],
                        'month' => $today['mon'],
                        'day' => $today['mday'],
                    ),
                ),
            );
            $query = new WP_Query($args);
            return $query->post_count;

        }

        /**
         * 用途：获取本周发文数量
         * 来源：https://www.166yc.cn/195.html
         * 参考：https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
         * 描述：仅统计当前周的已发布数量
         */
        public static function get_publish_count_week()
        {
            $date_query = array(

                'year' => date('Y'),
                'week' => date('W'),

            );
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'date_query' => $date_query,
                'no_found_rows' => true, //跳过计算找到的总行数
                'suppress_filters' => true,
                'fields' => 'ids',
                'posts_per_page' => -1,
            );
            $query = new WP_Query($args);
            return $query->post_count;
        }

        /**
         * 用途：获取本月发文数量
         * 描述：仅统计本月已发布文章数量
         */
        public static function get_publish_count_month()
        {
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'post__not_in' => get_option('sticky_posts'), //排除置顶文章
                'date_query' => array(
                    array(
                        'after' => '1 month ago',
                    ),
                ),
                'posts_per_page' => -1,
            );
            $query = new WP_Query($args);
            return $query->post_count;
        }

        /**
         * 用途：获取本年发文数量
         * 描述：仅统计本年已发布文章数量
         */
        public static function get_publish_count_year()
        {
            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'post__not_in' => get_option('sticky_posts'), //排除置顶文章
                'date_query' => array(
                    array(
                        'after' => '1 year ago',
                    ),
                ),
                'posts_per_page' => -1,
            );
            $query = new WP_Query($args);
            return $query->post_count;
        }
        /**
         * 用途：获取所有已发布文章数量
         * 来源：https://developer.wordpress.org/reference/functions/wp_count_posts/
         * 返回：数组
         */
        public static function get_publish_count()
        {
            $count_posts = wp_count_posts();

            if ($count_posts) {
                $published_posts = $count_posts->publish;
            }
            return $published_posts;
        }

        /**
         * 输入人员ID和时间，输出发文数量
         * 时间：2022-12-09
         */
        public static function get_count_user($id = '1', $time = '2023-02-16', $type = 'publish')
        {
            /**
             * $id:待查询人员的ID
             * $time:时间：2022-12-09
             * $type:文章状态类型，默认publish(已发布)
             */
            $arr = array();
            $args = array(
                'date_query' => array(
                    array(
                        'after' => $time,
                        'before' => $time,
                        //'after'     => '2022-12-09',
                        //'before'    => '2022-12-09',
                        'inclusive' => true,
                    ),
                ),
                'posts_per_page' => -1, //全显示
                'post_status' => $type, //已发布的文章 - 非待审、草稿、私密
                'author' => $id, //指定用户的ID
            );
            $query = new WP_Query($args);
            $arr['user_id'] = $id;
            $arr['time'] = $time;
            $arr['post_status'] = $type;
            $arr['total'] = $query->post_count;
            return $arr;

        }
        /**
         * 输入人员ID，返回最近7天发文数量
         * 输出：数组(array)
         */
        public static function get_count_user_week($id = '1')
        {
            //拿到时间数组
            $t = self::get_time()['a'];
            //存储数据
            $arr = array();

            for ($i = 0; $i < 7; $i++) {
                //拿到日期
                $time = $t[$i];
                $arr[$i] = self::get_count_user($id, $time, 'publish');

            }
            return $arr;
        }

        /**
         * 根据给出的ID返回对应属性值
         * [ID] => 1
         *[user_login] => test
         *[user_pass] => $P$Bm9497CNcPxNS8DJMMCMqgXR.jKTeQ.
         *[user_nicename] => test
         *[user_email] => test@test.cc
         *[user_url] => http://magick.plugin
         *[user_registered] => 2023-02-01 08:40:27
         *[user_activation_key] =>
         *[user_status] => 0
         *[display_name] => test  推荐用这个获取名字
         */
        public static function get_user_data($id = '1', $type = 'ID')
        {
            $user = new WP_User($id);
            return $user->data->$type;
        }

    } //end
}
//显示当前页hook
//add_action('admin_enqueue_scripts', array('Magick_Mixtrue_Tool', 'display_page_hook'));