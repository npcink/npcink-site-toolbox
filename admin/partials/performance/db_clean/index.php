<?php

defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Performance_Db_Clean')) {
    class Npcink_Toolbox_Performance_Db_Clean implements Npcink_Toolbox_Module_Interface
    {
        private const BATCH_SIZE = 100;
        private const CRON_HOOK = 'npcink_site_toolbox_auto_db_clean';

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
                wp_schedule_event(time(), $schedule, self::CRON_HOOK);
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
            $schedules['weekly'] = array('interval' => 604800, 'display' => '每周');
            $schedules['monthly'] = array('interval' => 2592000, 'display' => '每月');

            return $schedules;
        }

        public static function ajax_stats()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('权限不足', 403);
            }

            $stats = self::get_cleanup_counts();
            $stats['db_size'] = self::get_database_size();

            wp_send_json_success($stats);
        }

        /**
         * 预览清理影响（dry-run）。
         */
        public static function ajax_preview(\WP_REST_Request $request)
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('权限不足', 403);
            }

            $params = $request->get_json_params();
            $params = is_array($params) ? $params : array();
            $type_value = isset($params['type']) ? $params['type'] : '';
            $type = is_string($type_value) ? sanitize_key($type_value) : '';
            $allowed_types = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'pending', 'trash');
            if (!in_array($type, $allowed_types, true)) {
                wp_send_json_error('无效的清理类型', 400);
            }

            wp_send_json_success(self::build_preview($type));
        }

        public static function ajax_clean(\WP_REST_Request $request)
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('权限不足', 403);
            }

            $params = $request->get_json_params();
            $params = is_array($params) ? $params : array();
            $type_value = isset($params['type']) ? $params['type'] : '';
            $type = is_string($type_value) ? sanitize_key($type_value) : '';
            $dry_run_value = array_key_exists('dry_run', $params) ? $params['dry_run'] : true;
            $dry_run = is_scalar($dry_run_value) ? rest_sanitize_boolean($dry_run_value) : true;

            $allowed_types = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'pending', 'trash');
            if (!in_array($type, $allowed_types, true)) {
                wp_send_json_error('无效的清理类型', 400);
            }

            if ($dry_run) {
                wp_send_json_success(self::build_preview($type));
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
                $result['message'] = '数据库表优化完成';
            } else {
                $result['deleted'] = self::clean_type($type);
            }

            $result['dry_run'] = false;
            wp_send_json_success($result);
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
                return array(
                    'message' => '将优化当前站点的数据库表（不删除数据）',
                    'dry_run' => true,
                );
            }

            $counts = self::get_cleanup_counts();
            $messages = array(
                'revisions' => '个文章修订版本',
                'drafts' => '个自动草稿',
                'spam' => '条垃圾评论',
                'transients' => '个临时选项',
                'pending' => '个待审核文章',
                'trash' => '个回收站文章',
            );
            $affected = isset($counts[$type]) ? $counts[$type] : 0;

            return array(
                'affected' => $affected,
                'message' => '将删除 ' . $affected . ' ' . (isset($messages[$type]) ? $messages[$type] : '条数据'),
                'dry_run' => true,
            );
        }

        /**
         * Fetch one fresh, internally consistent snapshot for all cleanup counters.
         *
         * @return array{revisions: int, drafts: int, spam: int, transients: int, pending: int, trash: int}
         */
        private static function get_cleanup_counts()
        {
            global $wpdb;

            $transient_pattern = $wpdb->esc_like('_transient_') . '%';
            $transient_timeout_pattern = $wpdb->esc_like('_transient_timeout_') . '%';
            $site_transient_pattern = $wpdb->esc_like('_site_transient_') . '%';
            $site_transient_timeout_pattern = $wpdb->esc_like('_site_transient_timeout_') . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Admin stats and dry-run previews require a fresh snapshot; one merged query replaces repeated uncached counts.
            $row = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT
                        (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = %s) AS revisions,
                        (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s) AS drafts,
                        (SELECT COUNT(*) FROM {$wpdb->comments} WHERE comment_approved = %s) AS spam,
                        (SELECT COUNT(DISTINCT CASE
                            WHEN option_name LIKE %s THEN CONCAT('site:', SUBSTRING(option_name, %d))
                            WHEN option_name LIKE %s THEN CONCAT('site:', SUBSTRING(option_name, %d))
                            WHEN option_name LIKE %s THEN CONCAT('local:', SUBSTRING(option_name, %d))
                            WHEN option_name LIKE %s THEN CONCAT('local:', SUBSTRING(option_name, %d))
                            END)
                         FROM {$wpdb->options}
                         WHERE option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s) AS transients,
                        (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s) AS pending,
                        (SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = %s) AS trash",
                    'revision',
                    'auto-draft',
                    'spam',
                    $site_transient_timeout_pattern,
                    strlen('_site_transient_timeout_') + 1,
                    $site_transient_pattern,
                    strlen('_site_transient_') + 1,
                    $transient_timeout_pattern,
                    strlen('_transient_timeout_') + 1,
                    $transient_pattern,
                    strlen('_transient_') + 1,
                    $site_transient_timeout_pattern,
                    $site_transient_pattern,
                    $transient_timeout_pattern,
                    $transient_pattern,
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
         * Delete transient keys through their APIs and clean orphaned option rows through
         * the Options API. Both paths invalidate the corresponding object-cache entries.
         *
         * @return int
         */
        private static function delete_transients()
        {
            global $wpdb;

            $patterns = array(
                $wpdb->esc_like('_transient_') . '%',
                $wpdb->esc_like('_transient_timeout_') . '%',
                $wpdb->esc_like('_site_transient_') . '%',
                $wpdb->esc_like('_site_transient_timeout_') . '%',
            );
            $deleted = 0;
            $last_option_name = '';

            do {
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Maintenance reads a bounded, fresh option-name batch; Transient and Options APIs perform cache-aware deletion.
                $option_names = $wpdb->get_col(
                    $wpdb->prepare(
                        "SELECT option_name FROM {$wpdb->options}
                            WHERE option_name > %s
                              AND (option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s OR option_name LIKE %s)
                            ORDER BY option_name ASC
                            LIMIT %d",
                        $last_option_name,
                        $patterns[0],
                        $patterns[1],
                        $patterns[2],
                        $patterns[3],
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

            $table_pattern = $wpdb->esc_like($wpdb->prefix) . '%';
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Live table discovery is required immediately before this maintenance operation.
            $tables = $wpdb->get_col($wpdb->prepare('SHOW TABLES LIKE %s', $table_pattern));
            if (!is_array($tables)) {
                return 0;
            }

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
