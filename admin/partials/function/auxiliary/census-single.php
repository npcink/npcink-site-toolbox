<?php
defined('ABSPATH') || exit;

/**
 * 文章统计功能
 */

if (!class_exists('Npcink_Toolbox_Census_Single')) {
    class Npcink_Toolbox_Census_Single implements Npcink_Toolbox_Module_Interface
    {

        public static function run($config = array())
        {
            //add_action('wp_loaded', array(__CLASS__, 'load'));
            //添加发文统计菜单
            add_action('admin_menu', array(__CLASS__, 'add_menu_single'));
            //添加设置选项
            add_action('admin_init', array(__CLASS__, 'register_census_settings'));
            //加载图标用js
            add_action('admin_enqueue_scripts', array(__CLASS__, 'load_enqueue_admin_script'));
        }



        /**
         * 添加发文统计菜单
         */
        public static function add_menu_single()
        {

            add_submenu_page(
                'index.php',
                __('发文统计', 'npcink-site-toolbox'),
                __('发文统计', 'npcink-site-toolbox'),
                'administrator',
                'npcink-site-toolbox-census',
                array(__CLASS__, 'load_content')
            );
        }

        //页面加载图标用css和js
        public static function load_enqueue_admin_script($hook)
        {
            if ('dashboard_page_npcink-site-toolbox-census' != $hook) {
                return;
            }

            $plugin_root_path = plugin_dir_path(dirname(dirname(dirname(__DIR__))));
            $build_css_path = $plugin_root_path . 'vite/count/dist/index.css';
            $build_js_path = $plugin_root_path . 'vite/count/dist/index.js';
            $build_css = plugin_dir_url(dirname(dirname(dirname(__DIR__)))) . 'vite/count/dist/index.css';
            $build_js = plugin_dir_url(dirname(dirname(dirname(__DIR__)))) . 'vite/count/dist/index.js';
            $build_css_version = is_file($build_css_path)
                ? NPCINK_SITE_TOOLBOX_VERSION . '-' . (string) filemtime($build_css_path)
                : NPCINK_SITE_TOOLBOX_VERSION;
            $build_js_version = is_file($build_js_path)
                ? NPCINK_SITE_TOOLBOX_VERSION . '-' . (string) filemtime($build_js_path)
                : NPCINK_SITE_TOOLBOX_VERSION;
            wp_enqueue_style(
                NPCINK_SITE_TOOLBOX_NAME . '_census_css',
                $build_css,
                array(),
                $build_css_version,
                'all'
            );
            wp_enqueue_script(
                NPCINK_SITE_TOOLBOX_NAME . '_census_js',
                $build_js,
                array(),
                $build_js_version,
                true
            );

            //传输数据给JS
            $npcink_site_toolbox_array = array(
                'countData' => self::deliver_data(), //统计的数据信息
            );

            wp_localize_script(NPCINK_SITE_TOOLBOX_NAME . '_census_js', 'dataLocal', $npcink_site_toolbox_array); //传给vite项目
        }

        /**
         * 准备传递的数据
         */
        public static function deliver_data()
        {
            //准备对象
            $array = array(
                'single' => array(
                    'count' => self::get_today_data(), //今天的统计数据
                    'today' => self::get_count_release()['week'], //今天文章发布数据
                    'month' => self::get_count_release()['month'], //今天文章发布数据
                )
            );
            return $array;
        }

        //待渲染的内容
        public static function load_content()
        {
?>
            <!-- 在默认WordPress“包装”容器中创建标题 -->
            <div class="wrap magick_section">

                <!--标题-->
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <!--展示内容-->
                <div id="npcink_site_toolbox_census_count"></div>

                <!--在保存设置时调用WordPress函数以呈现错误。 -->
                <?php settings_errors(); ?>
                <!-- 创建用于呈现选项的表单 -->
                <form method="post" action="options.php">
                    <?php settings_fields('npcink_site_toolbox_census_settings'); ?>
                    <?php do_settings_sections('npcink_site_toolbox_census_settings'); ?>
                    <?php submit_button(); ?>
                </form>

                <?php
                //echo "<h3>原始数据</h3>";
                //$user_release_arr = self::get_user_release_arr();
                //if (!empty($user_release_arr)) {
                //    echo '<pre>' . print_r($user_release_arr, true) . '</pre>';
                //} else {
                //    echo '<pre>暂无对象值</pre>';
                //}
                ?>

            </div><!-- /.wrap -->
            <?php
        }

        /**
         * 今日文章信息
         */
        public static function get_today_data()
        {
            //今天的数据
            $tool = new Npcink_Toolbox_Tool;
            $option = $tool->get_site_census_data();

            $array = array(
                array(
                    'title' => __('已发布', 'npcink-site-toolbox'),
                    'num' => (int)$option['today']['single'],
                    'unit' => __('篇', 'npcink-site-toolbox'),
                    'icon' => "dashicons dashicons-universal-access",
                ),
                array(
                    'title' => __('已评论', 'npcink-site-toolbox'),
                    'num' => (int)$option['today']['comments'],
                    'unit' => __('条', 'npcink-site-toolbox'),
                    'icon' => "dashicons dashicons-format-status",
                ),
                array(
                    'title' => __('已注册', 'npcink-site-toolbox'),
                    'num' => (int)$option['today']['register'],
                    'unit' => __('位', 'npcink-site-toolbox'),
                    'icon' => "dashicons dashicons-database-add",
                )

            );
            return $array;
        }

        /**
         * 准备表格统计信息
         */
        public static function get_count_release()
        {
            //准备日期

            $week = array(
                "title" => __('统计', 'npcink-site-toolbox'),
                "dataset" => self::get_user_release_arr()["week_sum"],
            );

            $month = array(
                "width" => 1200,
                "height" => 300,
                "title" => __('月度统计', 'npcink-site-toolbox'),
                "dataset" => self::get_user_release_arr()["month_sum"],
            );

            $array = array(
                "week" => $week,
                "month" => $month,
            );

            return $array;
        }

        //添加设置选项
        public static function register_census_settings()
        {
            // 首先，我们注册一个部分。这是必要的，因为所有未来的选项都必须属于一个。
            add_settings_section(
                'npcink_site_toolbox_census_section', // 用于标识此部分以及用于注册选项的ID
                __('已统计人员 ID', 'npcink-site-toolbox'), // 要在管理页面上显示的标题
                array(__CLASS__, 'render_census_settings_section'),
                'npcink_site_toolbox_census_settings' // 添加此部分选项的页面
            );

            //添加一个对钩选项
            add_settings_field(
                'option_id', // 用于标识整个主题中的字段的ID
                __('待统计人员', 'npcink-site-toolbox'), // 选项接口元素左侧的标签
                array(__CLASS__, 'render_census_user_field'),
                'npcink_site_toolbox_census_settings', // 将显示此选项的页面
                'npcink_site_toolbox_census_section', // 此字段所属的节的名称
                array( // 要传递给回调的参数数组。在这种情况下，只是一个描述。
                    __('选择需要监控的用户（排除订阅者）', 'npcink-site-toolbox'),
                )
            );

            //注册这个设置
            register_setting(
                'npcink_site_toolbox_census_settings', //选项组
                'npcink_site_toolbox_census', //选项名称
                array(
                    'type'              => 'array',
                    'sanitize_callback' => array(__CLASS__, 'sanitize_options'),
                    'default'           => array('option_id' => array()),
                )
            );
        }

        /**
         * 仅保留唯一的正整数用户 ID。
         */
        public static function sanitize_options($input)
        {
            $candidates = is_array($input) && isset($input['option_id'])
                ? (array) $input['option_id']
                : array();
            $ids = array();

            foreach ($candidates as $candidate) {
                if (is_int($candidate)) {
                    $id = $candidate;
                } elseif (is_string($candidate)) {
                    $candidate = trim($candidate);
                    if ($candidate === '' || !ctype_digit($candidate)) {
                        continue;
                    }
                    $id = (int) $candidate;
                } else {
                    continue;
                }

                if ($id > 0 && !isset($ids[$id])) {
                    $ids[$id] = $id;
                }
            }

            return array('option_id' => array_values($ids));
        }

        /**
         * 选择结果
         */
        public static function render_census_settings_section()
        {
            //拿到选项的值
            $options = get_option('npcink_site_toolbox_census');
            if ($options) {
                printf(
                    /* translators: %s: Comma-separated user IDs. */
                    esc_html__('您选择的人员 ID 是：%s', 'npcink-site-toolbox'),
                    esc_html(implode(',', $options['option_id']))
                );
                return;
            } else {
                esc_html_e('您没有选择值', 'npcink-site-toolbox');
                return;
            }
        }

        /**
         * 选中框设置的回调
         */
        public static function render_census_user_field($args)
        {
            // 首先，我们拿到选项
            $options = get_option('npcink_site_toolbox_census');
            $uwcc_checkbox_field_1 = isset($options['option_id']) ? (array) $options['option_id'] : [];
            //name值很关键

            //拿到用户数据
            $user_data = get_users(
                array(
                    //符合其中之一要求的人
                    'role__in' => $role = array('administrator', 'author', 'editor', 'contributor'),
                )
            );

            //将选项循环出来
            foreach ($user_data as $key => $value) {
                $id = $value->ID;
                $name = $value->display_name;
            ?>

                <input type='checkbox' name='npcink_site_toolbox_census[option_id][]' <?php checked(in_array($id, $uwcc_checkbox_field_1), 1); ?> value='<?php echo esc_attr($id); ?>'>
                <label class="magick-user-label"><?php echo esc_html($name); ?></label>
                &nbsp;&nbsp;|&nbsp;&nbsp;


            <?php
            } //end foreach
            ?>
            <!--描述-->
            <hr /><label for="option_id"> <?php echo esc_html($args[0]); ?></label>

<?php

        }




        /**
         * 优化：单次查询替代 N+1 WP_Query
         */
        public static function get_article_counts($data, $id)
        {
            global $wpdb;

            if (empty($data)) {
                return array();
            }

            // 计算日期范围
            $start_date = min($data);
            $end_date = max($data);

            $posts_last_changed = function_exists('wp_cache_get_last_changed')
                ? wp_cache_get_last_changed('posts')
                : wp_cache_get('last_changed', 'posts');
            $cache_key = 'article_counts_' . md5(
                $start_date . '|' . $end_date . '|' . (string) $posts_last_changed
            );
            $results = wp_cache_get($cache_key, 'npcink_site_toolbox');

            if (false === $results) {
                // 单次 SQL 查询，按日期和作者分组
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Aggregate query avoids an N+1 WP_Query loop; cache invalidates via posts last_changed.
                $results = $wpdb->get_results($wpdb->prepare(
                    "SELECT DATE(post_date) as post_date, post_author, COUNT(*) as cnt
                    FROM {$wpdb->posts}
                    WHERE post_status = 'publish'
                    AND post_type = 'post'
                    AND DATE(post_date) >= %s
                    AND DATE(post_date) <= %s
                    GROUP BY DATE(post_date), post_author",
                    $start_date,
                    $end_date
                ));
                $results = is_array($results) ? $results : array();
                wp_cache_set($cache_key, $results, 'npcink_site_toolbox', HOUR_IN_SECONDS);
            }

            // 构建查找表：[date => [author_id => count]]
            $lookup = array();
            foreach ($results as $row) {
                if (!isset($lookup[$row->post_date])) {
                    $lookup[$row->post_date] = array();
                }
                $lookup[$row->post_date][$row->post_author] = (int) $row->cnt;
            }

            // 构建输出数组
            $result = array();
            foreach ($data as $date) {
                $current_date = DateTime::createFromFormat('Y-m-d', $date);
                $current_day = $current_date->format('d');

                $counts = array($current_day);
                foreach ($id as $userId) {
                    $counts[] = isset($lookup[$date][$userId]) ? $lookup[$date][$userId] : 0;
                }

                $result[] = $counts;
            }

            return $result;
        }

        /**
         * 整理用户名
         * 输入用户ID数组
         */
        public static function format_dates($ID)
        {
            $result = array();

            foreach ($ID as $id) {
                $user = get_user_by('ID', $id);
                if ($user) {
                    $nickname = $user->display_name;
                    $result[] = $nickname;
                }
            }

            return $result;
        }


        /**
         * 结合
         * 用户数组，时间数组
         */
        public static function handle_data($id, $time)
        {
            $week_time = self::format_dates($id); //整理昵称数据
            array_unshift($week_time, "user"); //添加标识头
            $week_time = array($week_time); //存进数组

            $week_data = array_reverse(self::get_article_counts($time, $id)); //获取数据并反序

            $arr = array_merge($week_time, $week_data); //整理为所需格式
            return $arr;
        }

        /**
         * 获取一批人的发文数量,一周，一个月
         */
        public static function get_user_release_arr()
        {
            //工具函数
            $tool = new Npcink_Toolbox_Tool;
            //存储数组
            $arr = array();
            //拿到ID数组
            $options = get_option('npcink_site_toolbox_census');

            //默认查阅ID为1的人的发文数据
            $id = isset($options['option_id']) ? $options['option_id'] : [1];

            //拿到时间数组 - 最近一周
            $t_week = $tool->get_time()['a'];
            //拿到时间数组 - 本月
            $t_month =  array_reverse($tool->get_time_long("this_month")); //获取时间并取反



            $arr['week_sum'] = self::handle_data($id, $t_week);

            $arr['month_sum'] = self::handle_data($id, $t_month);

            return $arr;
        }
    } //end class
}
