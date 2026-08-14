<?php
defined('ABSPATH') || exit;
require_once __DIR__ . '/webp_batch.php';
if (!class_exists('Npcink_Toolbox_Performance_Media_Health')) {
    class Npcink_Toolbox_Performance_Media_Health implements Npcink_Toolbox_Module_Interface {
        const ATTACHMENT_SCAN_BATCH_SIZE = 100;
        const ATTACHMENT_SCAN_LIMIT = 500;
        const WEBP_SAMPLE_LIMIT = 3;
        const WEBP_SAMPLE_MAX_FILE_BYTES = 5242880;
        const WEBP_SAMPLE_MAX_PIXELS = 12000000;
        const WEBP_CONTINUOUS_MAX_CANDIDATES = 50;
        const WEBP_BATCH_MIN_CANDIDATES = 100;
        const WEBP_BATCH_MIN_BYTES = 209715200;
        const WEBP_MIN_SAVINGS_PERCENT = 15.0;

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
            global $wpdb;

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-triggered live aggregate; cached diagnostic counts would be stale.
            $missing_alt = $wpdb->get_var($wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s WHERE p.post_type = %s AND p.post_mime_type LIKE %s AND (pm.meta_value IS NULL OR pm.meta_value = '')",
                '_wp_attachment_image_alt',
                'attachment',
                'image/%'
            ));
            if ($missing_alt > 0) {
                $issues[] = array('type' => __('缺少 Alt', 'npcink-site-toolbox'), 'count' => intval($missing_alt));
            }

            $attachment_scan = self::scan_recent_attachments();
            if ($attachment_scan['large'] > 0) {
                $issues[] = array(
                    'type'         => $attachment_scan['sampled']
                        ? sprintf(__('超大图片（最近 %d 个附件抽样）', 'npcink-site-toolbox'), $attachment_scan['checked'])
                        : __('超大图片', 'npcink-site-toolbox'),
                    'count'        => $attachment_scan['large'],
                    'sampled'      => $attachment_scan['sampled'],
                    'sample_size'  => $attachment_scan['checked'],
                    'total_attachments' => $attachment_scan['total'],
                );
            }

            if ($attachment_scan['chinese'] > 0) {
                $issues[] = array(
                    'type'  => $attachment_scan['sampled']
                        ? sprintf(__('中文文件名（最近 %d 个附件抽样）', 'npcink-site-toolbox'), $attachment_scan['checked'])
                        : __('中文文件名', 'npcink-site-toolbox'),
                    'count' => $attachment_scan['chinese'],
                );
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-triggered bounded diagnostic returns at most 100 current candidates.
            $unused = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT p.ID FROM {$wpdb->posts} p WHERE p.post_type = %s AND NOT EXISTS (SELECT 1 FROM {$wpdb->posts} parent WHERE parent.post_content LIKE CONCAT(%s, p.guid, %s) OR parent.post_excerpt LIKE CONCAT(%s, p.guid, %s)) LIMIT 100",
                    'attachment',
                    '%',
                    '%',
                    '%',
                    '%'
                )
            );
            if (count($unused) > 0) {
                $issues[] = array('type' => __('可能未使用', 'npcink-site-toolbox'), 'count' => count($unused));
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Administrator-triggered live aggregate; cached diagnostic counts would be stale.
            $missing_featured = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->posts} p LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s WHERE p.post_status = %s AND p.post_type = %s AND pm.meta_id IS NULL", '_thumbnail_id', 'publish', 'post'));
            if ($missing_featured > 0) {
                $issues[] = array('type' => __('无特色图文章', 'npcink-site-toolbox'), 'count' => intval($missing_featured));
            }

            return rest_ensure_response(array(
                'success' => true,
                'data'    => array(
                    'issues' => $issues,
                    'attachment_scan' => array(
                        'checked' => $attachment_scan['checked'],
                        'total'   => $attachment_scan['total'],
                        'sampled' => $attachment_scan['sampled'],
                    ),
                    'webp_assessment' => $attachment_scan['webp_assessment'],
                ),
            ));
        }
        public static function ajax_convert_webp($request) {
            if (!current_user_can('manage_options')) {
                return new \WP_Error('rest_forbidden', __('权限不足', 'npcink-site-toolbox'), array('status' => 403));
            }
            $ids = self::sanitize_attachment_ids($request->get_param('attachment_ids'));
            return rest_ensure_response(array(
                'success' => true,
                'data'    => Npcink_Toolbox_Webp_Batch::convert_many($ids),
            ));
        }

        public static function ajax_restore_webp($request) {
            if (!current_user_can('manage_options')) {
                return new \WP_Error('rest_forbidden', __('权限不足', 'npcink-site-toolbox'), array('status' => 403));
            }
            $ids = self::sanitize_attachment_ids($request->get_param('attachment_ids'));
            return rest_ensure_response(array(
                'success' => true,
                'data'    => Npcink_Toolbox_Webp_Batch::restore_many($ids),
            ));
        }

        public static function validate_attachment_ids($value) {
            return Npcink_Toolbox_Webp_Batch::validate_attachment_ids($value);
        }

        public static function sanitize_attachment_ids($value) {
            return Npcink_Toolbox_Webp_Batch::normalize_attachment_ids($value);
        }

        private static function scan_recent_attachments() {
            $checked = 0;
            $large = 0;
            $chinese = 0;
            $total = 0;
            $page = 1;
            $image_checked = 0;
            $missing_files = 0;
            $sample_candidates = array();
            $batch_candidate_ids = array();
            $restorable_ids = array();
            $formats = array(
                'jpeg'  => array('count' => 0, 'bytes' => 0),
                'png'   => array('count' => 0, 'bytes' => 0),
                'webp'  => array('count' => 0, 'bytes' => 0),
                'other' => array('count' => 0, 'bytes' => 0),
            );

            while ($checked < self::ATTACHMENT_SCAN_LIMIT) {
                $query = new WP_Query(array(
                    'post_type'              => 'attachment',
                    'post_status'            => 'inherit',
                    'posts_per_page'         => self::ATTACHMENT_SCAN_BATCH_SIZE,
                    'paged'                  => $page,
                    'orderby'                => 'ID',
                    'order'                  => 'DESC',
                    'no_found_rows'          => $page > 1,
                    'update_post_meta_cache' => false,
                    'update_post_term_cache' => false,
                ));
                $attachments = is_array($query->posts) ? $query->posts : array();
                if ($page === 1) {
                    $total = max(0, intval($query->found_posts));
                }
                if (empty($attachments)) break;

                $image_ids = array();
                foreach ($attachments as $attachment) {
                    if (
                        is_object($attachment)
                        && isset($attachment->ID, $attachment->post_mime_type)
                        && strpos((string) $attachment->post_mime_type, 'image/') === 0
                    ) {
                        $image_ids[] = intval($attachment->ID);
                    }
                }
                if (!empty($image_ids)) {
                    update_meta_cache('post', $image_ids);
                }

                foreach ($attachments as $attachment) {
                    if ($checked >= self::ATTACHMENT_SCAN_LIMIT) break;
                    if (!is_object($attachment) || !isset($attachment->ID)) continue;
                    $checked++;

                    $post_name = isset($attachment->post_name) ? (string) $attachment->post_name : '';
                    if (preg_match('/[\x{4e00}-\x{9fff}]/u', $post_name)) $chinese++;

                    $mime_type = isset($attachment->post_mime_type)
                        ? (string) $attachment->post_mime_type
                        : '';
                    if (strpos($mime_type, 'image/') !== 0) continue;
                    $image_checked++;

                    $file = get_attached_file(intval($attachment->ID));
                    if (!is_string($file) || !is_file($file)) {
                        $missing_files++;
                        continue;
                    }
                    $size = filesize($file);
                    if (!is_int($size) || $size < 0) continue;

                    $format = self::classify_image_format($file, $mime_type);
                    $formats[$format]['count']++;
                    $formats[$format]['bytes'] += $size;

                    $webp_backup = get_post_meta(
                        intval($attachment->ID),
                        Npcink_Toolbox_Webp_Batch::BACKUP_META_KEY,
                        true
                    );
                    if (
                        $format === 'webp'
                        && $mime_type === 'image/webp'
                        && is_array($webp_backup)
                        && count($restorable_ids) < self::WEBP_CONTINUOUS_MAX_CANDIDATES
                    ) {
                        $restorable_ids[] = intval($attachment->ID);
                    }

                    if (
                        count($batch_candidate_ids) < self::WEBP_CONTINUOUS_MAX_CANDIDATES
                        && Npcink_Toolbox_Webp_Batch::is_candidate(
                            intval($attachment->ID),
                            $file,
                            $mime_type
                        )
                    ) {
                        $batch_candidate_ids[] = intval($attachment->ID);
                    }

                    if ($size > 512000) $large++;
                    if (
                        $format === 'jpeg'
                        && count($sample_candidates) < self::WEBP_SAMPLE_LIMIT
                        && self::is_safe_webp_sample($file, $size)
                    ) {
                        $sample_candidates[] = array(
                            'file'  => $file,
                            'bytes' => $size,
                        );
                    }
                }

                if (count($attachments) < self::ATTACHMENT_SCAN_BATCH_SIZE) break;
                $page++;
            }

            $webp_supported = function_exists('wp_image_editor_supports')
                && wp_image_editor_supports(array('mime_type' => 'image/webp'));
            $sample = self::estimate_webp_savings($sample_candidates, $webp_supported);
            $sample['recommendation'] = self::get_webp_recommendation(
                $webp_supported,
                $formats['jpeg']['count'],
                $formats['jpeg']['bytes'],
                $sample
            );

            return array(
                'checked' => $checked,
                'total'   => $total,
                'large'   => $large,
                'chinese' => $chinese,
                'sampled' => $total > $checked,
                'webp_assessment' => array(
                    'supported'     => $webp_supported,
                    'checked'       => $image_checked,
                    'sampled'       => $total > $checked,
                    'missing_files' => $missing_files,
                    'formats'       => $formats,
                    'sample'        => $sample,
                    'thresholds'    => array(
                        'candidate_count' => self::WEBP_BATCH_MIN_CANDIDATES,
                        'candidate_bytes' => self::WEBP_BATCH_MIN_BYTES,
                        'savings_percent' => self::WEBP_MIN_SAVINGS_PERCENT,
                    ),
                    'batch'         => array(
                        'candidate_ids'    => $batch_candidate_ids,
                        'restorable_ids'   => $restorable_ids,
                        'batch_size'       => Npcink_Toolbox_Webp_Batch::MAX_BATCH_SIZE,
                        'original_retained'=> true,
                        'restorable'       => true,
                    ),
                ),
            );
        }

        private static function classify_image_format($file, $mime_type) {
            $extension = strtolower((string) pathinfo($file, PATHINFO_EXTENSION));
            if (in_array($extension, array('jpg', 'jpeg', 'jpe'), true)) {
                return 'jpeg';
            }
            if ($extension === 'png') {
                return 'png';
            }
            if ($extension === 'webp') {
                return 'webp';
            }
            if ($mime_type === 'image/jpeg') return 'jpeg';
            if ($mime_type === 'image/png') return 'png';
            if ($mime_type === 'image/webp') return 'webp';
            return 'other';
        }

        private static function is_safe_webp_sample($file, $size) {
            if ($size <= 0 || $size > self::WEBP_SAMPLE_MAX_FILE_BYTES) {
                return false;
            }

            $image_size = wp_getimagesize($file);
            if (!is_array($image_size) || empty($image_size[0]) || empty($image_size[1])) {
                return false;
            }

            return ((int) $image_size[0] * (int) $image_size[1]) <= self::WEBP_SAMPLE_MAX_PIXELS;
        }

        private static function estimate_webp_savings($candidates, $supported) {
            $result = array(
                'attempted'               => 0,
                'successful'              => 0,
                'errors'                  => 0,
                'input_bytes'             => 0,
                'output_bytes'            => 0,
                'savings_bytes'           => 0,
                'savings_percent'         => null,
                'temporary_files_cleaned' => true,
            );
            if (!$supported || empty($candidates)) {
                return $result;
            }

            // REST 请求不会默认加载 wp-admin/includes/file.php，而 wp_tempnam()
            // 定义在该文件中。按需加载，避免媒体体检在 REST 上下文触发致命错误。
            if (!function_exists('wp_tempnam')) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
            }

            foreach (array_slice($candidates, 0, self::WEBP_SAMPLE_LIMIT) as $candidate) {
                $result['attempted']++;
                $temporary_paths = array();
                $editor = null;

                try {
                    $temporary_stub = wp_tempnam('npcink-webp-assessment');
                    if (!is_string($temporary_stub) || $temporary_stub === '') {
                        $result['errors']++;
                        continue;
                    }

                    $temporary_paths[] = $temporary_stub;
                    $destination = $temporary_stub . '.webp';
                    $temporary_paths[] = $destination;
                    if (is_file($temporary_stub)) {
                        wp_delete_file($temporary_stub);
                    }

                    $editor = wp_get_image_editor($candidate['file']);
                    if (is_wp_error($editor)) {
                        $result['errors']++;
                        continue;
                    }

                    $saved = $editor->save($destination, 'image/webp');
                    if (is_wp_error($saved) || empty($saved['path']) || !is_file($saved['path'])) {
                        $result['errors']++;
                        continue;
                    }

                    $temporary_paths[] = $saved['path'];
                    $output_bytes = filesize($saved['path']);
                    if (!is_int($output_bytes) || $output_bytes < 0) {
                        $result['errors']++;
                        continue;
                    }

                    $result['successful']++;
                    $result['input_bytes'] += (int) $candidate['bytes'];
                    $result['output_bytes'] += $output_bytes;
                } finally {
                    unset($editor);
                    foreach (array_unique($temporary_paths) as $temporary_path) {
                        if (is_file($temporary_path)) {
                            wp_delete_file($temporary_path);
                        }
                        if (is_file($temporary_path)) {
                            $result['temporary_files_cleaned'] = false;
                        }
                    }
                }
            }

            if ($result['successful'] > 0 && $result['input_bytes'] > 0) {
                $result['savings_bytes'] = $result['input_bytes'] - $result['output_bytes'];
                $result['savings_percent'] = round(
                    ($result['savings_bytes'] / $result['input_bytes']) * 100,
                    1
                );
            }

            return $result;
        }

        private static function get_webp_recommendation($supported, $candidate_count, $candidate_bytes, $sample) {
            if (!$supported) return 'unsupported';
            if ($candidate_count < 1) return 'no_candidates';
            if (empty($sample['temporary_files_cleaned'])) return 'cleanup_failed';
            if ($sample['successful'] < min(3, $candidate_count)) return 'insufficient_sample';
            if ($sample['savings_percent'] === null) return 'sample_failed';
            if ($sample['savings_percent'] < self::WEBP_MIN_SAVINGS_PERCENT) return 'low_savings';

            if (
                $candidate_count >= self::WEBP_BATCH_MIN_CANDIDATES
                || $candidate_bytes >= self::WEBP_BATCH_MIN_BYTES
            ) {
                return 'consider_batch';
            }

            return 'below_scale';
        }
    }
}
