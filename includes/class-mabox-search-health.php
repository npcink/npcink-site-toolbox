<?php
defined('ABSPATH') || exit;

if (!class_exists('MaBox_Search_Health')) {
    class MaBox_Search_Health
    {
        private static $option_key = 'mabox_search_log';
        private static $keep_days = 30;

        public static function rest_get_summary($request)
        {
            $days = $request->get_param('days');
            if (empty($days)) {
                $days = 30;
            }
            return rest_ensure_response(self::get_summary($days));
        }

        public static function get_summary($days = 30)
        {
            $days = max(1, min(365, (int) $days));
            $log = self::get_log();
            $config = MaBox_Config_Manager::get_merged_config();
            if (empty($config)) {
                $config = array();
            }

            $cutoff = date('Y-m-d', strtotime("-{$days} days"));
            $total_searches = 0;
            $term_stats = array();

            foreach ($log as $date => $terms) {
                if ($date < $cutoff) {
                    continue;
                }
                foreach ($terms as $term => $entry) {
                    $entry = self::normalize_entry($entry);
                    $count = $entry['count'];
                    $no_result = $entry['no_result_count'];
                    $total_searches += $count;

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
            if (empty($term) || mb_strlen($term) > 200) {
                return;
            }

            $log = self::get_log();
            $today = current_time('Y-m-d');
            $now = current_time('Y-m-d H:i:s');

            if (!isset($log[$today])) {
                $log[$today] = array();
            }
            if (!isset($log[$today][$term])) {
                $log[$today][$term] = array(
                    'count' => 0,
                    'no_result_count' => 0,
                    'last_searched_at' => $now,
                );
            }

            $log[$today][$term] = self::normalize_entry($log[$today][$term]);
            $log[$today][$term]['count']++;
            if (!$has_results) {
                $log[$today][$term]['no_result_count']++;
            }
            $log[$today][$term]['last_searched_at'] = $now;

            $log = self::prune_old_entries($log);

            update_option(self::$option_key, $log, false);
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

            $log[$today][$term] = self::normalize_entry($log[$today][$term]);
            $log[$today][$term]['no_result_count']++;
            $log[$today][$term]['last_searched_at'] = $now;

            $log = self::prune_old_entries($log);

            update_option(self::$option_key, $log, false);
        }

        private static function get_log()
        {
            return get_option(self::$option_key, array());
        }

        private static function normalize_entry($entry)
        {
            if (is_array($entry)) {
                return array_merge(
                    array('count' => 0, 'no_result_count' => 0, 'last_searched_at' => ''),
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
            return array('count' => 0, 'no_result_count' => 0, 'last_searched_at' => '');
        }

        private static function prune_old_entries($log)
        {
            $cutoff = date('Y-m-d', strtotime('-' . self::$keep_days . ' days'));
            foreach ($log as $date => $terms) {
                if ($date < $cutoff) {
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
                    $reason = __('单关键词搜索频次异常（超过 100 次）', 'magick-toolbox');
                } elseif ($total_searches > 0 && ($count / $total_searches) > 0.1) {
                    $reason = __('单关键词占比过高（超过 10%）', 'magick-toolbox');
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

            $page_function = MaBox_Diagnostics::get_nested($config, 'page', 'function');
            if (empty($page_function['search_limit'])) {
                $recommendations[] = array(
                    'id' => 'rec_search_rate_limit',
                    'title' => __('限制搜索频次', 'magick-toolbox'),
                    'reason' => __('未启用搜索频次限制，可能被恶意搜索消耗服务器资源。', 'magick-toolbox'),
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
                        'title' => __('无结果搜索比例过高', 'magick-toolbox'),
                        'reason' => sprintf(__('超过 %.0f%% 的搜索无结果，建议为热门无结果词补充相关内容。', 'magick-toolbox'), $no_result_ratio * 100),
                    );
                } elseif ($no_result_ratio > 0.2) {
                    $recommendations[] = array(
                        'id' => 'rec_no_result_moderate',
                        'title' => __('关注无结果搜索词', 'magick-toolbox'),
                        'reason' => sprintf(__('约 %.0f%% 的搜索无结果，可考虑补充相关内容。', 'magick-toolbox'), $no_result_ratio * 100),
                    );
                }
            }

            if (!empty($suspicious_terms)) {
                $recommendations[] = array(
                    'id' => 'rec_suspicious_search',
                    'title' => __('检测到异常高频搜索', 'magick-toolbox'),
                    'reason' => sprintf(__('发现 %d 个异常高频搜索词，可能为爬虫或恶意行为，建议开启搜索频次限制。', 'magick-toolbox'), count($suspicious_terms)),
                );
            }

            $search_enhance = MaBox_Diagnostics::get_nested($config, 'performance', 'search_enhance');
            if (empty($search_enhance['hotwords_enabled'])) {
                $recommendations[] = array(
                    'id' => 'rec_enable_search_log',
                    'title' => __('开启搜索日志', 'magick-toolbox'),
                    'reason' => __('搜索日志已关闭，无法收集搜索健康数据。建议开启以获得搜索分析。', 'magick-toolbox'),
                );
            }

            return $recommendations;
        }
    }
}
