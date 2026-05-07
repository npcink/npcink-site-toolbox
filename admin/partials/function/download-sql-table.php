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

            // Nonce 验证
            check_ajax_referer('mabox_save_nonce', 'nonce');

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

            // Nonce 验证
            check_ajax_referer('mabox_save_nonce', 'nonce');

            // 检查是否传递了数据库名
            if (empty($_POST['databaseName'])) {
                return wp_send_json_error(['error' => '没有拿到表名',], 400);
            }

            $databaseName = sanitize_text_field(wp_unslash($_POST['databaseName']));

            // 白名单验证：表名只能包含字母、数字、下划线
            if (!preg_match('/^[a-zA-Z0-9_]+$/', $databaseName)) {
                return wp_send_json_error(['error' => '非法表名'], 400);
            }

            //待查询的表名
            $searchTableName = $databaseName;

            // 检查数据库表是否存在
            $existingTableName = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $searchTableName));
            if ($existingTableName !== $searchTableName) {
                return wp_send_json_error([
                    'error' => '该表不存在',
                ], 404);
            }

            $query = "SELECT * FROM `{$searchTableName}`";
            $results = $wpdb->get_results($query, ARRAY_A);

            // 检查查询结果是否为空
            if (!$results) {
                return wp_send_json_error(['error' => '没有查到表格的数据，可能该表为空',], 404);
            }

            // 使用内存流代替临时文件，避免竞态条件
            $stream = fopen('php://temp', 'r+');
            $header = array_keys((array) $results[0]);
            fputcsv($stream, $header);

            foreach ($results as $row) {
                fputcsv($stream, (array) $row);
            }

            rewind($stream);
            $file_content = stream_get_contents($stream);
            fclose($stream);

            if ($file_content !== false) {
                wp_send_json_success(['data' => $file_content, 'message' => '下载成功']);
            } else {
                wp_send_json_error(['error' => '无法读取文件内容',], 400);
            }
        }
    }
}
