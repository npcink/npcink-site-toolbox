<?php
defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Webp_Batch')) {
    /**
     * 历史 JPEG 附件的小批量 WebP 转换器。
     *
     * 转换时保留原始 JPEG 和旧附件元数据。只有主图、所需缩略图和数据库
     * 更新全部成功后，附件才会指向 WebP；最近一批可按附件 ID 恢复。
     */
    class Npcink_Toolbox_Webp_Batch {
        const MAX_BATCH_SIZE = 5;
        const BACKUP_META_KEY = '_npcink_site_toolbox_webp_backup_v1';
        const LOCK_META_KEY = '_npcink_site_toolbox_webp_lock';
        const OSS_META_KEY = '_npcink_site_toolbox_oss_offloaded';
        const LOCK_TIMEOUT = 600;
        const SOURCE_MAX_FILE_BYTES = 20971520;
        const SOURCE_MAX_PIXELS = 20000000;

        /**
         * @param mixed $value REST 参数值。
         */
        public static function validate_attachment_ids($value) {
            if (!is_array($value) || count($value) < 1 || count($value) > self::MAX_BATCH_SIZE) {
                return false;
            }

            $normalized = self::normalize_attachment_ids($value);
            return count($normalized) === count($value);
        }

        /**
         * @param mixed $value REST 参数值。
         * @return int[]
         */
        public static function normalize_attachment_ids($value) {
            if (!is_array($value)) return array();

            $ids = array();
            foreach ($value as $candidate) {
                if (is_bool($candidate) || !is_scalar($candidate) || !is_numeric($candidate)) continue;
                $id = (int) $candidate;
                if ($id < 1 || (string) $id !== trim((string) $candidate)) continue;
                $ids[$id] = $id;
                if (count($ids) >= self::MAX_BATCH_SIZE) break;
            }
            return array_values($ids);
        }

        public static function is_candidate($attachment_id, $file, $mime_type) {
            if ((string) $mime_type !== 'image/jpeg') return false;
            if (!is_string($file) || !is_file($file)) return false;
            if (!in_array(strtolower((string) pathinfo($file, PATHINFO_EXTENSION)), array('jpg', 'jpeg', 'jpe'), true)) {
                return false;
            }
            $file_size = filesize($file);
            $image_size = wp_getimagesize($file);
            if (!is_int($file_size) || $file_size < 1 || $file_size > self::SOURCE_MAX_FILE_BYTES) {
                return false;
            }
            if (!is_array($image_size) || empty($image_size[0]) || empty($image_size[1])) {
                return false;
            }
            if (((int) $image_size[0] * (int) $image_size[1]) > self::SOURCE_MAX_PIXELS) {
                return false;
            }
            return empty(get_post_meta((int) $attachment_id, self::BACKUP_META_KEY, true));
        }

        /**
         * @param int[] $attachment_ids
         * @return array<string,mixed>
         */
        public static function convert_many($attachment_ids) {
            $ids = self::normalize_attachment_ids($attachment_ids);
            $results = array();
            foreach ($ids as $attachment_id) {
                $results[] = self::convert_one($attachment_id);
            }
            return self::summarize_results($results, 'converted');
        }

        /**
         * @param int[] $attachment_ids
         * @return array<string,mixed>
         */
        public static function restore_many($attachment_ids) {
            $ids = self::normalize_attachment_ids($attachment_ids);
            $results = array();
            foreach ($ids as $attachment_id) {
                $results[] = self::restore_one($attachment_id);
            }
            return self::summarize_results($results, 'restored');
        }

        private static function convert_one($attachment_id) {
            if (!function_exists('wp_image_editor_supports')
                || !wp_image_editor_supports(array('mime_type' => 'image/webp'))) {
                return self::result($attachment_id, 'failed', __('当前服务器不支持生成 WebP。', 'npcink-site-toolbox'));
            }

            if (!self::acquire_lock($attachment_id)) {
                return self::result($attachment_id, 'skipped', __('该附件正在处理中，请稍后重试。', 'npcink-site-toolbox'));
            }

            $generated_files = array();
            try {
                $post = get_post($attachment_id);
                if (!$post || $post->post_type !== 'attachment') {
                    return self::result($attachment_id, 'failed', __('附件不存在。', 'npcink-site-toolbox'));
                }

                $existing_backup = get_post_meta($attachment_id, self::BACKUP_META_KEY, true);
                if (is_array($existing_backup) && get_post_mime_type($attachment_id) === 'image/webp') {
                    return self::result($attachment_id, 'skipped', __('该附件已转换，可先恢复后再重试。', 'npcink-site-toolbox'));
                }
                if (!empty($existing_backup)) {
                    return self::result($attachment_id, 'failed', __('检测到未完成的转换记录，请先恢复该附件。', 'npcink-site-toolbox'));
                }

                $source = get_attached_file($attachment_id, true);
                $mime_type = (string) get_post_mime_type($attachment_id);
                if (!self::is_candidate($attachment_id, $source, $mime_type)) {
                    return self::result($attachment_id, 'skipped', __('仅处理本地可读的 JPEG 附件。', 'npcink-site-toolbox'));
                }

                $old_metadata = wp_get_attachment_metadata($attachment_id, true);
                if (!is_array($old_metadata)) {
                    return self::result($attachment_id, 'failed', __('原附件元数据不可读。', 'npcink-site-toolbox'));
                }

                $upload_dir = wp_get_upload_dir();
                if (!empty($upload_dir['error']) || empty($upload_dir['basedir'])) {
                    return self::result($attachment_id, 'failed', __('上传目录不可用。', 'npcink-site-toolbox'));
                }
                $upload_root = realpath($upload_dir['basedir']);
                $source_real = realpath($source);
                if ($upload_root === false || $source_real === false || !self::is_path_inside($source_real, $upload_root)) {
                    return self::result($attachment_id, 'failed', __('附件不在当前上传目录中。', 'npcink-site-toolbox'));
                }

                $destination = self::make_destination($source_real);
                $generated = self::generate_webp_set($attachment_id, $source_real, $destination, $old_metadata);
                if (is_wp_error($generated)) {
                    return self::result($attachment_id, 'failed', $generated->get_error_message());
                }
                $metadata = $generated['metadata'];
                $generated_files = $generated['files'];
                $destination = $generated['main_file'];

                $backup = array(
                    'version'          => 1,
                    'attached_file'    => (string) get_post_meta($attachment_id, '_wp_attached_file', true),
                    'metadata'         => $old_metadata,
                    'mime_type'        => $mime_type,
                    'oss_meta_exists'  => metadata_exists('post', $attachment_id, self::OSS_META_KEY),
                    'oss_meta'         => get_post_meta($attachment_id, self::OSS_META_KEY, true),
                    'generated_files'  => self::relative_paths($generated_files, $upload_root),
                    'converted_at_gmt' => gmdate('c'),
                );

                if (!add_post_meta($attachment_id, self::BACKUP_META_KEY, $backup, true)) {
                    self::delete_generated_files($generated_files, $upload_root);
                    return self::result($attachment_id, 'failed', __('无法保存恢复记录，未切换附件。', 'npcink-site-toolbox'));
                }

                if (!self::apply_snapshot($attachment_id, array(
                    'file'      => $destination,
                    'metadata'  => $metadata,
                    'mime_type' => 'image/webp',
                ))) {
                    self::apply_snapshot($attachment_id, array(
                        'file'      => $source_real,
                        'metadata'  => $old_metadata,
                        'mime_type' => $mime_type,
                    ));
                    self::restore_oss_meta($attachment_id, $backup);
                    delete_post_meta($attachment_id, self::BACKUP_META_KEY);
                    self::delete_generated_files($generated_files, $upload_root);
                    return self::result($attachment_id, 'failed', __('数据库切换失败，已恢复原附件。', 'npcink-site-toolbox'));
                }

                // 此时本地文件与数据库均已完成，再通知对象存储等现有监听器。
                try {
                    $filtered_metadata = apply_filters(
                        'wp_generate_attachment_metadata',
                        $metadata,
                        $attachment_id,
                        'update'
                    );
                    if (is_array($filtered_metadata) && $filtered_metadata !== $metadata) {
                        wp_update_attachment_metadata($attachment_id, $filtered_metadata);
                    }
                } catch (\Throwable $error) {
                    // 外部同步失败时保留本地 WebP，并回退到本地 URL。
                    delete_post_meta($attachment_id, self::OSS_META_KEY);
                }

                return self::result($attachment_id, 'converted', __('已转换为 WebP，原 JPEG 与恢复记录已保留。', 'npcink-site-toolbox'));
            } finally {
                self::release_lock($attachment_id);
            }
        }

        private static function restore_one($attachment_id) {
            if (!self::acquire_lock($attachment_id)) {
                return self::result($attachment_id, 'skipped', __('该附件正在处理中，请稍后重试。', 'npcink-site-toolbox'));
            }

            try {
                $backup = get_post_meta($attachment_id, self::BACKUP_META_KEY, true);
                if (!is_array($backup) || empty($backup['attached_file']) || !isset($backup['metadata'])) {
                    return self::result($attachment_id, 'skipped', __('未找到可用的恢复记录。', 'npcink-site-toolbox'));
                }

                $upload_dir = wp_get_upload_dir();
                $upload_root = !empty($upload_dir['basedir']) ? realpath($upload_dir['basedir']) : false;
                if ($upload_root === false) {
                    return self::result($attachment_id, 'failed', __('上传目录不可用。', 'npcink-site-toolbox'));
                }
                $original_file = $upload_root . '/' . ltrim((string) $backup['attached_file'], '/');
                $original_real = realpath($original_file);
                if ($original_real === false || !is_file($original_real) || !self::is_path_inside($original_real, $upload_root)) {
                    return self::result($attachment_id, 'failed', __('原 JPEG 备份不可读，未执行恢复。', 'npcink-site-toolbox'));
                }

                $current = array(
                    'file'      => get_attached_file($attachment_id, true),
                    'metadata'  => wp_get_attachment_metadata($attachment_id, true),
                    'mime_type' => (string) get_post_mime_type($attachment_id),
                );
                $restored = self::apply_snapshot($attachment_id, array(
                    'file'      => $original_real,
                    'metadata'  => is_array($backup['metadata']) ? $backup['metadata'] : array(),
                    'mime_type' => !empty($backup['mime_type']) ? (string) $backup['mime_type'] : 'image/jpeg',
                ));
                if (!$restored) {
                    self::apply_snapshot($attachment_id, $current);
                    return self::result($attachment_id, 'failed', __('恢复数据库失败，附件保持转换前状态。', 'npcink-site-toolbox'));
                }

                self::restore_oss_meta($attachment_id, $backup);
                if (!delete_post_meta($attachment_id, self::BACKUP_META_KEY)) {
                    self::apply_snapshot($attachment_id, $current);
                    return self::result($attachment_id, 'failed', __('恢复记录清理失败，已撤销本次恢复。', 'npcink-site-toolbox'));
                }

                $cleanup_failed = false;
                foreach ((array) $backup['generated_files'] as $relative_path) {
                    $path = $upload_root . '/' . ltrim((string) $relative_path, '/');
                    if (!self::is_path_inside($path, $upload_root) || strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'webp') {
                        $cleanup_failed = true;
                        continue;
                    }
                    if (is_file($path)) wp_delete_file($path);
                    if (is_file($path)) $cleanup_failed = true;
                }

                return self::result(
                    $attachment_id,
                    'restored',
                    $cleanup_failed
                        ? __('已恢复原 JPEG；部分无引用的本地 WebP 文件需人工清理。', 'npcink-site-toolbox')
                        : __('已恢复原 JPEG，并清理本次生成的本地 WebP 文件。', 'npcink-site-toolbox')
                );
            } finally {
                self::release_lock($attachment_id);
            }
        }

        private static function generate_webp_set($attachment_id, $source, $destination, $old_metadata) {
            $editor = wp_get_image_editor($source);
            if (is_wp_error($editor)) {
                return new WP_Error('npcink_webp_editor_unavailable', __('无法读取 JPEG 图像。', 'npcink-site-toolbox'));
            }
            $saved = $editor->save($destination, 'image/webp');
            unset($editor);
            if (is_wp_error($saved) || empty($saved['path']) || !is_file($saved['path'])) {
                return new WP_Error('npcink_webp_main_failed', __('WebP 主图生成失败。', 'npcink-site-toolbox'));
            }

            $destination = $saved['path'];
            $image_size = wp_getimagesize($destination);
            if (!is_array($image_size) || empty($image_size[0]) || empty($image_size[1])) {
                wp_delete_file($destination);
                return new WP_Error('npcink_webp_invalid_main', __('生成的 WebP 主图不可读。', 'npcink-site-toolbox'));
            }

            $metadata = array(
                'width'      => (int) $image_size[0],
                'height'     => (int) $image_size[1],
                'file'       => _wp_relative_upload_path($destination),
                'filesize'   => (int) wp_filesize($destination),
                'sizes'      => array(),
                'image_meta' => isset($old_metadata['image_meta']) && is_array($old_metadata['image_meta'])
                    ? $old_metadata['image_meta']
                    : array(),
            );
            if (isset($metadata['image_meta']['orientation'])) {
                $metadata['image_meta']['orientation'] = 1;
            }

            $sizes = wp_get_registered_image_subsizes();
            $sizes = apply_filters('intermediate_image_sizes_advanced', $sizes, $metadata, $attachment_id);
            if (!is_array($sizes)) $sizes = array();

            $generated_files = array($destination);
            if (!empty($sizes)) {
                $subsize_editor = wp_get_image_editor($destination);
                if (is_wp_error($subsize_editor)) {
                    self::delete_generated_files($generated_files, dirname(dirname($destination)));
                    return new WP_Error('npcink_webp_subsize_editor_failed', __('无法创建 WebP 缩略图。', 'npcink-site-toolbox'));
                }

                $expected_sizes = array();
                foreach ($sizes as $name => $size) {
                    if (!is_array($size)) continue;
                    $width = !empty($size['width']) ? (int) $size['width'] : 0;
                    $height = !empty($size['height']) ? (int) $size['height'] : 0;
                    $crop = !empty($size['crop']) ? $size['crop'] : false;
                    if (!image_resize_dimensions($metadata['width'], $metadata['height'], $width, $height, $crop)) {
                        continue;
                    }
                    $expected_sizes[$name] = $size;
                }

                $created_sizes = $subsize_editor->multi_resize($expected_sizes);
                foreach ($expected_sizes as $name => $size) {
                    if (empty($created_sizes[$name]) || empty($created_sizes[$name]['file'])) {
                        unset($subsize_editor);
                        self::delete_generated_files($generated_files, dirname(dirname($destination)));
                        return new WP_Error(
                            'npcink_webp_subsize_failed',
                            sprintf(
                                /* translators: %s: WordPress image sub-size name. */
                                __('WebP 缩略图 %s 生成失败。', 'npcink-site-toolbox'),
                                $name
                            )
                        );
                    }
                    $metadata['sizes'][$name] = $created_sizes[$name];
                    $generated_files[] = dirname($destination) . '/' . $created_sizes[$name]['file'];
                }
                unset($subsize_editor);
            }

            foreach ($generated_files as $generated_file) {
                if (!is_file($generated_file) || !is_readable($generated_file)) {
                    self::delete_generated_files($generated_files, dirname(dirname($destination)));
                    return new WP_Error('npcink_webp_output_missing', __('WebP 文件集校验失败。', 'npcink-site-toolbox'));
                }
            }

            return array(
                'metadata'  => $metadata,
                'files'     => array_values(array_unique($generated_files)),
                'main_file' => $destination,
            );
        }

        private static function make_destination($source) {
            $directory = dirname($source);
            $filename = pathinfo($source, PATHINFO_FILENAME) . '.webp';
            return $directory . '/' . wp_unique_filename($directory, $filename);
        }

        private static function apply_snapshot($attachment_id, $snapshot) {
            if (empty($snapshot['file']) || !is_array($snapshot['metadata']) || empty($snapshot['mime_type'])) {
                return false;
            }
            if (!update_attached_file($attachment_id, $snapshot['file'])) return false;
            if (!wp_update_attachment_metadata($attachment_id, $snapshot['metadata'])) return false;
            $updated = wp_update_post(array(
                'ID'             => $attachment_id,
                'post_mime_type' => $snapshot['mime_type'],
            ), true);
            return !is_wp_error($updated) && (int) $updated === (int) $attachment_id;
        }

        private static function restore_oss_meta($attachment_id, $backup) {
            if (!empty($backup['oss_meta_exists'])) {
                update_post_meta($attachment_id, self::OSS_META_KEY, $backup['oss_meta']);
            } else {
                delete_post_meta($attachment_id, self::OSS_META_KEY);
            }
        }

        private static function acquire_lock($attachment_id) {
            $locked_at = get_post_meta($attachment_id, self::LOCK_META_KEY, true);
            if ($locked_at && (time() - (int) $locked_at) > self::LOCK_TIMEOUT) {
                delete_post_meta($attachment_id, self::LOCK_META_KEY);
            }
            return (bool) add_post_meta($attachment_id, self::LOCK_META_KEY, time(), true);
        }

        private static function release_lock($attachment_id) {
            delete_post_meta($attachment_id, self::LOCK_META_KEY);
        }

        private static function is_path_inside($path, $root) {
            $normalized_root = trailingslashit(wp_normalize_path($root));
            $normalized_path = wp_normalize_path($path);
            return strpos($normalized_path, $normalized_root) === 0;
        }

        private static function relative_paths($files, $upload_root) {
            $root = trailingslashit(wp_normalize_path($upload_root));
            $relative = array();
            foreach ($files as $file) {
                $normalized = wp_normalize_path($file);
                if (strpos($normalized, $root) === 0) {
                    $relative[] = ltrim(substr($normalized, strlen($root)), '/');
                }
            }
            return array_values(array_unique($relative));
        }

        private static function delete_generated_files($files, $upload_root) {
            foreach (array_unique((array) $files) as $file) {
                if (!is_string($file) || strtolower(pathinfo($file, PATHINFO_EXTENSION)) !== 'webp') continue;
                if (!self::is_path_inside($file, $upload_root)) continue;
                if (is_file($file)) wp_delete_file($file);
            }
        }

        private static function result($attachment_id, $status, $message) {
            return array(
                'attachment_id' => (int) $attachment_id,
                'status'        => $status,
                'message'       => $message,
            );
        }

        private static function summarize_results($results, $success_status) {
            $summary = array(
                'processed'         => count($results),
                $success_status     => 0,
                'skipped'           => 0,
                'failed'            => 0,
                'results'           => $results,
                'original_retained' => true,
            );
            foreach ($results as $result) {
                $status = isset($result['status']) ? $result['status'] : 'failed';
                if ($status === $success_status) $summary[$success_status]++;
                elseif ($status === 'skipped') $summary['skipped']++;
                else $summary['failed']++;
            }
            return $summary;
        }
    }
}
