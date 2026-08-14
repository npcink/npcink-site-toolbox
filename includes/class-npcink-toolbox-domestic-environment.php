<?php
defined('ABSPATH') || exit;

if (!class_exists('Npcink_Toolbox_Domestic_Environment')) {
    class Npcink_Toolbox_Domestic_Environment
    {
        private static $checks = array(
            'google_fonts' => array(
                'name'    => 'Google Fonts',
                'url'     => 'https://fonts.googleapis.com/css?family=Roboto',
                'timeout' => 5,
            ),
            'gravatar' => array(
                'name'    => 'Gravatar',
                'url'     => 'https://secure.gravatar.com/avatar/00000000000000000000000000000000?d=mp',
                'timeout' => 5,
            ),
            'google_ajax' => array(
                'name'    => 'Google Ajax',
                'url'     => 'https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js',
                'timeout' => 5,
            ),
            'wordpress_org' => array(
                'name'    => 'WordPress.org API',
                'url'     => 'https://api.wordpress.org/core/version-check/1.7/',
                'timeout' => 5,
            ),
        );

        public static function rest_check(\WP_REST_Request $request)
        {
            $cached = get_transient('npcink_site_toolbox_environment_check');
            if ($cached !== false) {
                return rest_ensure_response(array(
                    'success' => true,
                    'data'    => $cached,
                ));
            }

            $results = array();
            foreach (self::$checks as $key => $check) {
                $start = microtime(true);
                $response = wp_remote_get($check['url'], array(
                    'timeout' => $check['timeout'],
                    'headers' => array('Accept' => '*/*'),
                ));
                $latency = round((microtime(true) - $start) * 1000);

                $reachable = !is_wp_error($response)
                    && wp_remote_retrieve_response_code($response) >= 200
                    && wp_remote_retrieve_response_code($response) < 400;

                $suggestion = '';
                if (!$reachable) {
                    $suggestion = self::get_suggestion($key);
                }

                $results[$key] = array(
                    'service'   => $check['name'],
                    'reachable' => $reachable,
                    'latency'   => $reachable ? $latency : -1,
                    'suggestion' => $suggestion,
                );
            }

            set_transient('npcink_site_toolbox_environment_check', $results, HOUR_IN_SECONDS);

            return rest_ensure_response(array(
                'success' => true,
                'data'    => $results,
            ));
        }

        public static function rest_apply(\WP_REST_Request $request)
        {
            $fixes = $request->get_param('fixes');
            if (!is_array($fixes)) {
                return new \WP_Error('rest_invalid_data', __('fixes 参数必须是数组', 'npcink-site-toolbox'), array('status' => 400));
            }

            $allowed_fixes = array('gravatar', 'google_fonts', 'google_ajax');
            $fixes = array_intersect($fixes, $allowed_fixes);

            if (empty($fixes)) {
                return new \WP_Error('rest_invalid_data', __('没有有效的修复项', 'npcink-site-toolbox'), array('status' => 400));
            }

            $current = get_option(NPCINK_SITE_TOOLBOX_OPTION_OPTIMIZE, array());
            if (!is_array($current)) {
                $current = array();
            }
            if (!isset($current['site']) || !is_array($current['site'])) {
                $current['site'] = array();
            }

            $diffs = array();

            if (in_array('gravatar', $fixes)) {
                $diffs[] = array(
                    'key'        => 'cdn_gravatar',
                    'label'      => __('Gravatar 头像替换', 'npcink-site-toolbox'),
                    'before'     => !empty($current['site']['cdn_gravatar']),
                    'after'      => true,
                    'risk_level' => 'none',
                );
                if (empty($current['site']['cdn_gravatar_mirror'])) {
                    $diffs[] = array(
                        'key'        => 'cdn_gravatar_mirror',
                        'label'      => __('Gravatar 镜像地址', 'npcink-site-toolbox'),
                        'before'     => '',
                        'after'      => 'gravatar.loli.net/avatar/',
                        'risk_level' => 'none',
                    );
                }
                $diffs[] = array(
                    'key'        => 'cdn_replace',
                    'label'      => __('CDN 替换总开关', 'npcink-site-toolbox'),
                    'before'     => !empty($current['site']['cdn_replace']),
                    'after'      => true,
                    'risk_level' => 'high',
                );
            }

            if (in_array('google_fonts', $fixes)) {
                $diffs[] = array(
                    'key'        => 'cdn_google_fonts',
                    'label'      => __('Google Fonts 替换', 'npcink-site-toolbox'),
                    'before'     => !empty($current['site']['cdn_google_fonts']),
                    'after'      => true,
                    'risk_level' => 'none',
                );
                if (empty($current['site']['cdn_google_fonts_mirror'])) {
                    $diffs[] = array(
                        'key'        => 'cdn_google_fonts_mirror',
                        'label'      => __('Google Fonts 镜像地址', 'npcink-site-toolbox'),
                        'before'     => '',
                        'after'      => 'fonts.loli.net',
                        'risk_level' => 'none',
                    );
                }
                $diffs[] = array(
                    'key'        => 'cdn_replace',
                    'label'      => __('CDN 替换总开关', 'npcink-site-toolbox'),
                    'before'     => !empty($current['site']['cdn_replace']),
                    'after'      => true,
                    'risk_level' => 'high',
                );
            }

            if (in_array('google_ajax', $fixes)) {
                $diffs[] = array(
                    'key'        => 'cdn_google_ajax',
                    'label'      => __('Google Ajax 替换', 'npcink-site-toolbox'),
                    'before'     => !empty($current['site']['cdn_google_ajax']),
                    'after'      => true,
                    'risk_level' => 'none',
                );
                $diffs[] = array(
                    'key'        => 'cdn_replace',
                    'label'      => __('CDN 替换总开关', 'npcink-site-toolbox'),
                    'before'     => !empty($current['site']['cdn_replace']),
                    'after'      => true,
                    'risk_level' => 'high',
                );
            }

            $proposed = array();
            foreach ($diffs as $d) {
                $proposed[$d['key']] = $d['after'];
            }

            if (class_exists('Npcink_Toolbox_Audit_Logger')) {
                Npcink_Toolbox_Audit_Logger::log('info', 'config', '国内环境修复预览', array(
                    'fixes' => $fixes,
                    'diffs' => $proposed,
                ));
            }

            return rest_ensure_response(array(
                'success' => true,
                'message' => __('已生成建议变更，请确认后保存', 'npcink-site-toolbox'),
                'data'    => array(
                    'fixes'    => $fixes,
                    'diffs'    => $diffs,
                    'proposed' => $proposed,
                ),
            ));
        }

        private static function get_suggestion($key)
        {
            $suggestions = array(
                'google_fonts'  => __('建议开启 Google Fonts CDN 替换，使用国内镜像', 'npcink-site-toolbox'),
                'gravatar'      => __('建议开启 Gravatar 头像替换，使用国内镜像', 'npcink-site-toolbox'),
                'google_ajax'   => __('建议开启 Google Ajax CDN 替换', 'npcink-site-toolbox'),
                'wordpress_org' => __('WordPress.org API 不可达，部分后台功能可能受影响；建议检查服务器网络或安全插件设置', 'npcink-site-toolbox'),
            );
            return isset($suggestions[$key]) ? $suggestions[$key] : '';
        }

        public static function get_environment_status()
        {
            $config = Npcink_Toolbox_Config_Manager::get_merged_config();
            $optimize_site = isset($config['optimize']['site']) ? $config['optimize']['site'] : array();

            $items = array(
                'gravatar_replaced'     => !empty($optimize_site['cdn_gravatar']) && $optimize_site['cdn_gravatar'] !== 'false',
                'google_fonts_replaced' => !empty($optimize_site['cdn_google_fonts']) && $optimize_site['cdn_google_fonts'] !== 'false',
                'google_ajax_replaced'  => !empty($optimize_site['cdn_google_ajax']) && $optimize_site['cdn_google_ajax'] !== 'false',
            );

            $replaced_count = count(array_filter($items));
            $total_count = count($items);

            return array(
                'items'         => $items,
                'replaced'      => $replaced_count,
                'total'         => $total_count,
                'all_replaced'  => $replaced_count === $total_count,
                'none_replaced' => $replaced_count === 0,
            );
        }
    }
}
