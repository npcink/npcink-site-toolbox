<?php
//提供各种数据接口
class MaMi_Interface
{
    public static function run()
    {
        // 提供数据库表格数据
        add_action('wp_ajax_get_all_table_names', array(__CLASS__, 'get_all_table_names'));
       // add_action('wp_ajax_nopriv_get_all_table_names', array(__CLASS__, 'get_all_table_names'));

        // 提供数据库表格数据下载
        add_action('wp_ajax_get_table_data', array(__CLASS__, 'get_table_data'));
       // add_action('wp_ajax_nopriv_get_table_data', array(__CLASS__, 'get_table_data'));
    }

    //获取所有的数据库表名
    public static function get_all_table_names()
    {
        global $wpdb;

        $table_names = $wpdb->tables();

        // 处理请求，并生成响应数据
        $response = array(
            'data' =>  $table_names,
        );

        // 返回响应数据
        wp_send_json($response);
    }

    //获取表格数据
    public static function get_table_data()
    {
        global $wpdb;
        $databaseName = $_POST['databaseName']; // 假设数据库名通过 POST 请求传递

        $query = "SELECT * FROM $databaseName"; // 根据数据库名构建查询语句
        $results = $wpdb->get_results($query); // 执行查询

        $filename = $databaseName . '.csv'; // 生成要下载的文件名

        // 创建 CSV 文件并写入表头
        $file = fopen($filename, 'w');
        $header = array_keys((array) $results[0]); // 获取第一行数据的属性名作为表头
        fputcsv($file, $header);

        // 写入查询结果
        foreach ($results as $row) {
            fputcsv($file, (array) $row);
        }
        fclose($file);

        // 设置下载头部
        header('Content-Type: application/csv');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Pragma: no-cache');
        readfile($filename);

        // 删除临时文件
        unlink($filename);
    }
}
