<?php

/**
 * 文章统计菜单
 */

if (!class_exists('Magick_Mixtrue_Census_Single')) {
    class Magick_Mixtrue_Census_Single
    {

        public static function run()
        {
            //add_action('wp_loaded', array(__CLASS__, 'load'));
            //添加发文统计菜单
            add_action('admin_menu', array(__CLASS__, 'add_menu_single'));
            //添加设置选项
            add_action('admin_init', array(__CLASS__, 'magick_plugin_options'));
            //加载图标用js
            add_action('admin_enqueue_scripts', array(__CLASS__, 'load_enqueue_admin_script'));
        }



        /**
         * 添加发文统计菜单
         */
        public static function add_menu_single()
        {

            add_submenu_page('index.php', __('发文统计'), __('发文统计'), 'administrator', 'magick-census-single', array(__CLASS__, 'load_content'));
        }

        //页面加载图标用css和js
        public static function load_enqueue_admin_script($hook)
        {
            //判断下，是否在文章统计页中
            if ('dashboard_page_magick-census-single' != $hook) {
                return;
            }

            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_census-single',
                plugin_dir_url(\dirname(__FILE__)) . 'css/mm-census-style.css',
                array(),
                MAGICK_MIXTURE_VERSION,
                'all'
            );

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_echarts-single',
                plugin_dir_url(\dirname(__FILE__)) . 'js/echarts_v5.4.0.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );
        }

        //待渲染的内容
        public static function load_content()
        {
?>
            <!-- 在默认WordPress“包装”容器中创建标题 -->
            <div class="wrap magick_section">

                <!--标题-->
                <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                <!--展示图表内容-->
                <?php self::render_page() ?>
                <!--在保存设置时调用WordPress函数以呈现错误。 -->
                <?php settings_errors(); ?>
                <!-- 创建用于呈现选项的表单 -->
                <form method="post" action="options.php">
                    <?php settings_fields('sandbox_theme_display_options'); ?>
                    <?php do_settings_sections('sandbox_theme_display_options'); ?>
                    <?php submit_button(); ?>
                </form>


            </div><!-- /.wrap -->
            <?php
        }

        //添加设置选项
        public static function magick_plugin_options()
        {
            // 如果插件选项不存在，请创建它们。
            if (false == get_option('sandbox_theme_display_options')) {
                add_option('sandbox_theme_display_options');
            } // end if

            // 首先，我们注册一个部分。这是必要的，因为所有未来的选项都必须属于一个。
            add_settings_section(
                'sandbox_theme_display_option', // 用于标识此部分以及用于注册选项的ID
                '已统计人员ID', // 要在管理页面上显示的标题
                //'magick_plugin_options_callback', // 用于呈现节描述的回调
                array(__CLASS__, 'magick_plugin_options_callback'),
                'sandbox_theme_display_options' // 添加此部分选项的页面
            );

            //添加一个对钩选项
            add_settings_field(
                'option_id', // 用于标识整个主题中的字段的ID
                '待统计人员', // 选项接口元素左侧的标签
                //'magick_show_select_callback', // 负责呈现选项界面的函数的名称
                array(__CLASS__, 'magick_show_select_callback'),
                'sandbox_theme_display_options', // 将显示此选项的页面
                'sandbox_theme_display_option', // 此字段所属的节的名称
                array( // 要传递给回调的参数数组。在这种情况下，只是一个描述。
                    '选择需要监控的用户（排除订阅者）',
                )
            );

            //注册这个设置
            register_setting(
                'sandbox_theme_display_options', //选项组
                'magick_plugin_config', //选项名称
            );
        } //结束magick_plugin_options

        /**
         * 选择结果
         */
        public static function magick_plugin_options_callback()
        {
            //拿到选项的值
            $options = get_option('magick_plugin_config');
            if ($options) {
                echo "您选择的是人员ID是：" . implode(',', $options['option_id']);
                return;
            } else {
                echo "您没有选择值";
                return;
            }
        } //结束magick_plugin_options_callback

        /**
         * 选中框设置的回调
         */
        public static function magick_show_select_callback($args)
        {
            // 首先，我们拿到选项
            $options = get_option('magick_plugin_config');
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

                <input type='checkbox' name='magick_plugin_config[option_id][]' <?php checked(in_array($id, $uwcc_checkbox_field_1), 1); ?> value=<?php echo $id; ?>>
                <label class="magick-user-label"><?php echo $name; ?></label>
                &nbsp;&nbsp;|&nbsp;&nbsp;


            <?php
            } //end foreach
            ?>
            <!--描述-->
            <hr /><label for="option_id"> <?php echo $args[0]; ?></label>

        <?php

        } // end magick_show_select_callback

        /**
         * 统计页面基本框架
         */
        public static function render_page()
        {
            $tool = new Magick_Mixtrue_Tool;

            /**
             * 表格数据准备 - 周
             */
            $chart_data_week = self::get_user_release_arr()['week'];

            /**
             * 表格数据准备 - 月
             */
            $chart_data_month = self::get_user_release_arr()['month'];

            //看看里面有啥
            //$tool->p($chart);
            //$tool->p($chart_user);
            //$tool->p($chart_time);
            //$tool->p($chart_content);

            /**
             * 基础数据准备
             */
            $arr_data = $tool->get_site_census_data();

            //今天发文
            $count_today = $arr_data['today']['single'];
            //今天发评论
            $today_comments = $arr_data['today']['comments'];
            //今天注册
            $count_register = $arr_data['today']['register'];
            //总发文
            $total_single = $arr_data['total']['single'];
            //总用户
            $total_user = $arr_data['total']['register'];

        ?>

            <section class="magick_section">
                <div class="single-mixtrue">
                    <!--放统计图-->
                    <div id="magick-seven-census" style="width:700px;height:400px;"></div>
                    <!--放方框-->
                    <div class="magick-right">
                        <div class="magick-per">
                            <div class="per-content">
                                <div class="black-data-box-mix">
                                    <span>今日发文</span>
                                    <div class="child">
                                        <p><span><?php echo $count_today; ?></span>篇</p>
                                        <span class="dashicons dashicons-text-page"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="per-content">
                                <div class="black-data-box-mix">
                                    <span>今日评论</span>
                                    <div class="child">
                                        <p><span><?php echo $today_comments ?></span>篇</p>
                                        <span class="dashicons dashicons-format-status"></span>
                                    </div>
                                </div>
                            </div>

                            <div class="per-content">
                                <div class="black-data-box-mix">
                                    <span>今日注册</span>
                                    <div class="child">
                                        <p><span><?php echo $count_register; ?></span>次</p>
                                        <span class="dashicons dashicons-universal-access"></span>
                                    </div>
                                </div>
                            </div>


                        </div>

                        <div class="magick-per">
                            <div class="per-content">
                                <div class="black-data-box-mix">
                                    <span>总计发文</span>
                                    <div class="child">
                                        <p><span><?php echo $total_single; ?></span>篇</p>
                                        <span class="dashicons dashicons-clipboard"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="per-content ">
                                <div class="black-data-box-mix">
                                    <span>总计用户</span>
                                    <div class="child">
                                        <p><span><?php echo $total_user; ?></span>位</p>
                                        <span class="dashicons dashicons-universal-access-alt"></span>
                                    </div>
                                </div>
                            </div>



                        </div>
                    </div>
                </div>

            </section>
            <!--月度统计-->
            <section class="magick-census-single-month">
                <div id="magick-month-census" style="width:1200px;height:400px;"></div>
            </section>

            <script type="text/javascript">
                // 基于准备好的dom，初始化echarts实例
                let myChart_week = echarts.init(document.getElementById("magick-seven-census"));
                let myChart_month = echarts.init(document.getElementById("magick-month-census"));

                // 指定图表的配置项和数据
                let option_week = {
                    title: {
                        text: "一周发文统计",
                    },
                    tooltip: {},
                    legend: {
                        data: <?php echo $chart_data_week['user'] ?>,
                    },
                    xAxis: {
                        data: <?php echo $chart_data_week['time'] ?>,
                    },
                    yAxis: {},
                    series: <?php echo $chart_data_week['content'] ?>,
                };
                // 指定图表的配置项和数据
                let option_month = {
                    title: {
                        text: "月度发文统计",
                    },
                    tooltip: {},
                    legend: {
                        data: <?php echo $chart_data_month['user'] ?>,
                    },
                    xAxis: {
                        data: <?php echo $chart_data_month['time'] ?>,
                    },
                    yAxis: {},
                    series: <?php echo $chart_data_month['content'] ?>,
                };

                // 使用刚指定的配置项和数据显示图表。
                myChart_week.setOption(option_week);
                myChart_month.setOption(option_month);
            </script>

<?php
        }

        /**
         * 获取一批人的发文数量,一周，一个月
         */
        public static function get_user_release_arr()
        {
            $tool = new Magick_Mixtrue_Tool;
            //存储数组
            $arr = array();
            //拿到ID数组
            $options = get_option('magick_plugin_config');

            //默认查阅ID为1的人的发文数据
            $id = isset($options['option_id']) ? $options['option_id'] : [1];

            //获取周发文数量
            $week = array();
            foreach ($id as $key => $value) {
                //$week[$key] = $tool->get_count_user_week($value);
                $week[$key] = self::get_count_release($value)['week'];
            }

            //获取月发文数量
            $month = array();
            foreach ($id as $key => $value) {
                //$month[$key] = $tool->get_count_user_month($value);
                $month[$key] = self::get_count_release($value)['month'];
            }

            //将数据处理后存入数组
            $arr['week'] = self::optimize_chart_data($week);
            $arr['month'] = self::optimize_chart_data($month);

            return $arr;
        }

        /**
         * 输入人员ID，返回最近7天，本月发文数量
         * 输出：数组(array)
         */
        public static function get_count_release($id = '1')
        {
            $tool = new Magick_Mixtrue_Tool;
            //存储数据
            $arr = array();
            /**
             * 最近一周发文
             */
            //拿到时间数组
            $t_week = $tool->get_time()['a'];
            //表格需要，反转下时间
            $t_week = array_reverse($t_week);
            //开始循环
            for ($i = 0; $i < count((array) $t_week); $i++) {
                //拿到日期
                $time = $t_week[$i];
                $arr['week'][$i] = $tool->get_count_user($id, $time, 'publish');
            }
            /**
             * 本月发文数量
             */
            //拿到时间数组
            $t_month = $tool->get_time_long("this_month");
            //循环
            for ($i = 0; $i < count((array) $t_month); $i++) {
                //拿到日期
                $time = $t_month[$i];
                $arr['month'][$i] = $tool->get_count_user($id, $time, 'publish');
            }
            return $arr;
        }

        /**
         * 对拿到的列表数组进行优化，产出数组
         */
        public static function optimize_chart_data($chart)
        {
            //实例化工具
            $tool = new Magick_Mixtrue_Tool;
            //拿到作者名
            $chart_user = array();
            foreach ($chart as $key => $value) {
                $id = $value['0']['user_id'];
                $chart_user[$key] = $tool->get_user_data($id, 'display_name'); //拿到名字
            }

            //拿到时间
            $chart_time = array();
            foreach ($chart['0'] as $key => $value) {
                $time = $value['time'];
                $chart_time[$key] = date("d", strtotime($time));
            }

            //拿到数据
            $chart_content = array();
            foreach ($chart as $a => $b) {

                foreach ($b as $key => $value) {

                    $c[$key] = $value['total'];
                }
                $id = $b['0']['user_id'];
                $chart_content[$a]['name'] = $tool->get_user_data($id, 'display_name'); //拿到名字
                $chart_content[$a]['type'] = "bar";
                $chart_content[$a]['data'] = $c;
            }
            //存储数组
            //调整为表格用格式
            $arr = array();
            $arr['user'] = json_encode($chart_user);
            $arr['time'] = json_encode($chart_time);
            $arr['content'] = json_encode($chart_content);
            return $arr;
        } //end 数据优化

    } //end class
}
