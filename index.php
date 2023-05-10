<?php //沉默是金
function my_custom_function()
{
    $data1 = $_POST['data1'];
    $data2 = $_POST['data2'];

    // 处理请求，并生成响应数据
    $response = array(
        'status' => 'success',
        'message' => '处理下：Received data1=' . $data1 . ' and  data2=' . $data2,
    );

    // 返回响应数据
    wp_send_json($response);
}

// 注册动作钩子
add_action('wp_ajax_my_custom_function', 'my_custom_function');
add_action('wp_ajax_nopriv_my_custom_function', 'my_custom_function');

// 创建图片展示次数表
function create_image_view_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'npc_ad_count';
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE $table_name (
      id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      identify BIGINT(20) UNSIGNED NOT NULL,
      click_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (id)
  ) $charset_collate;";
    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql);
}
add_action('init', 'create_image_view_table');

// 处理图片展示次数ajax请求
function record_image_view()
{

    global $wpdb;
    $table_name = $wpdb->prefix . 'npc_ad_count';

    //获取图片ID
    $image_id = $_POST['image_id'];

    echo "<script>console.log('我打印了')</script>" . $image_id;
    // 插入记录到数据库中
    $wpdb->insert(
        $table_name,
        array(
            'identify' => $image_id,

        )
    );

    exit;
}
add_action("wp_ajax_record_image_view", "record_image_view");
add_action("wp_ajax_nopriv_record_image_view", "record_image_view");

// 在 WordPress 后台管理界面中添加一个菜单链接
add_action('admin_menu', 'my_admin_menu');
function my_admin_menu()
{
    add_menu_page('Image Views', '广告统计', 'manage_options', 'image-views', 'show_image_views');
}

// 显示图片展示次数的函数
function show_image_views()
{
    global $wpdb;
    $start_date = '';
    $end_date = '';
    $date_filter = isset($_POST['date_filter']) ? $_POST['date_filter'] : 'all';
    $date_filters = [
        'today' => '今天',
        'yesterday' => '昨天',
        'last_week' => '过去一周',
        'this_month' => '本月',
        'last_month' => '上月',
        'all' => '总计',
    ];

    $filter_options = '';
    foreach ($date_filters as $key => $label) {
        $selected = ($key === $date_filter) ? 'selected' : '';
        $filter_options .= sprintf('<option value="%s" %s>%s</option>', $key, $selected, $label);
    }

    if (isset($_POST['start_date']) && isset($_POST['end_date'])) {
        $start_date = $_POST['start_date'];
        $end_date = $_POST['end_date'];
    }

    $table_name = $wpdb->prefix . 'npc_ad_count';
    $where_clause = '';

    switch ($date_filter) {
        case 'today':
            $where_clause = "WHERE DATE(click_time) = CURDATE()";
            break;
        case 'yesterday':
            $where_clause = "WHERE DATE(click_time) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            break;
        case 'last_week':
            $where_clause = "WHERE click_time >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            break;
        case 'this_month':
            $where_clause = "WHERE YEAR(click_time) = YEAR(CURDATE()) AND MONTH(click_time) = MONTH(CURDATE())";
            break;
        case 'last_month':
            $where_clause = "WHERE PERIOD_DIFF(EXTRACT(YEAR_MONTH FROM CURDATE()), EXTRACT(YEAR_MONTH FROM click_time)) = 1";
            break;
        default:
            $where_clause = "";
            break;
    }

    if (!empty($start_date) && !empty($end_date)) {
        $where_clause = sprintf("WHERE DATE(click_time) BETWEEN '%s' AND '%s'", $start_date, $end_date);
    }

    //$rows = $wpdb->get_results("SELECT identify, DATE(click_time) as date, COUNT(*) as count FROM $table_name $where_clause GROUP BY identify, DATE(click_time)");
    $rows = $wpdb->get_results("SELECT identify, DATE(click_time) as date, COUNT(*) as count
    FROM $table_name $where_clause
    GROUP BY identify, DATE(click_time)
    ORDER BY MIN(click_time) ASC, identify ASC");

    echo '<h1>广告统计</h1>';
    echo '<form method="post">';
    echo '<select name="date_filter">';
    echo $filter_options;
    echo '</select>';

    echo '<label for="start_date">开始日期：</label>';
    echo sprintf('<input type="date" name="start_date" id="start_date" value="%s">', $start_date);
    echo '<label for="end_date">结束日期：</label>';
    echo sprintf('<input type="date" name="end_date" id="end_date" value="%s">', $end_date);

    echo '<input type="submit" value="筛选">';
    echo '</form>';

    //echo '<table class="widefat">';
    //echo '<thead><tr><th>ID</th><th>展示日期</th><th>展示次数</th></tr></thead>';
    //echo '<tbody>';
    //foreach ($rows as $row) {
    //    echo sprintf('<tr><td>%d</td><td>%s</td><td>%d</td></tr>', $row->identify, $row->date, $row->count);
    //}
    //echo '</tbody>';
    //echo '</table>';

    //---------------------------------获取数据

    $data = array();
    //echo var_dump($rows);
    foreach ($rows as $row) {
        $data[] = array(

            'id' => $row->identify, //广告ID
            'date' => $row->date, //时间
            'count' => $row->count, //展现次数
        );
    }

    // Enqueue the script file
    wp_enqueue_script('my-image-views-vue', plugin_dir_url(__FILE__) . 'js/vue.global.js', array(), '1.0', true);
    wp_enqueue_script('my-image-views-echarts', plugin_dir_url(__FILE__) . 'js/echarts.js', array(), '1.0', true);
    wp_enqueue_script('my-image-views-script', plugin_dir_url(__FILE__) . 'js/my-image-views.js', array(), '1.7', true);

    wp_add_inline_script('my-image-views-script', sprintf('const imageViewsData = %s;', json_encode($data)), 'before');

    // Display the menu HTML

    echo '
        <br />

        <div id="Application"></div>

    ';
}

//获取最近3个月的数据
function get_image_view_data()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'npc_ad_count';

    // 计算三个月前的时间
    $three_months_ago = strtotime('-3 months');

    // 使用 WordPress 提供的 $wpdb 对象构造 SQL 查询语句
    $sql = $wpdb->prepare("
        SELECT *
        FROM $table_name
        WHERE click_time >= '%s'
        ORDER BY click_time DESC
    ", date('Y-m-d H:i:s', $three_months_ago));

    // 执行 SQL 查询，获取结果
    $result = $wpdb->get_results($sql);

    // 返回结果
    return $result;
}
