<?php
//下载指定数据库表内容
if (!class_exists('MaBox_Download_SQL_Table')) {
    class MaBox_Download_SQL_Table
    {
        public static function run()
        {
            // 提供数据库表格数据
            add_action('wp_ajax_get_all_table_names', array(__CLASS__, 'get_all_table_names'));

            // 提供数据库表格数据下载
            add_action('wp_ajax_get_table_data', array(__CLASS__, 'get_table_data'));
        }

        //获取所有的数据库表名
        public static function get_all_table_names()
        {
            global $wpdb;

            //管理员权限
            if (!current_user_can('manage_options')) {
                return wp_send_json_error(['error' => '非管理员，无权获取此内容', 'data' => []], 404);
            }


            //获取所有表名
            $results = $wpdb->get_results("SHOW TABLES", ARRAY_N);

            $table_names = array();

            foreach ($results as $result) {
                $table_names[] = $result[0];
            }


            // 如果 $table_names 是空数组，则返回空数据
            if (empty($table_names)) {
                wp_send_json_error(['error' => '获取数据库表名失败', 'data' => []], 404);
            } else {
                // 返回响应数据
                wp_send_json_success(['msg' => '成功获取数据库表名', 'data' => $table_names]);
            }
        }

        //获取表格数据
        public static function get_table_data()
        {
            global $wpdb;

            //管理员权限
            if (!current_user_can('manage_options')) {
                return  wp_send_json_error(['error' => '非管理员，无权获取此内容', 'data' => []], 404);
            }

            // 检查是否传递了数据库名
            if (empty($_POST['databaseName'])) {
                return wp_send_json_error(['error' => '没有拿到表名',], 400);
            }

            $databaseName = sanitize_text_field($_POST['databaseName']); // 对数据库名进行安全过滤

            //待查询的表名
            $searchTableName = "{$databaseName}";

            // 检查数据库表是否存在
            $existingTableName = $wpdb->get_var("SHOW TABLES LIKE '{$searchTableName}'");
            if ($existingTableName !== $searchTableName) {
                return wp_send_json_error([
                    'error' => '该表不存在',
                ], 404);
            }

            $query = "SELECT * FROM {$searchTableName}"; // 使用预处理语句构建查询语句
            $results = $wpdb->get_results($query, ARRAY_A); // 执行查询并获取数组结果

            // 检查查询结果是否为空
            if (!$results) {
                return wp_send_json_error(['error' => '没有查到表格的数据，可能该表为空',], 404);
            }

            $filename = $databaseName . '.csv'; // 生成要下载的文件名

            // 创建 CSV 文件并写入表头
            $file = fopen($filename, 'w');
            $header = array_keys((array) $results[0]); // 获取第一行数据的属性名作为表头
            fputcsv($file, $header); //将表头写入到 CSV 文件中。

            // 写入查询结果
            foreach ($results as $row) {
                fputcsv($file, (array) $row);
            }
            fclose($file); //关闭文件句柄，完成文件写入操作。

            // readfile($filename);//将指定的文件发送给浏览器，完成下载操作。
            // 读取文件内容
            $file_content = file_get_contents($filename);

            // 如果文件内容读取成功，就将数据传递给前端
            if ($file_content !== false) {
                wp_send_json_success(['data' => $file_content, 'message' => '下载成功']);
            } else {
                wp_send_json_error(['error' => '无法读取文件内容',], 400);
            }

            // 删除临时文件
            unlink($filename);
        }
    }
}
