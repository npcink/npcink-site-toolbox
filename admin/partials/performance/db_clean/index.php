<?php
if (!class_exists('MaBox_Performance_Db_Clean')) {
    class MaBox_Performance_Db_Clean {
        private static $config;
        public static function run($config) {
            self::$config = $config;
            if (!empty($config['auto_clean'])) {
                $schedule = !empty($config['auto_clean_schedule']) ? $config['auto_clean_schedule'] : 'weekly';
                if (!wp_next_scheduled('mabox_auto_db_clean')) {
                    wp_schedule_event(time(), $schedule, 'mabox_auto_db_clean');
                }
                add_action('mabox_auto_db_clean', array(__CLASS__, 'auto_clean'));
            } else {
                $timestamp = wp_next_scheduled('mabox_auto_db_clean');
                if ($timestamp) wp_unschedule_event($timestamp, 'mabox_auto_db_clean');
            }
            add_filter('cron_schedules', array(__CLASS__, 'add_cron_schedules'));
        }
        public static function add_cron_schedules($schedules) {
            $schedules['weekly'] = array('interval' => 604800, 'display' => '每周');
            $schedules['monthly'] = array('interval' => 2592000, 'display' => '每月');
            return $schedules;
        }
        public static function ajax_stats() {
            if (!current_user_can('manage_options')) wp_send_json_error('权限不足', 403);
            global $wpdb;
            $stats = array();
            $stats['revisions'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", 'revision')));
            $stats['drafts'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'auto-draft')));
            $stats['spam'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s", 'spam')));
            $stats['transients'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s", '_transient_%')));
            $db_size = $wpdb->get_var("SELECT SUM(data_length + index_length) FROM information_schema.tables WHERE table_schema = DATABASE()");
            $stats['db_size'] = size_format($db_size ?: 0);
            wp_send_json_success($stats);
        }

        /**
         * 预览清理影响（dry-run）
         */
        public static function ajax_preview() {
            if (!current_user_can('manage_options')) wp_send_json_error('权限不足', 403);
            global $wpdb;

            $preview = array();
            $preview['revisions'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", 'revision')));
            $preview['drafts'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'auto-draft')));
            $preview['spam'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s", 'spam')));
            $preview['transients'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_%', '_site_transient_%')));

            // 获取待发布文章数（可选清理项）
            $preview['pending'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'pending')));
            $preview['trash'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'trash')));

            wp_send_json_success($preview);
        }

        public static function ajax_clean(\WP_REST_Request $request) {
            if (!current_user_can('manage_options')) wp_send_json_error('权限不足', 403);

            $params = $request->get_json_params();
            $type = isset($params['type']) ? sanitize_text_field($params['type']) : '';
            $dry_run = isset($params['dry_run']) ? rest_sanitize_boolean($params['dry_run']) : true; // 默认 dry-run

            // 验证清理类型
            $allowed_types = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'all', 'pending', 'trash');
            if (!in_array($type, $allowed_types, true)) {
                wp_send_json_error('无效的清理类型', 400);
            }

            // Dry-run 模式：只预览，不执行
            if ($dry_run) {
                $preview = array();
                global $wpdb;

                switch ($type) {
                    case 'revisions':
                        $preview['affected'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", 'revision')));
                        $preview['message'] = '将删除 ' . $preview['affected'] . ' 个文章修订版本';
                        break;
                    case 'drafts':
                        $preview['affected'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'auto-draft')));
                        $preview['message'] = '将删除 ' . $preview['affected'] . ' 个自动草稿';
                        break;
                    case 'spam':
                        $preview['affected'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s", 'spam')));
                        $preview['message'] = '将删除 ' . $preview['affected'] . ' 条垃圾评论';
                        break;
                    case 'transients':
                        $preview['affected'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_%', '_site_transient_%')));
                        $preview['message'] = '将删除 ' . $preview['affected'] . ' 个临时选项';
                        break;
                    case 'pending':
                        $preview['affected'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'pending')));
                        $preview['message'] = '将删除 ' . $preview['affected'] . ' 个待审核文章';
                        break;
                    case 'trash':
                        $preview['affected'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'trash')));
                        $preview['message'] = '将删除 ' . $preview['affected'] . ' 个回收站文章';
                        break;
                    case 'all':
                        $preview['revisions'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s", 'revision')));
                        $preview['drafts'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'auto-draft')));
                        $preview['spam'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s", 'spam')));
                        $preview['transients'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_%', '_site_transient_%')));
                        $preview['pending'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'pending')));
                        $preview['trash'] = intval($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s", 'trash')));
                        $preview['total'] = $preview['revisions'] + $preview['drafts'] + $preview['spam'] + $preview['transients'] + $preview['pending'] + $preview['trash'];
                        $preview['message'] = '将删除总计 ' . $preview['total'] . ' 条数据';
                        break;
                    case 'optimize':
                        $preview['message'] = '将优化所有数据库表（不删除数据）';
                        break;
                }

                $preview['dry_run'] = true;
                wp_send_json_success($preview);
            }

            // 执行清理（非 dry-run 模式）
            $result = array('deleted' => 0);
            global $wpdb;

            // 记录操作日志
            if (class_exists('MaBox_Audit_Logger')) {
                MaBox_Audit_Logger::database('数据库清理: type=' . $type, array(
                    'type' => $type,
                    'user_id' => get_current_user_id(),
                    'dry_run' => false,
                ));
            }
            error_log('[MaBox] 数据库清理: type=' . $type . ' by user ' . get_current_user_id());

            switch ($type) {
                case 'revisions':
                    $result['deleted'] = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_type = %s", 'revision'));
                    break;
                case 'drafts':
                    $result['deleted'] = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_status = %s", 'auto-draft'));
                    break;
                case 'spam':
                    $result['deleted'] = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->comments} WHERE comment_approved = %s", 'spam'));
                    break;
                case 'transients':
                    $result['deleted'] = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_%', '_site_transient_%'));
                    break;
                case 'pending':
                    $result['deleted'] = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_status = %s", 'pending'));
                    break;
                case 'trash':
                    $result['deleted'] = $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_status = %s", 'trash'));
                    break;
                case 'optimize':
                    $tables = $wpdb->get_results("SHOW TABLES", ARRAY_N);
                    foreach ($tables as $table) {
                        $wpdb->query("OPTIMIZE TABLE `{$table[0]}`");
                    }
                    $result['message'] = '数据库表优化完成';
                    break;
                case 'all':
                    $result['deleted'] = 0;
                    $result['deleted'] += $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_type = %s", 'revision'));
                    $result['deleted'] += $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_status = %s", 'auto-draft'));
                    $result['deleted'] += $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->comments} WHERE comment_approved = %s", 'spam'));
                    $result['deleted'] += $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_%', '_site_transient_%'));
                    $result['deleted'] += $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_status = %s", 'pending'));
                    $result['deleted'] += $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_status = %s", 'trash'));
                    break;
            }

            $result['dry_run'] = false;
            wp_send_json_success($result);
        }
        public static function auto_clean() {
            global $wpdb;
            if (!empty(self::$config['clean_revisions'])) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_type = %s", 'revision'));
            }
            if (!empty(self::$config['clean_drafts'])) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->posts} WHERE post_status = %s", 'auto-draft'));
            }
            if (!empty(self::$config['clean_spam_comments'])) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->comments} WHERE comment_approved = %s", 'spam'));
            }
            if (!empty(self::$config['clean_transients'])) {
                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s", '_transient_%', '_site_transient_%'));
            }
        }
    }
}
