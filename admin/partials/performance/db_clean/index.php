<?php

defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Performance_Db_Clean')) {
    class Npcink_Toolbox_Performance_Db_Clean implements Npcink_Toolbox_Module_Interface
    {
        private const BATCH_SIZE = 100;
        private const CRON_HOOK = 'npcink_site_toolbox_auto_db_clean';
        private const PREVIEW_TTL = 300;
        private const CONSUMED_PREVIEW_META_KEY = 'npcink_site_toolbox_consumed_db_previews';

        private static $config = array();

        public static function run($config = array())
        {
            self::$config = is_array($config) ? $config : array();
            self::sync_schedule(self::$config);
        }

        /**
         * Keep the scheduled event aligned with the persisted performance option.
         *
         * @param mixed $old_value Previous performance option value.
         * @param mixed $new_value New performance option value.
         */
        public static function handle_performance_option_update($old_value, $new_value)
        {
            $config = is_array($new_value) && isset($new_value['db_clean']) && is_array($new_value['db_clean'])
                ? $new_value['db_clean']
                : array();

            self::sync_schedule($config);
        }

        /**
         * Schedule, reschedule, or clear the automatic cleanup event.
         *
         * @param array $config Database cleanup configuration.
         */
        private static function sync_schedule($config = array())
        {
            if (empty($config['enabled']) || empty($config['auto_clean'])) {
                self::clear_schedule();
                return;
            }

            $allowed_schedules = array('daily', 'weekly', 'monthly');
            $schedule = isset($config['auto_clean_schedule']) && in_array($config['auto_clean_schedule'], $allowed_schedules, true)
                ? $config['auto_clean_schedule']
                : 'weekly';
            $event = wp_get_scheduled_event(self::CRON_HOOK);

            if ($event && isset($event->schedule) && $event->schedule === $schedule) {
                return;
            }

            if ($event) {
                self::clear_schedule();
            }

            // Do not create a duplicate event when an existing event could not be cleared.
            if (!wp_get_scheduled_event(self::CRON_HOOK)) {
                $schedule_intervals = array(
                    'daily'   => 86400,
                    'weekly'  => 604800,
                    'monthly' => 2592000,
                );
                wp_schedule_event(time() + $schedule_intervals[$schedule], $schedule, self::CRON_HOOK);
            }
        }

        /**
         * Remove every scheduled occurrence for this plugin hook.
         */
        public static function clear_schedule()
        {
            wp_clear_scheduled_hook(self::CRON_HOOK);
        }

        /**
         * Run from WP-Cron using the latest persisted configuration.
         */
        public static function run_scheduled_cleanup()
        {
            $performance = get_option(NPCINK_SITE_TOOLBOX_OPTION_PERFORMANCE, array());
            $config = is_array($performance) && isset($performance['db_clean']) && is_array($performance['db_clean'])
                ? $performance['db_clean']
                : array();

            if (empty($config['enabled']) || empty($config['auto_clean'])) {
                self::clear_schedule();
                return;
            }

            self::$config = $config;
            self::sync_schedule($config);
            self::auto_clean();
        }

        public static function add_cron_schedules($schedules)
        {
            $schedules['weekly'] = array('interval' => 604800, 'display' => __('每周', 'npcink-site-toolbox'));
            $schedules['monthly'] = array('interval' => 2592000, 'display' => __('每月', 'npcink-site-toolbox'));

            return $schedules;
        }

        public static function ajax_stats()
        {
            if (!current_user_can('manage_options')) {
                return new \WP_Error('rest_forbidden', __('权限不足', 'npcink-site-toolbox'), array('status' => 403));
            }

            $stats = self::get_cleanup_counts();
            $stats['db_size'] = self::get_database_size();

            return rest_ensure_response(array(
                'success' => true,
                'data'    => $stats,
            ));
        }

        /**
         * 预览清理影响（dry-run）。
         */
        public static function ajax_preview(\WP_REST_Request $request)
        {
            if (!current_user_can('manage_options')) {
                return new \WP_Error('rest_forbidden', __('权限不足', 'npcink-site-toolbox'), array('status' => 403));
            }

            $params = $request->get_json_params();
            $params = is_array($params) ? $params : array();
            $type_value = isset($params['type']) ? $params['type'] : '';
            $type = is_string($type_value) ? sanitize_key($type_value) : '';
            $allowed_types = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'pending', 'trash');
            if (!in_array($type, $allowed_types, true)) {
                return new \WP_Error('rest_invalid_param', __('无效的清理类型', 'npcink-site-toolbox'), array('status' => 400));
            }

            $preview = self::build_preview($type);
            $preview['preview_token'] = self::issue_preview_token($type, $preview);
            $preview['expires_in'] = self::PREVIEW_TTL;

            return rest_ensure_response(array(
                'success' => true,
                'data'    => $preview,
            ));
        }

        public static function ajax_clean(\WP_REST_Request $request)
        {
            if (!current_user_can('manage_options')) {
                return new \WP_Error('rest_forbidden', __('权限不足', 'npcink-site-toolbox'), array('status' => 403));
            }

            $params = $request->get_json_params();
            $params = is_array($params) ? $params : array();
            $type_value = isset($params['type']) ? $params['type'] : '';
            $type = is_string($type_value) ? sanitize_key($type_value) : '';
            $dry_run_value = array_key_exists('dry_run', $params) ? $params['dry_run'] : true;
            $dry_run = is_scalar($dry_run_value) ? rest_sanitize_boolean($dry_run_value) : true;

            $allowed_types = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'pending', 'trash');
            if (!in_array($type, $allowed_types, true)) {
                return new \WP_Error('rest_invalid_param', __('无效的清理类型', 'npcink-site-toolbox'), array('status' => 400));
            }

            if ($dry_run) {
                $preview = self::build_preview($type);
                $preview['preview_token'] = self::issue_preview_token($type, $preview);
                $preview['expires_in'] = self::PREVIEW_TTL;
                return rest_ensure_response(array(
                    'success' => true,
                    'data'    => $preview,
                ));
            }

            $token_value = isset($params['preview_token']) ? $params['preview_token'] : '';
            $preview_token = is_string($token_value) ? sanitize_text_field($token_value) : '';
            if (!preg_match('/^[A-Za-z0-9_-]+\.[a-f0-9]{64}$/', $preview_token)) {
                return new \WP_Error(
                    'rest_db_preview_required',
                    __('请先重新预览该清理项目，再确认执行。', 'npcink-site-toolbox'),
                    array('status' => 409)
                );
            }

            $preview_validation = self::consume_preview_token($type, $preview_token);
            if (!$preview_validation['valid']) {
                return new \WP_Error(
                    $preview_validation['code'],
                    $preview_validation['message'],
                    array('status' => 409)
                );
            }

            if (class_exists('Npcink_Toolbox_Audit_Logger')) {
                Npcink_Toolbox_Audit_Logger::database('数据库清理: type=' . $type, array(
                    'type' => $type,
                    'user_id' => get_current_user_id(),
                    'dry_run' => false,
                ));
            }

            $result = array('deleted' => 0);
            if ('optimize' === $type) {
                $result['optimized'] = self::optimize_tables();
                $result['message'] = __('数据库表优化完成', 'npcink-site-toolbox');
            } else {
                $result['deleted'] = self::clean_type($type);
            }

            $result['dry_run'] = false;
            return rest_ensure_response(array(
                'success' => true,
                'data'    => $result,
            ));
        }

        public static function auto_clean()
        {
            if (!empty(self::$config['clean_revisions'])) {
                self::clean_type('revisions');
            }
            if (!empty(self::$config['clean_drafts'])) {
                self::clean_type('drafts');
            }
            if (!empty(self::$config['clean_spam_comments'])) {
                self::clean_type('spam');
            }
            if (!empty(self::$config['clean_transients'])) {
                self::clean_type('transients');
            }
        }

        /**
         * Build a fresh preview. Cleanup counts intentionally are not persisted.
         *
         * @param string $type Cleanup type.
         * @return array<string, int|string|bool>
         */
        private static function build_preview($type)
        {
            if ('optimize' === $type) {
                $tables = self::get_optimizable_tables();
                return array(
                    'message' => __('将优化当前站点的数据库表（不删除数据）', 'npcink-site-toolbox'),
                    'table_count' => count($tables),
                    'table_fingerprint' => hash('sha256', serialize($tables)),
                    'dry_run' => true,
                );
            }

            $counts = self::get_cleanup_counts();
            $messages = array(
                'revisions' => __('将删除 %d 个文章修订版本', 'npcink-site-toolbox'),
                'drafts' => __('将删除 %d 个自动草稿', 'npcink-site-toolbox'),
                'spam' => __('将删除 %d 条垃圾评论', 'npcink-site-toolbox'),
                'transients' => __('将删除 %d 个过期临时选项', 'npcink-site-toolbox'),
                'pending' => __('将删除 %d 个待审核文章', 'npcink-site-toolbox'),
                'trash' => __('将删除 %d 个回收站文章', 'npcink-site-toolbox'),
            );
            $affected = isset($counts[$type]) ? $counts[$type] : 0;
            $message_format = isset($messages[$type])
                ? $messages[$type]
                : __('将删除 %d 条数据', 'npcink-site-toolbox');

            return array(
                'affected' => $affected,
                'message' => sprintf($message_format, $affected),
                'dry_run' => true,
            );
        }

        /**
         * Issue a signed token for an exact cleanup preview without persisting counts.
         *
         * @param string $type Cleanup type.
         * @param array  $preview Fresh preview data.
         * @return string
         */
        private static function issue_preview_token($type, $preview)
        {
            try {
                $nonce = bin2hex(random_bytes(16));
            } catch (\Exception $exception) {
                $nonce = hash('sha256', uniqid(self::CONSUMED_PREVIEW_META_KEY, true));
            }

            $payload = array(
                'type' => $type,
                'user_id' => get_current_user_id(),
                'expires_at' => time() + self::PREVIEW_TTL,
                'fingerprint' => self::preview_fingerprint($type, $preview),
                'nonce' => $nonce,
            );
            $encoded = self::base64url_encode(wp_json_encode($payload));
            $signature = hash_hmac('sha256', $encoded, self::preview_signing_key());

            return $encoded . '.' . $signature;
        }

        /**
         * Validate and consume a preview token before any destructive operation.
         *
         * @param string $type Cleanup type.
         * @param string $token Preview token.
         * @return array{valid: bool, code?: string, message?: string}
         */
        private static function consume_preview_token($type, $token)
        {
            $parts = explode('.', $token, 2);
            if (count($parts) !== 2
                || !hash_equals(hash_hmac('sha256', $parts[0], self::preview_signing_key()), $parts[1])) {
                return array(
                    'valid' => false,
                    'code' => 'rest_db_preview_expired',
                    'message' => __('清理预览已失效或不属于当前操作，请重新预览。', 'npcink-site-toolbox'),
                );
            }

            $decoded = self::base64url_decode($parts[0]);
            $payload = is_string($decoded) ? json_decode($decoded, true) : null;
            if (!is_array($payload)
                || !isset($payload['type'], $payload['user_id'], $payload['expires_at'], $payload['fingerprint'])
                || !is_string($payload['type'])
                || !is_string($payload['fingerprint'])
                || $payload['type'] !== $type
                || (int) $payload['user_id'] !== get_current_user_id()
                || (int) $payload['expires_at'] < time()
                || self::is_preview_consumed($token)) {
                return array(
                    'valid' => false,
                    'code' => 'rest_db_preview_expired',
                    'message' => __('清理预览已失效或不属于当前操作，请重新预览。', 'npcink-site-toolbox'),
                );
            }

            self::mark_preview_consumed($token, (int) $payload['expires_at']);

            $current_preview = self::build_preview($type);
            $current_fingerprint = self::preview_fingerprint($type, $current_preview);

            if (!hash_equals($payload['fingerprint'], $current_fingerprint)) {
                return array(
                    'valid' => false,
                    'code' => 'rest_db_preview_conflict',
                    'message' => __('数据库内容已发生变化，请重新预览并确认最新影响范围。', 'npcink-site-toolbox'),
                );
            }

            return array('valid' => true);
        }

        private static function preview_signing_key()
        {
            if (function_exists('wp_salt')) {
                return wp_salt('nonce');
            }
            if (defined('NONCE_SALT') && NONCE_SALT !== '') {
                return NONCE_SALT;
            }
            return 'npcink-site-toolbox-db-preview';
        }

        private static function base64url_encode($value)
        {
            return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
        }

        private static function base64url_decode($value)
        {
            $padding = strlen($value) % 4;
            if ($padding) {
                $value .= str_repeat('=', 4 - $padding);
            }
            return base64_decode(strtr($value, '-_', '+/'), true);
        }

        private static function is_preview_consumed($token)
        {
            $consumed = get_user_meta(get_current_user_id(), self::CONSUMED_PREVIEW_META_KEY, true);
            return is_array($consumed) && isset($consumed[hash('sha256', $token)]);
        }

        private static function mark_preview_consumed($token, $expires_at)
        {
            $user_id = get_current_user_id();
            $consumed = get_user_meta($user_id, self::CONSUMED_PREVIEW_META_KEY, true);
            $consumed = is_array($consumed) ? $consumed : array();
            $now = time();
            foreach ($consumed as $token_hash => $expiry) {
                if ((int) $expiry < $now) {
                    unset($consumed[$token_hash]);
                }
            }
            $consumed[hash('sha256', $token)] = $expires_at;
            update_user_meta($user_id, self::CONSUMED_PREVIEW_META_KEY, $consumed);
        }

        /**
         * @param string $type Cleanup type.
         * @param array  $preview Preview data.
         * @return string
         */
        private static function preview_fingerprint($type, $preview)
        {
            return hash('sha256', serialize(array(
                'type' => $type,
                'preview' => $preview,
            )));
        }

        /**
         * Fetch one fresh, internally consistent snapshot for all cleanup counters.
         *
         * @return array{revisions: int, drafts: int, spam: int, transients: int, pending: int, trash: int}
         */
        private static function get_cleanup_counts()
        {
            global $wpdb;

            $transient_timeout_pattern = $wpdb->esc_like('_transient_timeout_') . '%';
            $site_transient_timeout_pattern = $wpdb->esc_like('_site_transient_timeout_') . '%';
            $now = time();
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin stats and dry-run previews require a fresh snapshot; one merged query replaces repeated uncached counts.
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s) AS revisions,
                        (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s) AS drafts,
                        (SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s) AS spam,
                        (SELECT COUNT(*) FROM {$wpdb->options}
                         WHERE (option_name LIKE %s OR option_name LIKE %s)
                           AND CAST(option_value AS UNSIGNED) < %d) AS transients,
                        (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s) AS pending,
                        (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s) AS trash",
                    'revision',
                    'auto-draft',
                    'spam',
                    $transient_timeout_pattern,
                    $site_transient_timeout_pattern,
                    $now,
                    'pending',
                    'trash'
                ),
                ARRAY_A
            );
            $row = is_array($row) ? $row : array();

            return array(
                'revisions' => isset($row['revisions']) ? absint($row['revisions']) : 0,
                'drafts' => isset($row['drafts']) ? absint($row['drafts']) : 0,
                'spam' => isset($row['spam']) ? absint($row['spam']) : 0,
                'transients' => isset($row['transients']) ? absint($row['transients']) : 0,
                'pending' => isset($row['pending']) ? absint($row['pending']) : 0,
                'trash' => isset($row['trash']) ? absint($row['trash']) : 0,
            );
        }

        /**
         * Return the current site's table footprint without persisting stale size data.
         *
         * @return string
         */
        private static function get_database_size()
        {
            global $wpdb;

            $table_pattern = $wpdb->esc_like($wpdb->prefix) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Database size is live operational data and must not be served from a persistent cache.
            $table_statuses = $wpdb->get_results(
                $wpdb->prepare('SHOW TABLE STATUS LIKE %s', $table_pattern),
                ARRAY_A
            );
            $db_size = 0;
            if (is_array($table_statuses)) {
                foreach ($table_statuses as $table_status) {
                    $data_length = isset($table_status['Data_length']) ? (int) $table_status['Data_length'] : 0;
                    $index_length = isset($table_status['Index_length']) ? (int) $table_status['Index_length'] : 0;
                    $db_size += $data_length + $index_length;
                }
            }

            return size_format($db_size);
        }

        /**
         * @param string $type Cleanup type.
         * @return int Number of deleted objects.
         */
        private static function clean_type($type)
        {
            switch ($type) {
                case 'revisions':
                    return self::delete_revisions();
                case 'drafts':
                    return self::delete_posts_by_status('auto-draft');
                case 'spam':
                    return self::delete_spam_comments();
                case 'transients':
                    return self::delete_transients();
                case 'pending':
                    return self::delete_posts_by_status('pending');
                case 'trash':
                    return self::delete_posts_by_status('trash');
                default:
                    return 0;
            }
        }

        /**
         * Delete revisions through core so post meta, hooks and object caches stay consistent.
         *
         * @return int
         */
        private static function delete_revisions()
        {
            global $wpdb;

            $deleted = 0;
            $last_id = 0;
            do {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Maintenance reads a bounded, fresh ID batch; wp_delete_post_revision() performs the actual cache-aware deletion.
                $post_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} WHERE post_type = %s AND ID > %d ORDER BY ID ASC LIMIT %d",
                        'revision',
                        $last_id,
                        self::BATCH_SIZE
                    )
                );
                if (!is_array($post_ids) || empty($post_ids)) {
                    break;
                }

                foreach ($post_ids as $post_id) {
                    $post_id = absint($post_id);
                    $last_id = max($last_id, $post_id);
                    if ($post_id && wp_delete_post_revision($post_id)) {
                        ++$deleted;
                    }
                }
            } while (count($post_ids) === self::BATCH_SIZE);

            return $deleted;
        }

        /**
         * Delete posts through core so related data, hooks and object caches stay consistent.
         *
         * @param string $status Internal, allowlisted post status.
         * @return int
         */
        private static function delete_posts_by_status($status)
        {
            if (!in_array($status, array('auto-draft', 'pending', 'trash'), true)) {
                return 0;
            }

            global $wpdb;
            $deleted = 0;
            $last_id = 0;
            do {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Maintenance reads a bounded, fresh ID batch; wp_delete_post() performs the actual cache-aware deletion.
                $post_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT ID FROM {$wpdb->posts} WHERE post_status = %s AND ID > %d ORDER BY ID ASC LIMIT %d",
                        $status,
                        $last_id,
                        self::BATCH_SIZE
                    )
                );
                if (!is_array($post_ids) || empty($post_ids)) {
                    break;
                }

                foreach ($post_ids as $post_id) {
                    $post_id = absint($post_id);
                    $last_id = max($last_id, $post_id);
                    if ($post_id && wp_delete_post($post_id, true)) {
                        ++$deleted;
                    }
                }
            } while (count($post_ids) === self::BATCH_SIZE);

            return $deleted;
        }

        /**
         * Delete spam through core so comment meta, hooks and count caches stay consistent.
         *
         * @return int
         */
        private static function delete_spam_comments()
        {
            global $wpdb;

            $deleted = 0;
            $last_id = 0;
            do {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Maintenance reads a bounded, fresh ID batch; wp_delete_comment() performs the actual cache-aware deletion.
                $comment_ids = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT comment_ID FROM {$wpdb->comments} WHERE comment_approved = %s AND comment_ID > %d ORDER BY comment_ID ASC LIMIT %d",
                        'spam',
                        $last_id,
                        self::BATCH_SIZE
                    )
                );
                if (!is_array($comment_ids) || empty($comment_ids)) {
                    break;
                }

                foreach ($comment_ids as $comment_id) {
                    $comment_id = absint($comment_id);
                    $last_id = max($last_id, $comment_id);
                    if ($comment_id && wp_delete_comment($comment_id, true)) {
                        ++$deleted;
                    }
                }
            } while (count($comment_ids) === self::BATCH_SIZE);

            return $deleted;
        }

        /**
         * Delete only transient keys whose persisted timeout has expired. Transient APIs
         * and the Options API keep object caches and orphaned rows consistent.
         *
         * @return int
         */
        private static function delete_transients()
        {
            global $wpdb;

            $patterns = array(
                $wpdb->esc_like('_transient_timeout_') . '%',
                $wpdb->esc_like('_site_transient_timeout_') . '%',
            );
            $deleted = 0;
            $last_option_name = '';
            $now = time();

            do {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Maintenance reads a bounded, fresh option-name batch; Transient and Options APIs perform cache-aware deletion.
                $option_names = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT option_name FROM {$wpdb->options}
                            WHERE option_name > %s
                              AND (option_name LIKE %s OR option_name LIKE %s)
                              AND CAST(option_value AS UNSIGNED) < %d
                            ORDER BY option_name ASC
                            LIMIT %d",
                        $last_option_name,
                        $patterns[0],
                        $patterns[1],
                        $now,
                        self::BATCH_SIZE
                    )
                );
                if (!is_array($option_names) || empty($option_names)) {
                    break;
                }

                foreach ($option_names as $option_name) {
                    if (!is_string($option_name)) {
                        continue;
                    }
                    $last_option_name = $option_name;
                    $transient = self::parse_transient_option_name($option_name);
                    if (null === $transient) {
                        continue;
                    }

                    $key = $transient['key'];
                    $api_deleted = $transient['site'] ? delete_site_transient($key) : delete_transient($key);
                    $option_prefix = $transient['site'] ? '_site_transient_' : '_transient_';
                    $timeout_prefix = $transient['site'] ? '_site_transient_timeout_' : '_transient_timeout_';
                    $value_deleted = delete_option($option_prefix . $key);
                    $timeout_deleted = delete_option($timeout_prefix . $key);
                    if ($api_deleted || $value_deleted || $timeout_deleted) {
                        ++$deleted;
                    }
                }
            } while (count($option_names) === self::BATCH_SIZE);

            return $deleted;
        }

        /**
         * @param string $option_name Transient option name.
         * @return array{key: string, site: bool}|null
         */
        private static function parse_transient_option_name($option_name)
        {
            $prefixes = array(
                '_site_transient_timeout_' => true,
                '_site_transient_' => true,
                '_transient_timeout_' => false,
                '_transient_' => false,
            );

            foreach ($prefixes as $prefix => $site) {
                if (0 !== strpos($option_name, $prefix)) {
                    continue;
                }

                $key = substr($option_name, strlen($prefix));
                if ('' === $key) {
                    return null;
                }

                return array('key' => $key, 'site' => $site);
            }

            return null;
        }

        /**
         * Optimize only tables belonging to the current site. WordPress 6.0 does not
         * support identifier placeholders, so validated identifiers are quoted directly.
         *
         * @return int
         */
        private static function optimize_tables()
        {
            global $wpdb;
            $tables = self::get_optimizable_tables();

            $optimized = 0;
            foreach ($tables as $table_name) {
                if (!self::is_safe_table_name($table_name, $wpdb->prefix)) {
                    continue;
                }

                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared,WordPress.DB.PreparedSQL.InterpolatedNotPrepared,PluginCheck.Security.DirectDB.UnescapedDBParameter -- Identifier is restricted to the current prefix and [A-Za-z0-9_] for WordPress 6.0 compatibility; this is an intentional maintenance write.
                $result = $wpdb->query('OPTIMIZE TABLE `' . $table_name . '`');
                if (false !== $result) {
                    ++$optimized;
                }
            }

            return $optimized;
        }

        /**
         * Return a stable list of safe tables belonging to the current site.
         *
         * @return array<int, string>
         */
        private static function get_optimizable_tables()
        {
            global $wpdb;

            $table_pattern = $wpdb->esc_like($wpdb->prefix) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Live table discovery is required for preview and immediately before maintenance.
            $tables = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $table_pattern));
            if (!is_array($tables)) {
                return array();
            }

            $safe_tables = array_values(array_filter($tables, function ($table_name) use ($wpdb) {
                return self::is_safe_table_name($table_name, $wpdb->prefix);
            }));
            sort($safe_tables, SORT_STRING);
            return $safe_tables;
        }

        /**
         * @param mixed  $table_name Candidate table name.
         * @param string $prefix Current site table prefix.
         * @return bool
         */
        private static function is_safe_table_name($table_name, $prefix)
        {
            return is_string($table_name)
                && is_string($prefix)
                && '' !== $prefix
                && 0 === strpos($table_name, $prefix)
                && 1 === preg_match('/\A[A-Za-z0-9_]+\z/', $table_name);
        }
    }
}
