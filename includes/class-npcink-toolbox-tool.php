<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/**
 * 一些公共函数
 */
if (!class_exists('Npcink_Toolbox_Tool')) {
    class Npcink_Toolbox_Tool
    {
        /**
         * 获取 WordPress 站点本地日期时间。
         *
         * WordPress 返回的本地时间字符串是事实源；DateTimeImmutable 仅负责
         * 后续日历运算，避免把 PHP 默认时区或固定偏移混入站点日期。
         */
        protected static function current_site_datetime()
        {
            $current_time = current_time('mysql');
            $date_time = DateTimeImmutable::createFromFormat('!Y-m-d H:i:s', $current_time);

            if ($date_time instanceof DateTimeImmutable) {
                return $date_time;
            }

            return new DateTimeImmutable($current_time);
        }

        /**
         * 将日期输入转换为不可变日期对象。
         */
        private static function parse_date($value)
        {
            try {
                return new DateTimeImmutable(trim((string) $value));
            } catch (Exception $exception) {
                return false;
            }
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
         * 时间很重要
         */
        public static function get_time()
        {
            $today = static::current_site_datetime()->setTime(0, 0, 0);
            $dates = array();

            for ($days_ago = 0; $days_ago < 7; $days_ago++) {
                $dates[] = $today->modify('-' . $days_ago . ' days')->format('Y-m-d');
            }

            return array(
                'a' => $dates,
            );
        }
        /**
         * 输入一个日期，输出第一秒时间和最后一秒
         */
        public static function export_handle_time($type = 'start', $time = '2023-03-31')
        {
            $handle_time = '';
            $date_time = self::parse_date($time);

            if (!$date_time) {
                return $handle_time;
            }

            if ($type === 'start') {
                $handle_time = $date_time->setTime(0, 0, 0)->format('Y-m-d H:i:s');
            }
            if ($type === 'end') {
                $handle_time = $date_time->setTime(23, 59, 59)->format('Y-m-d H:i:s');
            }
            return $handle_time;
        }

        /**
         * 处理时间用
         */
        public static function getDateFromRange($startdate, $enddate)
        {
            $start = self::parse_date($startdate);
            $end = self::parse_date($enddate);

            if (!$start || !$end) {
                return array();
            }

            $current = $start->setTime(0, 0, 0);
            $last = $end->setTime(0, 0, 0);
            $dates = array();

            while ($current <= $last) {
                $dates[] = $current->format('Y-m-d');
                $current = $current->modify('+1 day');
            }

            return $dates;
        }
        /**
         * 输出本周、上周、本月、上月时间数组
         */
        public static function get_time_long($type = "this_week")
        {
            $today = static::current_site_datetime()->setTime(0, 0, 0);

            if ($type === 'this_week' || $type === 'last_week') {
                $days_since_monday = (int) $today->format('N') - 1;
                $start = $today->modify('-' . $days_since_monday . ' days');

                if ($type === 'last_week') {
                    $start = $start->modify('-7 days');
                }

                $end = $start->modify('+6 days');
                return self::getDateFromRange($start->format('Y-m-d'), $end->format('Y-m-d'));
            }

            if ($type === 'this_month' || $type === 'last_month') {
                $start = $type === 'this_month'
                    ? $today->modify('first day of this month')
                    : $today->modify('first day of last month');
                $end = $start->modify('last day of this month');

                return self::getDateFromRange($start->format('Y-m-d'), $end->format('Y-m-d'));
            }

            $msg = __('参数错误！', 'npcink-site-toolbox');
            return $msg;
        }

        /**
         * Count posts that match a site-local calendar range.
         *
         * Sticky posts remain part of the count. The query only disables their
         * special ordering and asks WordPress for one ID while retaining the
         * total row count.
         */
        private static function count_release_posts($type, $status, $date_query)
        {
            $query = new WP_Query(array(
                'post_type' => $type,
                'post_status' => $status,
                'date_query' => array($date_query),
                'fields' => 'ids',
                'posts_per_page' => 1,
                'ignore_sticky_posts' => true,
            ));

            return (int) $query->found_posts;
        }

        /**
         * 输出今天、本周、本月、本年和累计发文数量
         * 可选获取时间、类型（page.post）、状态
         * 描述：按 WordPress 站点时区统计日历区间。
         */
        public static function get_total_release_amount($time = 'today', $type = 'post', $status = 'publish')
        {

            /**
             * 作用本日发文数量
             * 查询：https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
             * 描述：仅统计已发布的公开内容和密码保护内容
             */
            if ($time == 'today') {

                $today = static::current_site_datetime();
                return self::count_release_posts($type, $status, array(
                    'year' => (int) $today->format('Y'),
                    'month' => (int) $today->format('n'),
                    'day' => (int) $today->format('j'),
                ));
            }

            /**
             *功能：本周发文数量
             *来源：https://www.166yc.cn/195.html
             *参考：https://developer.wordpress.org/reference/classes/wp_query/#date-parameters
             */
            if ($time == 'week') {
                $today = static::current_site_datetime()->setTime(0, 0, 0);
                $start = $today->modify('-' . ((int) $today->format('N') - 1) . ' days');

                return self::count_release_posts($type, $status, array(
                    'after' => $start->format('Y-m-d'),
                    'before' => $start->modify('+6 days')->format('Y-m-d'),
                    'inclusive' => true,
                ));
            }

            /**
             * 用途：获取本月发文数量
             * 描述：仅统计本月已发布文章数量
             */
            if ($time == 'month') {
                $today = static::current_site_datetime();
                return self::count_release_posts($type, $status, array(
                    'year' => (int) $today->format('Y'),
                    'month' => (int) $today->format('n'),
                ));
            }

            /**
             * 用途：获取本年发文数量
             */

            if ($time == 'year') {
                $today = static::current_site_datetime();
                return self::count_release_posts($type, $status, array(
                    'year' => (int) $today->format('Y'),
                ));
            }

            /**
             * 用途：获取所有已发布文章数量
             * 来源：https://developer.wordpress.org/reference/functions/wp_count_posts/
             */
            if ($time == 'total') {
                $count_posts = wp_count_posts($type);

                return isset($count_posts->{$status}) ? (int) $count_posts->{$status} : 0;
            }

            $msg = __('参数错误！', 'npcink-site-toolbox');
            return $msg;
        }

        /**
         * 输出今天的相关统计数据
         * ['today']
         * 发文数量 - ['single']
         * 评论数量 - ['comments']
         * 注册人数 - ['register']
         * ['total']
         * 总发文数量 - ['single']
         * 总用户数量 - ['register']
         */
        public static function get_site_census_data()
        {
            //存储数据
            $arr = array();
            //拿到今天的时间
            $today = static::current_site_datetime();

            /**
             * 今天
             */
            //今天发文数量
            $today_single = self::count_release_posts('post', 'publish', array(
                'year' => (int) $today->format('Y'),
                'month' => (int) $today->format('n'),
                'day' => (int) $today->format('j'),
            ));
            $arr['today']['single'] = $today_single;

            //获取今天的评论数量

            $args = array(
                'date_query' => array(
                    array(
                        'year' => (int) $today->format('Y'),
                        'month' => (int) $today->format('n'),
                        'day' => (int) $today->format('j'),
                    ),

                ),
            );

            $today_comments = count(get_comments($args));
            $arr['today']['comments'] = $today_comments;

            //获取今天的注册数量
            $today_users = new WP_User_Query(array(
                'fields'      => 'ID',
                'number'      => 1,
                'count_total' => true,
                'date_query'  => array(
                    array(
                        'year'  => (int) $today->format('Y'),
                        'month' => (int) $today->format('n'),
                        'day'   => (int) $today->format('j'),
                    ),
                ),
            ));
            $arr['today']['register'] = (int) $today_users->get_total();

            /**
             * 总计
             */
            //总计发文数量
            $count_posts = wp_count_posts();
            $total_single = $count_posts->publish;
            $arr['total']['single'] = $total_single;

            //总用户
            //网站注册用户总数
            $total_users = get_user_count();
            $arr['total']['register'] = $total_users;

            return $arr;
        }

        /**
         * 输入人员ID和时间，输出发文数量，可选文章状态
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
