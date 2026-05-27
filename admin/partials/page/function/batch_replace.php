<?php
/**
 * 文章批量替换
 * 保存文章时自动替换指定内容，也支持手动触发
 * 支持 dry-run 预览和回滚功能
 */
if (!class_exists('MaBox_Page_Batch_Replace')) {
    class MaBox_Page_Batch_Replace
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_filter('content_save_pre', array(__CLASS__, 'replace_on_save'), 10, 1);
            add_action('wp_ajax_mabox_batch_replace', array(__CLASS__, 'manual_replace_deprecated'));
        }

        public static function replace_on_save($content)
        {
            $pairs = MaBox_Admin::get_config(self::$option, 'batch_replace_pairs', array());
            if (empty($pairs)) {
                return $content;
            }

            foreach ($pairs as $pair) {
                if (!empty($pair['find']) && isset($pair['replace'])) {
                    $content = str_replace($pair['find'], $pair['replace'], $content);
                }
            }

            return $content;
        }

        public static function manual_replace_deprecated() {
            _deprecated_function('wp_ajax_mabox_batch_replace', '2.1.0', 'REST API POST /mabox/v1/page/batch-replace');
            self::manual_replace();
        }

        /**
         * 备份文章原始内容
         */
        private static function backup_post_content($post_id, $content) {
            $backup_key = '_mabox_batch_replace_backup';
            $backup_data = get_post_meta($post_id, $backup_key, true);

            if (empty($backup_data)) {
                $backup_data = array();
            }

            $backup_data['content'] = $content;
            $backup_data['timestamp'] = time();
            $backup_data['version'] = '2.4.0';

            update_post_meta($post_id, $backup_key, $backup_data);
        }

        /**
         * 回滚单篇文章
         */
        public static function rollback_post($post_id) {
            $backup_key = '_mabox_batch_replace_backup';
            $backup_data = get_post_meta($post_id, $backup_key, true);

            if (empty($backup_data) || empty($backup_data['content'])) {
                return array(
                    'success' => false,
                    'message' => '没有找到备份内容',
                );
            }

            wp_update_post(array(
                'ID' => $post_id,
                'post_content' => $backup_data['content'],
            ));

            delete_post_meta($post_id, $backup_key);

            return array(
                'success' => true,
                'message' => '回滚成功',
            );
        }

        public static function manual_replace()
        {
            if (!current_user_can('edit_posts')) {
                wp_send_json_error('权限不足');
            }

            // 支持 REST API 和 AJAX 两种参数传递方式
            $params = $_POST;
            $dry_run = isset($params['dry_run']) ? rest_sanitize_boolean($params['dry_run']) : true; // 默认 dry-run

            $pairs = MaBox_Admin::get_config(self::$option, 'batch_replace_pairs', array());
            if (empty($pairs)) {
                wp_send_json_error('没有设置替换规则');
            }

            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => -1,
            );
            $query = new WP_Query($args);
            $count = 0;
            $affected_posts = array();

            foreach ($query->posts as $post) {
                $new_content = $post->post_content;
                foreach ($pairs as $pair) {
                    if (!empty($pair['find']) && isset($pair['replace'])) {
                        $new_content = str_replace($pair['find'], $pair['replace'], $new_content);
                    }
                }

                if ($new_content !== $post->post_content) {
                    if ($dry_run) {
                        // Dry-run 模式：只记录受影响的文章
                        $affected_posts[] = array(
                            'ID' => $post->ID,
                            'post_title' => $post->post_title,
                        );
                        $count++;
                    } else {
                        // 执行模式：先备份，再替换
                        self::backup_post_content($post->ID, $post->post_content);
                        wp_update_post(array(
                            'ID' => $post->ID,
                            'post_content' => $new_content,
                        ));
                        $count++;
                    }
                }
            }

            if ($dry_run) {
                wp_send_json_success(array(
                    'dry_run' => true,
                    'affected_count' => $count,
                    'affected_posts' => array_slice($affected_posts, 0, 50), // 最多返回 50 条
                    'message' => '预览模式：将替换 ' . $count . ' 篇文章的内容',
                ));
            } else {
                // 记录操作日志
                if (class_exists('MaBox_Audit_Logger')) {
                    MaBox_Audit_Logger::database('批量替换: ' . $count . ' 篇文章', array(
                        'count' => $count,
                        'user_id' => get_current_user_id(),
                    ));
                }
                error_log('[MaBox] 批量替换: ' . $count . ' 篇文章 by user ' . get_current_user_id());

                wp_send_json_success(array(
                    'dry_run' => false,
                    'replaced_count' => $count,
                    'message' => '成功替换 ' . $count . ' 篇文章的内容（已备份原始内容）',
                ));
            }
        }

        /**
         * 回滚批量替换
         */
        public static function rollback() {
            if (!current_user_can('edit_posts')) {
                wp_send_json_error('权限不足');
            }

            $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
            if (empty($post_id)) {
                wp_send_json_error('无效的文章 ID');
            }

            $result = self::rollback_post($post_id);
            wp_send_json_success($result);
        }

        /**
         * 批量回滚所有替换
         */
        public static function rollback_all() {
            if (!current_user_can('edit_posts')) {
                wp_send_json_error('权限不足');
            }

            $backup_key = '_mabox_batch_replace_backup';
            $args = array(
                'post_type' => 'post',
                'post_status' => 'any',
                'posts_per_page' => -1,
                'meta_key' => $backup_key,
            );
            $query = new WP_Query($args);
            $count = 0;

            foreach ($query->posts as $post) {
                $result = self::rollback_post($post->ID);
                if ($result['success']) {
                    $count++;
                }
            }

            if (class_exists('MaBox_Audit_Logger')) {
                MaBox_Audit_Logger::database('批量回滚: ' . $count . ' 篇文章', array(
                    'count' => $count,
                    'user_id' => get_current_user_id(),
                ));
            }
            error_log('[MaBox] 批量回滚: ' . $count . ' 篇文章 by user ' . get_current_user_id());

            wp_send_json_success(array(
                'rolled_back_count' => $count,
                'message' => '成功回滚 ' . $count . ' 篇文章',
            ));
        }
    }
}
