<?php
if (!class_exists('MaBox_Performance_Seo_Checker')) {
    class MaBox_Performance_Seo_Checker {
        private static $config;
        public static function run($config) {
            self::$config = $config;
            if (empty($config['enabled'])) return;
            add_action('wp_ajax_mabox_seo_check', array(__CLASS__, 'ajax_check_deprecated'));
            add_action('wp_ajax_mabox_seo_fix_alt', array(__CLASS__, 'ajax_fix_alt_deprecated'));
        }
        public static function ajax_check_deprecated() {
            _deprecated_function('wp_ajax_mabox_seo_check', '2.1.0', 'REST API POST /mabox/v1/performance/seo/check');
            self::ajax_check();
        }
        public static function ajax_fix_alt_deprecated() {
            _deprecated_function('wp_ajax_mabox_seo_fix_alt', '2.1.0', 'REST API POST /mabox/v1/performance/seo/fix-alt');
            self::ajax_fix_alt();
        }
        public static function ajax_check() {
            if (!current_user_can('manage_options')) wp_send_json_error('权限不足', 403);
            $issues = array();
            $seo_home = MaBox_Config_Manager::get_module_config('function');
            if (isset($seo_home['seo']['title']) && empty($seo_home['seo']['title'])) {
                $issues[] = array('type' => '首页标题', 'message' => '首页 SEO 标题为空');
            }
            if (isset($seo_home['seo']['description']) && empty($seo_home['seo']['description'])) {
                $issues[] = array('type' => '首页描述', 'message' => '首页 SEO 描述为空');
            }
            global $wpdb;
            $missing_seo = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s AND post_type = %s AND (post_title = '' OR post_excerpt = '')", 'publish', 'post'));
            if ($missing_seo > 0) {
                $issues[] = array('type' => '文章SEO', 'message' => $missing_seo . ' 篇文章缺少标题或摘要');
            }
            $missing_alt = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s AND post_excerpt = ''", 'attachment'));
            if ($missing_alt > 0) {
                $issues[] = array('type' => '图片Alt', 'message' => $missing_alt . ' 张图片缺少 Alt 文本');
            }
            $missing_featured = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s WHERE p.post_status = %s AND p.post_type = %s AND pm.meta_id IS NULL", '_thumbnail_id', 'publish', 'post'));
            if ($missing_featured > 0) {
                $issues[] = array('type' => '特色图', 'message' => $missing_featured . ' 篇文章没有特色图');
            }
            $short_posts = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s AND post_type = %s AND LENGTH(post_content) < %d", 'publish', 'post', 300));
            if ($short_posts > 0) {
                $issues[] = array('type' => '内容过短', 'message' => $short_posts . ' 篇文章内容过短（少于300字）');
            }
            wp_send_json_success(array('issues' => $issues, 'total' => count($issues)));
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