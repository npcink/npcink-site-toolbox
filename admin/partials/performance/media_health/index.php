<?php
if (!class_exists('MaBox_Performance_Media_Health')) {
    class MaBox_Performance_Media_Health {
        private static $config;
        public static function run($config) {
            self::$config = $config;
            if (empty($config['enabled'])) return;
            // Deprecated: use REST API POST /mabox/v1/performance/media/check instead
            add_action('wp_ajax_mabox_media_check', array(__CLASS__, 'ajax_check_deprecated'));
            add_action('wp_ajax_mabox_media_fix_alt', array(__CLASS__, 'ajax_fix_alt_deprecated'));
        }
        public static function ajax_check_deprecated() {
            _deprecated_function('wp_ajax_mabox_media_check', '2.1.0', 'REST API POST /mabox/v1/performance/media/check');
            self::ajax_check();
        }
        public static function ajax_fix_alt_deprecated() {
            _deprecated_function('wp_ajax_mabox_media_fix_alt', '2.1.0', 'REST API POST /mabox/v1/performance/media/fix-alt');
            self::ajax_fix_alt();
        }
        public static function ajax_check() {
            if (!current_user_can('manage_options')) wp_send_json_error('权限不足', 403);
            $issues = array();
            global $wpdb;
            $missing_alt = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_excerpt = ''", 'attachment'));
            if ($missing_alt > 0) {
                $issues[] = array('type' => '缺少Alt', 'count' => intval($missing_alt));
            }
            $large_images = $wpdb->get_results($wpdb->prepare("SELECT ID, guid FROM {$wpdb->posts} WHERE post_type = %s AND post_mime_type LIKE %s", 'attachment', 'image/%'));
            $large_count = 0;
            foreach ($large_images as $img) {
                $file = get_attached_file($img->ID);
                if ($file && file_exists($file) && filesize($file) > 512000) {
                    $large_count++;
                }
            }
            if ($large_count > 0) {
                $issues[] = array('type' => '超大图片', 'count' => $large_count);
            }
            $chinese_names = $wpdb->get_results($wpdb->prepare("SELECT ID, post_name FROM {$wpdb->posts} WHERE post_type = %s", 'attachment'));
            $chinese_count = 0;
            foreach ($chinese_names as $img) {
                if (preg_match('/[\x{4e00}-\x{9fff}]/u', $img->post_name)) {
                    $chinese_count++;
                }
            }
            if ($chinese_count > 0) {
                $issues[] = array('type' => '中文文件名', 'count' => $chinese_count);
            }
            $unused = $wpdb->get_results($wpdb->prepare("SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type = %s AND NOT EXISTS (SELECT 1 FROM {$wpdb->posts} parent WHERE parent.post_content LIKE CONCAT('%%', p.guid, '%%') OR parent.post_excerpt LIKE CONCAT('%%', p.guid, '%%')) LIMIT 100", 'attachment'));
            if (count($unused) > 0) {
                $issues[] = array('type' => '可能未使用', 'count' => count($unused));
            }
            $missing_featured = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s WHERE p.post_status = %s AND p.post_type = %s AND pm.meta_id IS NULL", '_thumbnail_id', 'publish', 'post'));
            if ($missing_featured > 0) {
                $issues[] = array('type' => '无特色图文章', 'count' => intval($missing_featured));
            }
            wp_send_json_success(array('issues' => $issues));
        }
        public static function ajax_fix_alt() {
            if (!current_user_can('manage_options')) wp_send_json_error('权限不足', 403);
            global $wpdb;
            $images = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM {$wpdb->posts} WHERE post_type = %s AND post_excerpt = '' LIMIT 50", 'attachment'));
            $fixed = 0;
            foreach ($images as $img) {
                $alt = !empty($img->post_title) ? $img->post_title : '图片';
                wp_update_post(array('ID' => $img->ID, 'post_excerpt' => $alt));
                $fixed++;
            }
            wp_send_json_success(array('fixed' => $fixed));
        }
    }
}