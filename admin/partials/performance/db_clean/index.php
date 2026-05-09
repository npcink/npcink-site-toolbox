<?php
if (!class_exists('Npcink_Performance_Db_Clean')) {
    class Npcink_Performance_Db_Clean {
        private static $config;
        public static function run($config) {
            self::$config = $config;
            add_action('wp_ajax_mabox_db_clean', array(__CLASS__, 'ajax_clean'));
            add_action('wp_ajax_mabox_db_stats', array(__CLASS__, 'ajax_stats'));
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
        public static function ajax_clean() {
            if (!current_user_can('manage_options')) wp_send_json_error('权限不足', 403);
            $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
            $result = array('deleted' => 0);
            global $wpdb;
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
                    break;
            }
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