<?php
defined('ABSPATH') || exit;
if (!class_exists('Npcink_Toolbox_Performance_Seo_Checker')) {
    class Npcink_Toolbox_Performance_Seo_Checker implements Npcink_Toolbox_Module_Interface {
        private static $config;
        public static function run($config = array()) {
            self::$config = $config;
            if (empty($config['enabled'])) return;
        }
        public static function ajax_check() {
            if (!current_user_can('manage_options')) {
                return new \WP_Error('rest_forbidden', __('权限不足', 'npcink-site-toolbox'), array('status' => 403));
            }
            $issues = array();
            $seo_home = Npcink_Toolbox_Config_Manager::get_module_config('function');
            if (isset($seo_home['seo']['title']) && empty($seo_home['seo']['title'])) {
                $issues[] = array('type' => __('首页标题', 'npcink-site-toolbox'), 'message' => __('首页 SEO 标题为空', 'npcink-site-toolbox'));
            }
            if (isset($seo_home['seo']['description']) && empty($seo_home['seo']['description'])) {
                $issues[] = array('type' => __('首页描述', 'npcink-site-toolbox'), 'message' => __('首页 SEO 描述为空', 'npcink-site-toolbox'));
            }
            global $wpdb;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-triggered live aggregate; cached diagnostic counts would be stale.
            $missing_seo = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s AND post_type = %s AND (post_title = '' OR post_excerpt = '')", 'publish', 'post'));
            if ($missing_seo > 0) {
                $issues[] = array(
                    'type' => __('文章 SEO', 'npcink-site-toolbox'),
                    'message' => sprintf(__('%d 篇文章缺少标题或摘要', 'npcink-site-toolbox'), $missing_seo),
                );
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-triggered live aggregate; cached diagnostic counts would be stale.
            $missing_alt = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s WHERE p.post_type = %s AND p.post_mime_type LIKE %s AND (pm.meta_value IS NULL OR pm.meta_value = '')",
                '_wp_attachment_image_alt',
                'attachment',
                'image/%'
            ));
            if ($missing_alt > 0) {
                $issues[] = array(
                    'type' => __('图片 Alt', 'npcink-site-toolbox'),
                    'message' => sprintf(__('%d 张图片缺少 Alt 文本', 'npcink-site-toolbox'), $missing_alt),
                );
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-triggered live aggregate; cached diagnostic counts would be stale.
            $missing_featured = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s WHERE p.post_status = %s AND p.post_type = %s AND pm.meta_id IS NULL", '_thumbnail_id', 'publish', 'post'));
            if ($missing_featured > 0) {
                $issues[] = array(
                    'type' => __('特色图', 'npcink-site-toolbox'),
                    'message' => sprintf(__('%d 篇文章没有特色图', 'npcink-site-toolbox'), $missing_featured),
                );
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-triggered live aggregate; cached diagnostic counts would be stale.
            $short_posts = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s AND post_type = %s AND LENGTH(post_content) < %d", 'publish', 'post', 300));
            if ($short_posts > 0) {
                $issues[] = array(
                    'type' => __('内容过短', 'npcink-site-toolbox'),
                    'message' => sprintf(__('%d 篇文章内容过短（少于 300 字）', 'npcink-site-toolbox'), $short_posts),
                );
            }
            return rest_ensure_response(array(
                'success' => true,
                'data'    => array('issues' => $issues, 'total' => count($issues)),
            ));
        }
    }
}
