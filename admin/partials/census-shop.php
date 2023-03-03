<?php
/**
 * 商城统计
 */

if (!class_exists('Magick_Mixtrue_Census_Shop')) {
    class Magick_Mixtrue_Census_Shop
    {

        public function __construct()
        {

        }

        public static function run()
        {
            //加载菜单
            add_action('admin_menu', array(__CLASS__, 'add_menu_shop'));
            //加载图标用js
            add_action('admin_enqueue_scripts', array(__CLASS__, 'load_enqueue_admin_script'));

        }

        /**
         * 添加商城菜单
         */
        public static function add_menu_shop()
        {
            add_submenu_page('index.php', __('销售统计'), __('销售统计'), 'administrator', 'magick-census-shop', array(__CLASS__, 'load_content'));
        }

        //页面加载图标用css和js
        public static function load_enqueue_admin_script($hook)
        {
            //判断下，是否在当前页面
            if ('dashboard_page_magick-census-shop' != $hook) {
                return;
            }
            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_census-b2-shop',
                plugin_dir_url(\dirname(__FILE__)) . 'css/mm-census-b2-shop.css',
                array(),
                MAGICK_MIXTURE_VERSION,
                'all'
            );

            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_echarts-shop',
                plugin_dir_url(\dirname(__FILE__)) . 'js/echarts_v5.4.0.js',
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );

        }

        /**
         * 拿到指定时间内的所有数据
         */
        public static function get_sql_data()
        {
            //用WordPress提供的全局变量
            global $wpdb;
            //实例化工具
            $tool = new Magick_Mixtrue_Tool;
            $time = $tool->get_time();
            $time = $time['a'];
            $table_name = $wpdb->prefix . 'zrz_order';

            //获取近7天的销售数据
            $order_data_seven = "SELECT order_type,order_commodity,order_state,order_date,order_total FROM $table_name WHERE  order_date > '$time[6]'";
            //原始数据
            $order_data = $wpdb->get_results($order_data_seven, ARRAY_A);

            //整理 - 将拿到的数据以时间为键名保存
            $array_data_time = array();
            //找到符合当前时间的值
            foreach ($order_data as $v) {
                //拿到当前键值的时间
                $value_time = $v['order_date'];
                //格式下一下
                $handle_value = date("Y-m-d", strtotime($value_time));
                $array_data_time[$handle_value][] = $v;
            }

            //$tool->p($array_data_time);

            //转成数组，每天的总销售额、总订单、总退款额、总退款订单

            /**
             * 筛选
             * 获取最近7天每天的销售数组
             */
            $order_seven_total = array();

            for ($i = 0; $i < count((array) $time); $i++) {
                //准备默认值
                $arr_default = array();

                //拿到时间格式化
                $t = date("Y-m-d", strtotime($time[$i]));

                //拿到当前时间的值，没有当前时间的，则为空值
                $value = isset($array_data_time[$t]) ? $array_data_time[$t] : $arr_default;
                //对数据进行筛查，保留所需数据

                $data = array_filter($value, function ($v) {
                    $switch = false;
                    //商城订单
                    if ($v['order_type'] == 'gx') {
                        //实物
                        if ($v['order_commodity'] == '1') {
                            //已发货
                            if ($v['order_state'] == 'q') {$switch = true;}
                            //已签收
                            if ($v['order_state'] == 'c') {$switch = true;}
                        }
                    }
                    return $switch;
                });

                //获取总销售额
                $total = 0;
                foreach ($data as $v) {

                    $total += $v['order_total'];
                }
                //时间
                $order_seven_total[$t]['time'] = $t;

                //总销售额
                $order_seven_total[$t]['total'] = $total;

                //总销售订单 - 统计筛选后的数组有多少个
                $order_seven_total[$t]['order'] = count((array) $data);

                //拿到的数组键名会乱，这里重置下键名
                $order_seven_total[$t]['data'] = array_values($data);

            } //end for
            //$tool->p($order_seven_total);

            /**
             * 获取最近7天退款数组
             */
            $total_refund_data = array();
            for ($i = 0; $i < count((array) $time); $i++) {
                //准备默认值
                $arr_default = array();
                //拿到时间格式化
                $t = date("Y-m-d", strtotime($time[$i]));
                //先判断下，有时间则通过，没有时间则给默认值

                //拿到当前时间的值，没有当前时间的，则为空值
                $value = isset($array_data_time[$t]) ? $array_data_time[$t] : $arr_default;
                //对数据进行筛查，保留所需数据
                $data = array_filter($value, function ($v) {
                    $switch = false;
                    //商城订单
                    if ($v['order_type'] == 'gx') {
                        //实物
                        if ($v['order_commodity'] == '1') {
                            //已退款
                            if ($v['order_state'] == 't') {$switch = true;}
                        }
                    }
                    return $switch;
                });

                //获取退款总销售额
                $total = 0;
                foreach ($data as $v) {
                    $total += $v['order_total'];
                }

                $total_refund_data[$t]['time'] = $t;
                $total_refund_data[$t]['total'] = $total;
                $total_refund_data[$t]['order'] = count((array) $data);
                $total_refund_data[$t]['data'] = array_values($data);

            }
            //$tool->p($total_refund_data);
            /**
             * 待发货订单
             */
            $shipped_order = $wpdb->get_var("SELECT COUNT(*) FROM $table_name where order_state='f'");

            //创建数组，存储数据
            $array = array();
            //已售
            $array['sale'] = $order_seven_total;
            //已退
            $array['refund'] = $total_refund_data;
            //待发
            $array['shipped'] = $shipped_order;

            return $array;
        } //end get_sql_data()

        /**
         * 对拿到的数据进行二次处理
         */
        public static function handle_order_seven()
        {
            //实例化工具类
            $tool = new Magick_Mixtrue_Tool;
            //拿到数据
            $data = self::get_sql_data();
            //拿到时间
            $time = $tool->get_time();
            $time = $time['a'];

            //存储数据
            $arr = array();
            /**
             * 待发货订单
             */
            $arr['shipped'] = $data['shipped'];

            /**
             *今日统计
             */
            //今天的时间
            $today_time = date("Y-m-d", strtotime($time[0]));

            //今日总销售额
            $arr['today']['sale'] = $data['sale'][$today_time]['total'];

            //今日总订单
            $arr['today']['sale_order'] = $data['sale'][$today_time]['order'];

            //今日总退款
            $arr['today']['refund'] = $data['refund'][$today_time]['total'];

            //今日总退款订单
            $arr['today']['refund_order'] = $data['refund'][$today_time]['order'];

            /**
             * 表格用数据
             */
            //时间
            foreach ($time as $value) {
                $t[] = date("d", strtotime($value));
            }

            //最近7天总销售额
            $seven_sale_total = array();
            $seven_sale_total = array_column($data['sale'], 'total');
            //最近7天总订单
            $seven_sale_order = array();
            $seven_sale_order = array_column($data['sale'], 'order');

            //最近7天总退款
            $seven_refund_total = array();
            $seven_refund_total = array_column($data['refund'], 'total');

            //最近7天总退款订单
            $seven_refund_order = array();
            $seven_refund_order = array_column($data['refund'], 'order');

            //时间
            $arr['time'] = $t;
            $arr['latelly']['sale'] = $seven_sale_total;
            $arr['latelly']['sale_order'] = $seven_sale_order;
            $arr['latelly']['refund'] = $seven_refund_total;
            $arr['latelly']['refund_order'] = $seven_refund_order;
            //$tool->p($arr);
            return $arr;

        } //  handle_order_seven()

        /**
         * 月数据
         * 月总订单数（去退款）
         * 月总销售额（去退款）
         * 月总退款订单数
         * 月总退款额
         */
        public static function get_month_order()
        {
            //用WordPress提供的全局变量
            global $wpdb;
            //拿到表
            $table_name = $wpdb->prefix . 'zrz_order';
            //实例化工具
            $tool = new Magick_Mixtrue_Tool;
            //拿到时间
            $math = $tool->get_time_long('this_month');

            $start = '';
            $end = '';
            $start = $tool->export_handle_time('start', reset($math));
            $end = $tool->export_handle_time('end', end($math));
            //存储数据
            $arr = array();

            //开始查询
            //总销售额
            $judge_later_a = "SELECT SUM(BINARY(order_total)) AS total FROM $table_name WHERE order_type = 'gx' and order_commodity = 1 and (order_state = 'c' or order_state = 'q') and order_date > '$start' and order_date < '$end'";
            //总订单数
            $judge_later_b = "SELECT COUNT(*) FROM $table_name WHERE order_type = 'gx' and order_commodity = 1 and (order_state = 'c' or order_state = 'q') and order_date > '$start' and order_date < '$end'";

            //总退款
            $judge_later_c = "SELECT SUM(BINARY(order_total)) AS refund FROM $table_name WHERE order_type = 'gx' and order_commodity = 1 and order_state = 't' and order_date > '$start' and order_date < '$end'";

            //总退款订单数
            $judge_later_d = "SELECT COUNT(*) FROM $table_name WHERE order_type = 'gx' and order_commodity = 1 and  order_state = 't' and order_date > '$start' and order_date < '$end'";
            //第二天到第7天拿到的值
            //总销售额
            $arr['total_sales'] = isset(($wpdb->get_results($judge_later_a, ARRAY_A))['0']['total']) ? ($wpdb->get_results($judge_later_a, ARRAY_A))['0']['total'] : 0;

            //总订单数
            $arr['total_order'] = $wpdb->get_var($judge_later_b);

            //总退款
            $arr['total_refund_sales'] = isset(($wpdb->get_results($judge_later_c, ARRAY_A))['0']['refund']) ? ($wpdb->get_results($judge_later_c, ARRAY_A))['0']['refund'] : 0;

            //总退款订单
            $arr['total_refund_order'] = $wpdb->get_var($judge_later_d);

            return $arr;

        }

        //页面显示内容
        public static function load_content()
        {

            ?>
                     <!-- 在默认WordPress“包装”容器中创建标题 -->
                    <div class="wrap magick-content">

                    <!--标题-->
                     <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                     <hr />


                    <!--五栏数据-->
                    <?php echo self::week_data_show() ?>
                    <!--四栏分隔-->
                    <?php echo self::load_echarts_js() ?>
                    <!--月度统计-->
                    <?php echo self::month_content() ?>

                    </div><!-- end wrap-->
                    <?php
} //end load_content

        /**
         * 五栏数据展示
         */
        public static function week_data_show()
        {
            /**
             * 准备数据
             */
            $arr = self::handle_order_seven();
            ?>
                         <section class="magick_shop_box">
                    <div class="content">
                        <div class="child-box">
                            <span>待发货</span>
                            <div class="child">
                                <p><span><?php echo $arr['shipped'] ? $arr['shipped'] : 0 ?></span>个</p>
                                <span class="dashicons dashicons-store"></span>
                            </div>
                        </div>
                    </div>
                    <div class="content">
                        <div class="child-box">
                            <span>今日总销售额（已减退款）</span>
                            <div class="child">
                                <p><span><?php echo $arr['today']['sale'] ?></span>￥</p>
                                <span class="dashicons dashicons-insert"></span>
                            </div>

                        </div>
                    </div>
                    <div class="content">
                        <div class="child-box">
                            <span>今日总订单（已减退款）</span>
                            <div class="child">
                                <p><span><?php echo $arr['today']['sale_order'] ?></span>个</p>
                                <span class="dashicons dashicons-database-add"></span>
                            </div>
                        </div>
                    </div>
                    <div class="content">
                        <div class="child-box">
                            <span>今日总退款</span>
                            <div class="child">
                                <p><span><?php echo $arr['today']['refund'] ?></span>￥</p>
                                <span class="dashicons dashicons-remove"></span>
                            </div>
                        </div>
                    </div>
                    <div class="content">
                        <div class="child-box">
                            <span>今日总退款订单</span>
                            <div class="child">
                                <p><span><?php echo $arr['today']['refund_order'] ?></span>个</p>
                                <span class="dashicons dashicons-database-remove"></span>
                            </div>
                        </div>
                    </div>

                </section>
            <?php
}

        /**
         * 四栏统计表
         */
        public static function load_echarts_js()
        {
            //准备数据
            $arr = self::handle_order_seven();
            //时间
            $time = $arr['time'];

            ?>
                <style>
                .magick_four-column .content > div {
                  width: 600px;
                  height: 300px;
                }
                </style>
            <section class="magick_four-column">
                <div class="content">
                    <!--最近7天总销售额-->
                    <div id="total-sales"></div>
                </div>
                <div class="content">
                    <!--最近7天总销售订单-->
                    <div id="total-order"></div>
                </div>
                <div class="content">
                    <!--最近7天总退款销售额-->
                    <div id="total-refund"></div>
                </div>
                <div class="content">
                    <!--最近7天总退款订单-->
                    <div id="total-refund-order"></div>
                </div>
            </section>
            <script type="text/javascript">
                // 基于准备好的dom，初始化echarts实例
                //最近7天总销售额
                let total_sales = echarts.init(document.getElementById("total-sales"));
                //最近7天总销售订单
                let total_order = echarts.init(document.getElementById("total-order"));
                //最近7天总退款销售额
                let total_refund = echarts.init(document.getElementById("total-refund"));
                //最近7天总退款订单
                let total_refund_order = echarts.init(document.getElementById("total-refund-order"));



                // 指定图表的配置项和数据
                let total_sales_option = {
                    title: {
                        text: "最近7天总销售额（已减退款额）",
                    },
                    tooltip: {
                        valueFormatter: (value) =>  value.toFixed(2)+'￥'
                    },
                    xAxis: {
                        type: 'category',
                        //data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                        data: [<?php echo implode(',', array_reverse($arr['time'])) ?>]
                    },
                    yAxis: {
                        type: 'value'
                    },
                    series: [
                        {
                            //data: [120, 200, 150, 80, 70, 110, 130],
                             data: [<?php echo implode(',', array_reverse($arr['latelly']['sale'])) ?>],
                            type: 'bar',
                            name:"总销售额",
                            showBackground: true,
                            backgroundStyle: {
                                color: 'rgba(180, 180, 180, 0.2)'
                            },
                           label:{
                                show:true,
                                position: 'insideTop', //在上方显示
                                textStyle: { //数值样式
                                    color: '#fff',
                                    fontSize: 12,
                                     fontWeight:'bold',
                                }
                            }






                        }
                    ]
                };

                let total_order_option = {
                    title: {
                        text: "最近7天总销售订单（已减退款订单）",
                    },
                    tooltip: {
                        valueFormatter: (value) =>  value.toFixed(0)+'个'
                    },
                    xAxis: {
                        type: 'category',
                        //data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                        data: [<?php echo implode(',', array_reverse($arr['time'])) ?>]
                    },
                    yAxis: {
                        type: 'value'
                    },
                    series: [
                        {
                            //data: [120, 200, 150, 80, 70, 110, 130],
                            data: [<?php echo implode(',', array_reverse($arr['latelly']['sale_order'])) ?>],
                           type: 'bar',
                            name:"总销售订单",
                            showBackground: true,
                            backgroundStyle: {
                                color: 'rgba(180, 180, 180, 0.2)'
                            },
                           label:{
                                show:true,
                                position: 'insideTop', //在上方显示
                                textStyle: { //数值样式
                                    color: '#fff',
                                    fontSize: 12,
                                     fontWeight:'bold',
                                }
                            }
                        }
                    ]
                };

                let total_refund_option = {
                    title: {
                        text: "最近7天总退款额",
                    },
                    tooltip: {
                        valueFormatter: (value) =>  value.toFixed(2)+'￥'
                    },
                    xAxis: {
                        type: 'category',
                        //data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                        data: [<?php echo implode(',', array_reverse($arr['time'])) ?>],
                    },
                    yAxis: {
                        type: 'value'
                    },
                    series: [
                        {
                            //data: [120, 200, 150, 80, 70, 110, 130],
                            data: [<?php echo implode(',', array_reverse($arr['latelly']['refund'])) ?>],
                            type: 'bar',
                            name:"总退款额",
                            showBackground: true,
                            backgroundStyle: {
                                color: 'rgba(180, 180, 180, 0.2)'
                            },
                           label:{
                                show:true,
                                position: 'insideTop', //在上方显示
                                textStyle: { //数值样式
                                    color: '#fff',
                                    fontSize: 12,
                                     fontWeight:'bold',
                                }
                            }


                        }
                    ]
                };

                let total_refund_order_option = {
                    title: {
                        text: "最近7天总退款订单",
                    },
                    tooltip: {
                        valueFormatter: (value) => value.toFixed(0) + '个'
                    },
                    xAxis: {
                        type: 'category',
                        //data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun']
                        data: [<?php echo implode(',', array_reverse($arr['time'])) ?>],
                    },
                    yAxis: {
                        type: 'value'
                    },
                    series: [
                        {
                            //data: [120, 200, 150, 80, 70, 110, 130],
                            data: [<?php echo implode(',', array_reverse($arr['latelly']['refund_order'])) ?>],
                            type: 'bar',
                            name:"总退款订单",
                            showBackground: true,
                            backgroundStyle: {
                                color: 'rgba(180, 180, 180, 0.2)'
                            },
                           label:{
                                show:true,
                                position: 'insideTop', //在上方显示
                                textStyle: { //数值样式
                                    color: '#fff',
                                    fontSize: 12,
                                     fontWeight:'bold',
                                }
                            }


                        }
                    ]
                };

                // 使用刚指定的配置项和数据显示图表。
                //最近7天总销售额
                total_sales.setOption(total_sales_option);
                //最近7天总销售订单
                total_order.setOption(total_order_option);
                //最近7天总退款销售额
                total_refund.setOption(total_refund_option);
                //最近7天总退款订单
                total_refund_order.setOption(total_refund_order_option);


                </script>

            <?php

        } //end load_echarts_js()

        /**
         * 月度数据展示
         */
        public static function month_content()
        {
            //本月数据
            $month = self::get_month_order();
            ?>
                        <!--月度统计-->
                <section class="magick_shop_box">

            <div class="content">
                <div class="child-box">
                    <span>本月总销售额（已减退款）</span>
                    <div class="child">
                        <p><span><?php echo $month['total_sales'] ?></span>￥</p>
                        <span class="dashicons dashicons-insert"></span>
                    </div>

                </div>
            </div>
            <div class="content">
                <div class="child-box">
                    <span>本月总订单（已减退款）</span>
                    <div class="child">
                        <p><span><?php echo $month['total_order'] ? $month['total_order'] : 0 ?></span>个</p>
                        <span class="dashicons dashicons-database-add"></span>
                    </div>
                </div>
            </div>
            <div class="content">
                <div class="child-box">
                    <span>本月总退款</span>
                    <div class="child">
                        <p><span><?php echo $month['total_refund_sales'] ?></span>￥</p>
                        <span class="dashicons dashicons-remove"></span>
                    </div>
                </div>
            </div>

            <div class="content">
                <div class="child-box">
                    <span>本月总退款订单</span>
                    <div class="child">
                        <p><span><?php echo $month['total_refund_order'] ? $month['total_refund_order'] : 0 ?></span>个</p>
                        <span class="dashicons dashicons-database-remove"></span>
                    </div>
                </div>
            </div>

            </section>
            <?php
}

    } //end class
}
