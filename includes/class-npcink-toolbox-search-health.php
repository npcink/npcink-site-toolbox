<?php
defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Search_Health')) {
    class Npcink_Toolbox_Search_Health
    {
        private static $option_key = 'npcink_site_toolbox_search_log';
        private static $keep_days = 30;
        private static $daily_unique_term_limit = 500;
        private static $max_serialized_bytes = 262144;
        private static $writes_per_minute = 300;
        private static $overflow_term = '__npcink_overflow__';
        private static $write_rate_key = 'npcink_site_toolbox_search_log_write_rate';
        private static $write_lock_key = 'npcink_site_toolbox_search_log_write_lock';
        private static $write_lock_ttl = 15;

        public static function rest_get_summary($request)
        {
            $days = $request->get_param('days');
            if (empty($days)) {
                $days = 30;
            }
            return rest_ensure_response(array(
                'success' => true,
                'data' => self::get_summary($days),
            ));
        }

        public static function get_summary($days = 30)
        {
            $days = max(1, min(365, (int) $days));
            $log = self::get_log();
            $config = Npcink_Toolbox_Config_Manager::get_merged_config();
            if (empty($config)) {
                $config = array();
            }

            $cutoff = self::calendar_date_days_ago(current_time('Y-m-d'), $days);
            $total_searches = 0;
            $term_stats = array();

            foreach ($log as $date => $terms) {
                if (!is_string($date) || $date < $cutoff || !is_array($terms)) {
                    continue;
                }
                foreach ($terms as $term => $entry) {
                    $entry = self::normalize_entry($entry);
                    $count = $entry['count'];
                    $no_result = $entry['no_result_count'];
                    $total_searches += $count;

                    if ($term === self::$overflow_term) {
                        continue;
                    }

                    if (!isset($term_stats[$term])) {
                        $term_stats[$term] = array(
                            'count' => 0,
                            'no_result_count' => 0,
                            'last_searched_at' => '',
                        );
                    }
                    $term_stats[$term]['count'] += $count;
                    $term_stats[$term]['no_result_count'] += $no_result;
                    if ($entry['last_searched_at'] > $term_stats[$term]['last_searched_at']) {
                        $term_stats[$term]['last_searched_at'] = $entry['last_searched_at'];
                    }
                }
            }

            $unique_terms = count($term_stats);

            $top_terms = self::get_top_terms($term_stats, 20);
            $no_result_terms = self::get_no_result_terms($term_stats, 20);
            $suspicious_terms = self::detect_suspicious($log, $cutoff, $total_searches);
            $recommendations = self::generate_recommendations($total_searches, $unique_terms, $no_result_terms, $suspicious_terms, $config);

            return array(
                'range_days' => $days,
                'total_searches' => $total_searches,
                'unique_terms' => $unique_terms,
                'top_terms' => $top_terms,
                'no_result_terms' => $no_result_terms,
                'suspicious_terms' => $suspicious_terms,
                'recommendations' => $recommendations,
            );
        }

        public static function log_search_term($term, $has_results = true)
        {
            $term = sanitize_text_field($term);
            if (
                empty($term)
                || mb_strlen($term) > 200
                || $term === self::$overflow_term
                || !self::allow_write()
            ) {
                return;
            }

            $log = self::get_log();
            $today = current_time('Y-m-d');
            $now = current_time('Y-m-d H:i:s');

            if (!isset($log[$today])) {
                $log[$today] = array();
            }
            if (!isset($log[$today][$term])) {
                if (self::count_daily_terms($log[$today]) >= self::$daily_unique_term_limit) {
                    self::increment_overflow($log[$today], $has_results, $now);
                    self::persist_log($log, $today, self::$overflow_term);
                    return;
                }

                $log[$today][$term] = self::empty_entry($now);
            }

            $log[$today][$term] = self::normalize_entry($log[$today][$term]);
            $log[$today][$term]['count']++;
            if (!$has_results) {
                $log[$today][$term]['no_result_count']++;
            }
            $log[$today][$term]['last_searched_at'] = $now;

            self::persist_log($log, $today, $term);
        }

        public static function increment_no_result_count($term)
        {
            $term = sanitize_text_field($term);
            if (empty($term) || mb_strlen($term) > 200) {
                return;
            }

            $log = self::get_log();
            $today = current_time('Y-m-d');
            $now = current_time('Y-m-d H:i:s');

            if (!isset($log[$today]) || !isset($log[$today][$term])) {
                return;
            }

            if (!self::allow_write()) {
                return;
            }

            $log[$today][$term] = self::normalize_entry($log[$today][$term]);
            $log[$today][$term]['no_result_count']++;
            $log[$today][$term]['last_searched_at'] = $now;

            self::persist_log($log, $today, $term);
        }

        private static function get_log()
        {
            $log = get_option(self::$option_key, array());
            return is_array($log) ? $log : array();
        }

        private static function allow_write()
        {
            $lock = self::acquire_write_lock();
            if ($lock === '') {
                return false;
            }

            try {
                $rate = get_transient(self::$write_rate_key);
                $rate = is_array($rate) ? $rate : array();
                $count = isset($rate['count']) ? max(0, (int) $rate['count']) : 0;

                if ($count >= self::$writes_per_minute) {
                    return false;
                }

                set_transient(
                    self::$write_rate_key,
                    array('count' => $count + 1),
                    MINUTE_IN_SECONDS
                );
                return true;
            } finally {
                self::release_write_lock($lock);
            }
        }

        private static function acquire_write_lock()
        {
            $token = self::lock_token();
            $value = self::lock_value($token, time() + self::$write_lock_ttl);
            if (add_option(self::$write_lock_key, $value, '', false)) {
                return $token;
            }

            $existing = get_option(self::$write_lock_key, '');
            $lock = self::parse_lock_value($existing);
            if ($lock['expires'] >= time() || !self::delete_lock_if_value_matches($existing)) {
                return '';
            }

            return add_option(self::$write_lock_key, $value, '', false) ? $token : '';
        }

        private static function release_write_lock($token)
        {
            $existing = get_option(self::$write_lock_key, '');
            $lock = self::parse_lock_value($existing);
            if ($lock['token'] === $token) {
                self::delete_lock_if_value_matches($existing);
            }
        }

        private static function delete_lock_if_value_matches($value)
        {
            global $wpdb;
            if (!is_object($wpdb) || !isset($wpdb->options) || !method_exists($wpdb, 'delete')) {
                return false;
            }

            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- Conditional delete prevents one request from removing another request's concurrency lock.
            $deleted = $wpdb->delete(
                $wpdb->options,
                array('option_name' => self::$write_lock_key, 'option_value' => (string) $value),
                array('%s', '%s')
            );
            if ($deleted) {
                wp_cache_delete(self::$write_lock_key, 'options');
            }
            return $deleted === 1;
        }

        private static function lock_token()
        {
            if (function_exists('wp_generate_uuid4')) {
                return wp_generate_uuid4();
            }
            return md5(uniqid('', true));
        }

        private static function lock_value($token, $expires)
        {
            return $token . '|' . (int) $expires;
        }

        private static function parse_lock_value($value)
        {
            $parts = is_string($value) ? explode('|', $value, 2) : array();
            return array(
                'token' => isset($parts[0]) ? (string) $parts[0] : '',
                'expires' => isset($parts[1]) ? max(0, (int) $parts[1]) : 0,
            );
        }

        private static function persist_log($log, $today, $protected_term = '')
        {
            $log = self::compact_log(self::prune_old_entries($log), $today, $protected_term);
            update_option(self::$option_key, $log, false);
        }

        private static function compact_log($log, $today, $protected_term)
        {
            foreach ($log as $date => $terms) {
                if (!is_string($date) || !is_array($terms)) {
                    unset($log[$date]);
                }
            }

            ksort($log, SORT_STRING);
            if (isset($log[$today])) {
                self::trim_daily_terms($log[$today], $protected_term);
            }

            while (self::serialized_bytes($log) > self::$max_serialized_bytes) {
                $dates = array_keys($log);
                $oldest = reset($dates);
                if ($oldest === false || $oldest === $today) {
                    break;
                }
                unset($log[$oldest]);
            }

            if (self::serialized_bytes($log) > self::$max_serialized_bytes && isset($log[$today])) {
                foreach (array_keys($log[$today]) as $term) {
                    if ($term === $protected_term || $term === self::$overflow_term) {
                        continue;
                    }
                    unset($log[$today][$term]);
                    if (self::serialized_bytes($log) <= self::$max_serialized_bytes) {
                        break;
                    }
                }
            }

            return $log;
        }

        private static function trim_daily_terms(&$terms, $protected_term)
        {
            $excess = self::count_daily_terms($terms) - self::$daily_unique_term_limit;
            if ($excess <= 0) {
                return;
            }

            foreach (array_keys($terms) as $term) {
                if ($term === $protected_term || $term === self::$overflow_term) {
                    continue;
                }
                unset($terms[$term]);
                $excess--;
                if ($excess <= 0) {
                    break;
                }
            }
        }

        private static function serialized_bytes($value)
        {
            return strlen(serialize($value));
        }

        private static function count_daily_terms($terms)
        {
            if (!is_array($terms)) {
                return 0;
            }
            return count($terms) - (isset($terms[self::$overflow_term]) ? 1 : 0);
        }

        private static function increment_overflow(&$terms, $has_results, $now)
        {
            if (!isset($terms[self::$overflow_term])) {
                $terms[self::$overflow_term] = self::empty_entry($now);
            }

            $terms[self::$overflow_term] = self::normalize_entry($terms[self::$overflow_term]);
            $terms[self::$overflow_term]['count']++;
            if (!$has_results) {
                $terms[self::$overflow_term]['no_result_count']++;
            }
            $terms[self::$overflow_term]['last_searched_at'] = $now;
        }

        private static function empty_entry($now = '')
        {
            return array(
                'count' => 0,
                'no_result_count' => 0,
                'last_searched_at' => $now,
            );
        }

        private static function normalize_entry($entry)
        {
            if (is_array($entry)) {
                return array_merge(
                    self::empty_entry(),
                    $entry
                );
            }
            if (is_int($entry)) {
                return array(
                    'count' => $entry,
                    'no_result_count' => 0,
                    'last_searched_at' => '',
                );
            }
            return self::empty_entry();
        }

        private static function calendar_date_days_ago($site_date, $days)
        {
            $date = \DateTimeImmutable::createFromFormat(
                '!Y-m-d',
                $site_date,
                new \DateTimeZone('UTC')
            );
            if (!$date) {
                return $site_date;
            }

            return $date
                ->modify('-' . max(0, (int) $days) . ' days')
                ->format('Y-m-d');
        }

        private static function prune_old_entries($log)
        {
            $cutoff = self::calendar_date_days_ago(current_time('Y-m-d'), self::$keep_days);
            foreach ($log as $date => $terms) {
                if (!is_string($date) || $date < $cutoff || !is_array($terms)) {
                    unset($log[$date]);
                }
            }
            return $log;
        }

        private static function get_top_terms($term_stats, $limit)
        {
            uasort($term_stats, function ($a, $b) {
                return $b['count'] - $a['count'];
            });

            $result = array();
            $i = 0;
            foreach ($term_stats as $term => $stats) {
                if ($i >= $limit) {
                    break;
                }
                $result[] = array(
                    'term' => $term,
                    'count' => $stats['count'],
                    'no_result_count' => $stats['no_result_count'],
                );
                $i++;
            }
            return $result;
        }

        private static function get_no_result_terms($term_stats, $limit)
        {
            $filtered = array();
            foreach ($term_stats as $term => $stats) {
                if ($stats['no_result_count'] > 0) {
                    $filtered[$term] = $stats;
                }
            }

            uasort($filtered, function ($a, $b) {
                return $b['no_result_count'] - $a['no_result_count'];
            });

            $result = array();
            $i = 0;
            foreach ($filtered as $term => $stats) {
                if ($i >= $limit) {
                    break;
                }
                $result[] = array(
                    'term' => $term,
                    'count' => $stats['count'],
                    'no_result_count' => $stats['no_result_count'],
                );
                $i++;
            }
            return $result;
        }

        private static function detect_suspicious($log, $cutoff, $total_searches)
        {
            $suspicious = array();
            $daily_term_counts = array();

            foreach ($log as $date => $terms) {
                if ($date < $cutoff) {
                    continue;
                }
                foreach ($terms as $term => $entry) {
                    if ($term === self::$overflow_term) {
                        continue;
                    }
                    $entry = self::normalize_entry($entry);
                    if (!isset($daily_term_counts[$term])) {
                        $daily_term_counts[$term] = 0;
                    }
                    $daily_term_counts[$term] += $entry['count'];
                }
            }

            foreach ($daily_term_counts as $term => $count) {
                $reason = '';
                if ($count > 100) {
                    $reason = __('单关键词搜索频次异常（超过 100 次）', 'npcink-site-toolbox');
                } elseif ($total_searches > 0 && ($count / $total_searches) > 0.1) {
                    $reason = __('单关键词占比过高（超过 10%）', 'npcink-site-toolbox');
                }

                if (!empty($reason)) {
                    $suspicious[] = array(
                        'term' => $term,
                        'count' => $count,
                        'reason' => $reason,
                    );
                }
            }

            usort($suspicious, function ($a, $b) {
                return $b['count'] - $a['count'];
            });

            return array_slice($suspicious, 0, 20);
        }

        private static function generate_recommendations($total_searches, $unique_terms, $no_result_terms, $suspicious_terms, $config)
        {
            $recommendations = array();

            $page_function = Npcink_Toolbox_Diagnostics::get_nested($config, 'page', 'function');
            if (empty($page_function['search_limit'])) {
                $recommendations[] = array(
                    'id' => 'rec_search_rate_limit',
                    'title' => __('限制搜索频次', 'npcink-site-toolbox'),
                    'reason' => __('未启用搜索频次限制，可能被恶意搜索消耗服务器资源。', 'npcink-site-toolbox'),
                );
            }

            if (!empty($no_result_terms)) {
                $no_result_ratio = 0;
                if ($total_searches > 0) {
                    $no_result_total = 0;
                    foreach ($no_result_terms as $item) {
                        $no_result_total += $item['no_result_count'];
                    }
                    $no_result_ratio = $no_result_total / $total_searches;
                }

                if ($no_result_ratio > 0.5) {
                    $recommendations[] = array(
                        'id' => 'rec_no_result_high',
                        'title' => __('无结果搜索比例过高', 'npcink-site-toolbox'),
                        /* translators: %.0f: Percentage of searches with no results. */
                        'reason' => sprintf(__('超过 %.0f%% 的搜索无结果，建议为热门无结果词补充相关内容。', 'npcink-site-toolbox'), $no_result_ratio * 100),
                    );
                } elseif ($no_result_ratio > 0.2) {
                    $recommendations[] = array(
                        'id' => 'rec_no_result_moderate',
                        'title' => __('关注无结果搜索词', 'npcink-site-toolbox'),
                        /* translators: %.0f: Percentage of searches with no results. */
                        'reason' => sprintf(__('约 %.0f%% 的搜索无结果，可考虑补充相关内容。', 'npcink-site-toolbox'), $no_result_ratio * 100),
                    );
                }
            }

            if (!empty($suspicious_terms)) {
                $recommendations[] = array(
                    'id' => 'rec_suspicious_search',
                    'title' => __('检测到异常高频搜索', 'npcink-site-toolbox'),
                    /* translators: %d: Number of suspicious high-frequency search terms. */
                    'reason' => sprintf(__('发现 %d 个异常高频搜索词，可能为爬虫或恶意行为，建议开启搜索频次限制。', 'npcink-site-toolbox'), count($suspicious_terms)),
                );
            }

            $search_enhance = Npcink_Toolbox_Diagnostics::get_nested($config, 'performance', 'search_enhance');
            if (empty($search_enhance['hotwords_enabled'])) {
                $recommendations[] = array(
                    'id' => 'rec_enable_search_log',
                    'title' => __('开启搜索日志', 'npcink-site-toolbox'),
                    'reason' => __('搜索日志已关闭，无法收集搜索健康数据。建议开启以获得搜索分析。', 'npcink-site-toolbox'),
                );
            }

            return $recommendations;
        }
    }
}
