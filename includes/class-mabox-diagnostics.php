<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/**
 * 站点诊断聚合层
 *
 * 聚合现有配置、模块分层、激活模块、WordPress 环境信息，
 * 生成统一的 DiagnosticSummary，供前端体检中心消费。
 *
 * 不新增持久化 option，所有数据实时计算。
 *
 * @since 2.5.0
 */
if (!class_exists('MaBox_Diagnostics')) {
    class MaBox_Diagnostics
    {
        /**
         * 获取诊断摘要
         *
         * @return array DiagnosticSummary
         */
        public static function get_summary()
        {
            $config = MaBox_Config_Manager::get_merged_config();
            if (empty($config)) {
                $config = array();
            }

            $env = self::get_environment();
            $active_modules = get_option(MAGICK_TOOLBOX_ACTIVE_MODULES, array());
            $tiers = class_exists('MaBox_Module_Loader') ? MaBox_Module_Loader::get_tiers() : array();

            $score = self::calculate_score($config, $env);
            $items = self::get_diagnostic_items($config, $env, $active_modules, $tiers);
            $recommendations = self::get_recommendations($config);
            $risks = self::get_risks($config, $active_modules, $tiers);
            $service_hints = self::get_service_hints($items, $risks, $env);
            $fix_suggestions = self::get_fix_suggestions($config);
            $status = self::determine_status($score, $risks, $items);

            return array(
                'score'           => max(0, min(100, $score)),
                'status'          => $status,
                'items'           => $items,
                'recommendations' => $recommendations,
                'risks'           => $risks,
                'service_hints'   => $service_hints,
                'generated_at'    => current_time('mysql'),
                'environment'     => $env,
                'fix_suggestions' => $fix_suggestions,
            );
        }

        /**
         * 获取环境信息
         *
         * @return array
         */
        private static function get_environment()
        {
            $rest_url = get_rest_url();
            $response = wp_remote_get($rest_url, array(
                'timeout'     => 5,
                'sslverify'   => false,
            ));
            $rest_ok = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;

            return array(
                'php_version'        => PHP_VERSION,
                'wp_version'         => get_bloginfo('version'),
                'plugin_version'     => defined('MAGICK_MIXTURE_VERSION') ? MAGICK_MIXTURE_VERSION : 'unknown',
                'permalink'          => get_option('permalink_structure'),
                'object_cache'       => wp_using_ext_object_cache(),
                'rest_api_available' => $rest_ok,
                'site_url'           => home_url(),
            );
        }

        /**
         * 计算综合健康分
         *
         * 基础分 60，根据配置项和环境加减分。
         *
         * @param array $config
         * @param array $env
         * @return int
         */
        private static function calculate_score($config, $env)
        {
            $score = 60;

            // ===== 加分项（配置） =====
            $seo = self::get_nested($config, 'function', 'seo');
            if (!empty($seo['seo_home'])) {
                $score += 5;
            }
            if (!empty($seo['seo_single'])) {
                $score += 5;
            }
            if (!empty($seo['seo_category'])) {
                $score += 5;
            }

            $login_security = self::get_nested($config, 'login', 'security');
            if (!empty($login_security['login_code']) && $login_security['login_code'] !== 'false' && $login_security['login_code'] !== false) {
                $score += 5;
            }

            $optimize_site = self::get_nested($config, 'optimize', 'site');
            if (!empty($optimize_site['remove_RSS_version'])) {
                $score += 5;
            }
            if (!empty($optimize_site['hide_top_toolbar'])) {
                $score += 3;
            }

            $page_function = self::get_nested($config, 'page', 'function');
            if (!empty($page_function['search_limit'])) {
                $score += 5;
            }

            $optimize_medium = self::get_nested($config, 'optimize', 'medium');
            if (!empty($optimize_medium['img_add_tag'])) {
                $score += 3;
            }
            if (!empty($optimize_medium['upload_auto_name']) && $optimize_medium['upload_auto_name'] !== 'false' && $optimize_medium['upload_auto_name'] !== false) {
                $score += 4;
            }

            // ===== 减分项（配置风险） =====
            $cdn_gravatar = !empty($optimize_site['cdn_gravatar']) && $optimize_site['cdn_gravatar'] !== 'false';
            $cdn_google_fonts = !empty($optimize_site['cdn_google_fonts']) && $optimize_site['cdn_google_fonts'] !== 'false';
            $cdn_google_ajax = !empty($optimize_site['cdn_google_ajax']) && $optimize_site['cdn_google_ajax'] !== 'false';
            $cdn_replaced_count = ($cdn_gravatar ? 1 : 0) + ($cdn_google_fonts ? 1 : 0) + ($cdn_google_ajax ? 1 : 0);
            if ($cdn_replaced_count === 3) {
                $score += 5;
            } elseif ($cdn_replaced_count === 0) {
                $score -= 3;
            }

            // ===== 环境分调整 =====
            if (version_compare($env['php_version'], '7.4', '<')) {
                $score -= 10;
            }

            $main_wp_version = preg_replace('/^(\d+\.\d+).*/', '$1', $env['wp_version']);
            if (version_compare($main_wp_version, '6.0', '<')) {
                $score -= 5;
            }

            if (empty($env['permalink'])) {
                $score -= 5;
            }

            if (empty($env['object_cache'])) {
                $score -= 3;
            }

            if (empty($env['rest_api_available'])) {
                $score -= 10;
            }

            return max(0, min(100, $score));
        }

        /**
         * 生成诊断项列表
         *
         * @param array $config
         * @param array $env
         * @param array $active_modules
         * @param array $tiers
         * @return array
         */
        private static function get_diagnostic_items($config, $env, $active_modules, $tiers)
        {
            $items = array();

            // PHP 版本
            $php_ok = version_compare($env['php_version'], '7.4', '>=');
            $items[] = array(
                'id'      => 'php_version',
                'title'   => __('PHP 版本', 'magick-toolbox'),
                'status'  => $php_ok ? 'good' : 'critical',
                'message' => $php_ok
                    ? sprintf(__('当前 PHP 版本 %s，满足最低要求（7.4+）。', 'magick-toolbox'), $env['php_version'])
                    : sprintf(__('当前 PHP 版本 %s，低于最低要求 7.4。', 'magick-toolbox'), $env['php_version']),
                'action'  => $php_ok ? '' : __('升级 PHP 版本', 'magick-toolbox'),
            );

            // WP 版本
            $main_wp = preg_replace('/^(\d+\.\d+).*/', '$1', $env['wp_version']);
            $wp_ok = version_compare($main_wp, '6.0', '>=');
            $items[] = array(
                'id'      => 'wp_version',
                'title'   => __('WordPress 版本', 'magick-toolbox'),
                'status'  => $wp_ok ? 'good' : 'warning',
                'message' => $wp_ok
                    ? sprintf(__('当前 WordPress 版本 %s。', 'magick-toolbox'), $env['wp_version'])
                    : sprintf(__('当前 WordPress 版本 %s，建议升级至 6.0+。', 'magick-toolbox'), $env['wp_version']),
                'action'  => $wp_ok ? '' : __('检查更新', 'magick-toolbox'),
            );

            // 伪静态
            $permalink_ok = !empty($env['permalink']);
            $items[] = array(
                'id'      => 'permalink',
                'title'   => __('固定链接（伪静态）', 'magick-toolbox'),
                'status'  => $permalink_ok ? 'good' : 'warning',
                'message' => $permalink_ok
                    ? sprintf(__('当前固定链接结构：%s', 'magick-toolbox'), $env['permalink'])
                    : __('当前使用默认固定链接（?p=123），不利于 SEO。', 'magick-toolbox'),
                'action'  => $permalink_ok ? '' : __('设置固定链接', 'magick-toolbox'),
            );

            // 对象缓存
            $items[] = array(
                'id'      => 'object_cache',
                'title'   => __('对象缓存', 'magick-toolbox'),
                'status'  => !empty($env['object_cache']) ? 'good' : 'warning',
                'message' => !empty($env['object_cache'])
                    ? __('已启用外部对象缓存（如 Redis、Memcached）。', 'magick-toolbox')
                    : __('未启用外部对象缓存。高流量站点建议安装对象缓存插件。', 'magick-toolbox'),
                'action'  => !empty($env['object_cache']) ? '' : __('浏览对象缓存插件', 'magick-toolbox'),
            );

            // REST API
            $items[] = array(
                'id'      => 'rest_api',
                'title'   => __('REST API 可用性', 'magick-toolbox'),
                'status'  => !empty($env['rest_api_available']) ? 'good' : 'critical',
                'message' => !empty($env['rest_api_available'])
                    ? __('REST API 响应正常。', 'magick-toolbox')
                    : __('REST API 无法访问，可能导致插件后台功能异常。', 'magick-toolbox'),
                'action'  => !empty($env['rest_api_available']) ? '' : __('检查安全插件或服务器配置', 'magick-toolbox'),
            );

            // 已激活模块数
            $total_modules = class_exists('MaBox_Module_Loader') ? count(MaBox_Module_Loader::get_all_module_ids()) : 0;
            $items[] = array(
                'id'      => 'module_count',
                'title'   => __('已激活模块数', 'magick-toolbox'),
                'status'  => 'good',
                'message' => sprintf(__('当前已激活 %d 个模块，共 %d 个可用模块。', 'magick-toolbox'), count($active_modules), $total_modules),
                'action'  => '',
            );

            // 高风险模块检查
            $high_risk_active = array();
            $experimental_active = array();
            if (!empty($tiers)) {
                if (isset($tiers['high_risk'])) {
                    $high_risk_active = array_intersect($active_modules, $tiers['high_risk']);
                }
                if (isset($tiers['experimental'])) {
                    $experimental_active = array_intersect($active_modules, $tiers['experimental']);
                }
            }
            $total_risky = count($high_risk_active) + count($experimental_active);

            $risk_status = 'good';
            if ($total_risky > 3) {
                $risk_status = 'critical';
            } elseif ($total_risky > 0) {
                $risk_status = 'warning';
            }

            $risk_msg = __('当前未启用任何高风险或实验性模块。', 'magick-toolbox');
            if ($total_risky > 0) {
                $parts = array();
                if (!empty($high_risk_active)) {
                    $parts[] = sprintf(__('高风险 %d 个', 'magick-toolbox'), count($high_risk_active));
                }
                if (!empty($experimental_active)) {
                    $parts[] = sprintf(__('实验性 %d 个', 'magick-toolbox'), count($experimental_active));
                }
                $risk_msg = sprintf(__('已启用 %s，建议谨慎评估。', 'magick-toolbox'), implode('、', $parts));
            }

            $items[] = array(
                'id'      => 'high_risk_modules',
                'title'   => __('高风险模块检查', 'magick-toolbox'),
                'status'  => $risk_status,
                'message' => $risk_msg,
                'action'  => $total_risky > 0 ? __('查看模块分层', 'magick-toolbox') : '',
            );

            // SEO 基础配置
            $seo = self::get_nested($config, 'function', 'seo');
            $seo_basic = !empty($seo['seo_home']) || !empty($seo['seo_single']);
            $items[] = array(
                'id'      => 'seo_basic',
                'title'   => __('SEO 基础配置', 'magick-toolbox'),
                'status'  => $seo_basic ? 'good' : 'warning',
                'message' => $seo_basic
                    ? __('已配置首页或文章页 TDK。', 'magick-toolbox')
                    : __('未配置 SEO 基础项（首页/文章 TDK），建议开启以提升搜索引擎收录。', 'magick-toolbox'),
                'action'  => $seo_basic ? '' : __('去配置 SEO', 'magick-toolbox'),
            );

            // 登录安全
            $login_security = self::get_nested($config, 'login', 'security');
            $login_code_on = !empty($login_security['login_code']) && $login_security['login_code'] !== 'false' && $login_security['login_code'] !== false;
            $items[] = array(
                'id'      => 'login_security',
                'title'   => __('登录安全', 'magick-toolbox'),
                'status'  => $login_code_on ? 'good' : 'warning',
                'message' => $login_code_on
                    ? __('已启用登录验证码，可有效防御暴力破解。', 'magick-toolbox')
                    : __('未启用登录验证码，建议开启以增强后台安全。', 'magick-toolbox'),
                'action'  => $login_code_on ? '' : __('去配置登录安全', 'magick-toolbox'),
            );

            // 媒体优化
            $optimize_medium = self::get_nested($config, 'optimize', 'medium');
            $img_tag_on = !empty($optimize_medium['img_add_tag']);
            $items[] = array(
                'id'      => 'media_optimization',
                'title'   => __('媒体优化', 'magick-toolbox'),
                'status'  => $img_tag_on ? 'good' : 'warning',
                'message' => $img_tag_on
                    ? __('已启用图片 Alt 自动补全，有助于 SEO 和可访问性。', 'magick-toolbox')
                    : __('未启用图片 Alt 自动补全，建议开启以提升图片搜索收录。', 'magick-toolbox'),
                'action'  => $img_tag_on ? '' : __('去配置媒体优化', 'magick-toolbox'),
            );

            // 中国访问适配
            $optimize_site = self::get_nested($config, 'optimize', 'site');
            $cdn_gravatar = !empty($optimize_site['cdn_gravatar']) && $optimize_site['cdn_gravatar'] !== 'false';
            $cdn_google_fonts = !empty($optimize_site['cdn_google_fonts']) && $optimize_site['cdn_google_fonts'] !== 'false';
            $cdn_google_ajax = !empty($optimize_site['cdn_google_ajax']) && $optimize_site['cdn_google_ajax'] !== 'false';
            $cdn_replaced = ($cdn_gravatar ? 1 : 0) + ($cdn_google_fonts ? 1 : 0) + ($cdn_google_ajax ? 1 : 0);
            $cdn_status = 'warning';
            $cdn_msg = '';
            if ($cdn_replaced === 3) {
                $cdn_status = 'good';
                $cdn_msg = __('已全部开启国内 CDN 替换（Gravatar、Google Fonts、Google Ajax）。', 'magick-toolbox');
            } elseif ($cdn_replaced > 0) {
                $cdn_msg = sprintf(__('已开启 %d/3 项国内 CDN 替换，建议补全。', 'magick-toolbox'), $cdn_replaced);
            } else {
                $cdn_msg = __('未开启任何国内 CDN 替换，国内访问可能受影响。', 'magick-toolbox');
            }
            $items[] = array(
                'id'      => 'domestic_environment',
                'title'   => __('中国访问适配', 'magick-toolbox'),
                'status'  => $cdn_status,
                'message' => $cdn_msg,
                'action'  => $cdn_replaced < 3 ? __('去配置国内环境适配', 'magick-toolbox') : '',
            );

            $search_enhance = self::get_nested($config, 'performance', 'search_enhance');
            $search_logging_on = !empty($search_enhance['hotwords_enabled']);
            $items[] = array(
                'id'      => 'search_logging',
                'title'   => __('搜索日志', 'magick-toolbox'),
                'status'  => $search_logging_on ? 'good' : 'warning',
                'message' => $search_logging_on
                    ? __('搜索日志已开启，可收集搜索健康数据。', 'magick-toolbox')
                    : __('搜索日志已关闭，无法收集搜索健康数据，建议开启。', 'magick-toolbox'),
                'action'  => $search_logging_on ? '' : __('去开启搜索增强', 'magick-toolbox'),
            );

            $search_health = MaBox_Search_Health::get_summary(30);
            if ($search_health['total_searches'] > 0) {
                $no_result_total = 0;
                foreach ($search_health['no_result_terms'] as $term) {
                    $no_result_total += $term['no_result_count'];
                }
                $no_result_ratio = $no_result_total / $search_health['total_searches'];
                $nr_status = $no_result_ratio > 0.5 ? 'warning' : 'good';
                $items[] = array(
                    'id'      => 'search_no_result_ratio',
                    'title'   => __('搜索无结果比例', 'magick-toolbox'),
                    'status'  => $nr_status,
                    'message' => $nr_status === 'warning'
                        ? sprintf(__('近 30 天 %.0f%% 的搜索无结果，建议补充相关内容。', 'magick-toolbox'), $no_result_ratio * 100)
                        : sprintf(__('近 30 天 %.0f%% 的搜索无结果，比例正常。', 'magick-toolbox'), $no_result_ratio * 100),
                    'action'  => $nr_status === 'warning' ? __('查看无结果搜索词', 'magick-toolbox') : '',
                );
            }

            $page_function = self::get_nested($config, 'page', 'function');
            $search_limit_on = !empty($page_function['search_limit']);
            $items[] = array(
                'id'      => 'search_rate_limit',
                'title'   => __('搜索频次限制', 'magick-toolbox'),
                'status'  => $search_limit_on ? 'good' : 'warning',
                'message' => $search_limit_on
                    ? __('已启用搜索频次限制，可防御恶意搜索。', 'magick-toolbox')
                    : __('未启用搜索频次限制，可能被恶意搜索消耗资源。', 'magick-toolbox'),
                'action'  => $search_limit_on ? '' : __('去开启搜索频次限制', 'magick-toolbox'),
            );

            return $items;
        }

        /**
         * 获取建议开启/配置项
         *
         * @param array $config
         * @return array
         */
        private static function get_recommendations($config)
        {
            $recommendations = array();

            $optimize_site = self::get_nested($config, 'optimize', 'site');
            if (empty($optimize_site['remove_RSS_version'])) {
                $recommendations[] = array(
                    'id'     => 'rec_remove_wp_version',
                    'title'  => __('移除 WP 版本号', 'magick-toolbox'),
                    'module' => 'optimize',
                    'field'  => 'site.remove_RSS_version',
                    'reason' => __('减少信息泄露，提升安全性。', 'magick-toolbox'),
                );
            }

            $page_function = self::get_nested($config, 'page', 'function');
            if (empty($page_function['search_limit'])) {
                $recommendations[] = array(
                    'id'     => 'rec_search_limit',
                    'title'  => __('限制搜索频次', 'magick-toolbox'),
                    'module' => 'page',
                    'field'  => 'function.search_limit',
                    'reason' => __('防止恶意搜索消耗服务器资源。', 'magick-toolbox'),
                );
            }

            $optimize_medium = self::get_nested($config, 'optimize', 'medium');
            if (empty($optimize_medium['img_add_tag'])) {
                $recommendations[] = array(
                    'id'     => 'rec_img_alt',
                    'title'  => __('图片 Alt 自动补全', 'magick-toolbox'),
                    'module' => 'optimize',
                    'field'  => 'medium.img_add_tag',
                    'reason' => __('提升图片 SEO 和可访问性。', 'magick-toolbox'),
                );
            }

            $seo = self::get_nested($config, 'function', 'seo');
            if (empty($seo['seo_home'])) {
                $recommendations[] = array(
                    'id'     => 'rec_seo_home',
                    'title'  => __('首页 TDK', 'magick-toolbox'),
                    'module' => 'function',
                    'field'  => 'seo.seo_home',
                    'reason' => __('首页标题/描述/关键词是 SEO 基础。', 'magick-toolbox'),
                );
            }

            $login_security = self::get_nested($config, 'login', 'security');
            if (empty($login_security['login_code']) || $login_security['login_code'] === 'false' || $login_security['login_code'] === false) {
                $recommendations[] = array(
                    'id'     => 'rec_login_code',
                    'title'  => __('登录验证码', 'magick-toolbox'),
                    'module' => 'login',
                    'field'  => 'security.login_code',
                    'reason' => __('防御暴力破解登录后台。', 'magick-toolbox'),
                );
            }

            if (empty($optimize_site['hide_top_toolbar'])) {
                $recommendations[] = array(
                    'id'     => 'rec_hide_toolbar',
                    'title'  => __('隐藏顶部工具条', 'magick-toolbox'),
                    'module' => 'optimize',
                    'field'  => 'site.hide_top_toolbar',
                    'reason' => __('前台访客不显示 WP 管理工具条，提升体验。', 'magick-toolbox'),
                );
            }

            $search_enhance = self::get_nested($config, 'performance', 'search_enhance');
            if (empty($search_enhance['hotwords_enabled'])) {
                $recommendations[] = array(
                    'id'     => 'rec_search_logging',
                    'title'  => __('开启搜索日志', 'magick-toolbox'),
                    'module' => 'performance',
                    'field'  => 'search_enhance.hotwords_enabled',
                    'reason' => __('搜索日志已关闭，无法收集搜索健康数据。', 'magick-toolbox'),
                );
            }

            return $recommendations;
        }

        /**
         * 获取可一键修复的建议项
         *
         * 每项包含具体的配置变更 diff，前端可预览并应用到当前配置。
         * 只覆盖确定性高、低风险的开关项。
         *
         * @param array $config
         * @return array
         */
        private static function get_fix_suggestions($config)
        {
            $suggestions = array();

            $optimize_site = self::get_nested($config, 'optimize', 'site');
            if (empty($optimize_site['remove_RSS_version'])) {
                $suggestions[] = array(
                    'id'                   => 'fix_remove_wp_version',
                    'title'                => __('移除 WP 版本号', 'magick-toolbox'),
                    'reason'               => __('减少信息泄露，提升安全性。', 'magick-toolbox'),
                    'severity'             => 'low',
                    'module'               => 'optimize',
                    'requires_confirmation' => false,
                    'changes'              => array(
                        array(
                            'path'       => 'optimize.site.remove_RSS_version',
                            'label'      => __('移除 WP 版本号', 'magick-toolbox'),
                            'before'     => false,
                            'after'      => true,
                            'risk_level' => 'low',
                        ),
                    ),
                );
            }

            $page_function = self::get_nested($config, 'page', 'function');
            if (empty($page_function['search_limit'])) {
                $suggestions[] = array(
                    'id'                   => 'fix_search_limit',
                    'title'                => __('限制搜索频次', 'magick-toolbox'),
                    'reason'               => __('防止恶意搜索消耗服务器资源。', 'magick-toolbox'),
                    'severity'             => 'low',
                    'module'               => 'page',
                    'requires_confirmation' => false,
                    'changes'              => array(
                        array(
                            'path'       => 'page.function.search_limit',
                            'label'      => __('搜索频次限制', 'magick-toolbox'),
                            'before'     => false,
                            'after'      => true,
                            'risk_level' => 'low',
                        ),
                    ),
                );
            }

            $optimize_medium = self::get_nested($config, 'optimize', 'medium');
            if (empty($optimize_medium['img_add_tag'])) {
                $suggestions[] = array(
                    'id'                   => 'fix_img_alt',
                    'title'                => __('图片 Alt 自动补全', 'magick-toolbox'),
                    'reason'               => __('提升图片 SEO 和可访问性。', 'magick-toolbox'),
                    'severity'             => 'low',
                    'module'               => 'optimize',
                    'requires_confirmation' => false,
                    'changes'              => array(
                        array(
                            'path'       => 'optimize.medium.img_add_tag',
                            'label'      => __('图片 Alt 自动补全', 'magick-toolbox'),
                            'before'     => false,
                            'after'      => true,
                            'risk_level' => 'low',
                        ),
                    ),
                );
            }

            $seo = self::get_nested($config, 'function', 'seo');
            if (empty($seo['seo_home'])) {
                $suggestions[] = array(
                    'id'                   => 'fix_seo_home',
                    'title'                => __('首页 TDK', 'magick-toolbox'),
                    'reason'               => __('首页标题/描述/关键词是 SEO 基础。', 'magick-toolbox'),
                    'severity'             => 'low',
                    'module'               => 'function',
                    'requires_confirmation' => false,
                    'changes'              => array(
                        array(
                            'path'       => 'function.seo.seo_home',
                            'label'      => __('首页 TDK 开关', 'magick-toolbox'),
                            'before'     => false,
                            'after'      => true,
                            'risk_level' => 'low',
                        ),
                    ),
                );
            }

            $login_security = self::get_nested($config, 'login', 'security');
            if (empty($login_security['login_code']) || $login_security['login_code'] === 'false' || $login_security['login_code'] === false) {
                $suggestions[] = array(
                    'id'                   => 'fix_login_code',
                    'title'                => __('登录验证码', 'magick-toolbox'),
                    'reason'               => __('防御暴力破解登录后台。', 'magick-toolbox'),
                    'severity'             => 'low',
                    'module'               => 'login',
                    'requires_confirmation' => false,
                    'changes'              => array(
                        array(
                            'path'       => 'login.security.login_code',
                            'label'      => __('登录验证码', 'magick-toolbox'),
                            'before'     => isset($login_security['login_code']) ? $login_security['login_code'] : 'false',
                            'after'      => 'math',
                            'risk_level' => 'low',
                        ),
                    ),
                );
            }

            if (empty($optimize_site['hide_top_toolbar'])) {
                $suggestions[] = array(
                    'id'                   => 'fix_hide_toolbar',
                    'title'                => __('隐藏顶部工具条', 'magick-toolbox'),
                    'reason'               => __('前台访客不显示 WP 管理工具条，提升体验。', 'magick-toolbox'),
                    'severity'             => 'low',
                    'module'               => 'optimize',
                    'requires_confirmation' => false,
                    'changes'              => array(
                        array(
                            'path'       => 'optimize.site.hide_top_toolbar',
                            'label'      => __('隐藏顶部工具条', 'magick-toolbox'),
                            'before'     => false,
                            'after'      => true,
                            'risk_level' => 'low',
                        ),
                    ),
                );
            }

            $search_enhance = self::get_nested($config, 'performance', 'search_enhance');
            if (empty($search_enhance['hotwords_enabled'])) {
                $suggestions[] = array(
                    'id'                   => 'fix_search_logging',
                    'title'                => __('开启搜索日志', 'magick-toolbox'),
                    'reason'               => __('搜索日志已关闭，无法收集搜索健康数据。', 'magick-toolbox'),
                    'severity'             => 'low',
                    'module'               => 'performance',
                    'requires_confirmation' => false,
                    'changes'              => array(
                        array(
                            'path'       => 'performance.search_enhance.hotwords_enabled',
                            'label'      => __('搜索日志', 'magick-toolbox'),
                            'before'     => false,
                            'after'      => true,
                            'risk_level' => 'low',
                        ),
                    ),
                );
            }

            return $suggestions;
        }

        /**
         * 获取风险项
         *
         * @param array $config
         * @param array $active_modules
         * @param array $tiers
         * @return array
         */
        private static function get_risks($config, $active_modules, $tiers)
        {
            $risks = array();

            // 模块分层层面的高风险/实验性模块
            if (!empty($tiers) && !empty($active_modules)) {
                $registry = class_exists('MaBox_Module_Loader') ? MaBox_Module_Loader::get_registry() : array();

                if (isset($tiers['high_risk'])) {
                    foreach (array_intersect($active_modules, $tiers['high_risk']) as $module_id) {
                        $meta = isset($registry[$module_id]) ? $registry[$module_id] : null;
                        $risks[] = array(
                            'module_id' => $module_id,
                            'tier'      => 'high_risk',
                            'title'     => $meta && !empty($meta['name']) ? $meta['name'] : $module_id,
                            'message'   => __('该模块被标记为高风险，可能影响站点稳定性。', 'magick-toolbox'),
                        );
                    }
                }

                if (isset($tiers['experimental'])) {
                    foreach (array_intersect($active_modules, $tiers['experimental']) as $module_id) {
                        $meta = isset($registry[$module_id]) ? $registry[$module_id] : null;
                        $risks[] = array(
                            'module_id' => $module_id,
                            'tier'      => 'experimental',
                            'title'     => $meta && !empty($meta['name']) ? $meta['name'] : $module_id,
                            'message'   => __('该模块为实验性功能，不建议在生产环境长期开启。', 'magick-toolbox'),
                        );
                    }
                }
            }

            return $risks;
        }

        /**
         * 获取服务/技术支持提示
         *
         * 只在存在 critical 或需要人工处理的问题时生成提示。
         *
         * @param array $items
         * @param array $risks
         * @param array $env
         * @return array
         */
        private static function get_service_hints($items, $risks, $env)
        {
            $hints = array();

            $has_critical = false;
            foreach ($items as $item) {
                if ($item['status'] === 'critical') {
                    $has_critical = true;
                    break;
                }
            }

            if ($has_critical) {
                $hints[] = array(
                    'type'    => 'critical_environment',
                    'message' => __('检测到关键环境风险（PHP 版本过低或 REST API 不可用），建议联系技术支持排查。', 'magick-toolbox'),
                );
            }

            $high_risk_count = 0;
            foreach ($risks as $risk) {
                if ($risk['tier'] === 'high_risk') {
                    $high_risk_count++;
                }
            }

            if ($high_risk_count > 0) {
                $hints[] = array(
                    'type'    => 'high_risk_modules',
                    'message' => sprintf(__('已启用 %d 个高风险模块，如需稳定性评估可联系技术支持。', 'magick-toolbox'), $high_risk_count),
                );
            }

            if (empty($env['object_cache'])) {
                $hints[] = array(
                    'type'    => 'performance_optimization',
                    'message' => __('未启用对象缓存，高流量站点建议由技术支持协助部署 Redis/Memcached。', 'magick-toolbox'),
                );
            }

            return $hints;
        }

        /**
         * 确定总体状态
         *
         * @param int   $score
         * @param array $risks
         * @param array $items
         * @return string good|warning|critical
         */
        private static function determine_status($score, $risks, $items)
        {
            // 有任何 critical 项直接判定 critical
            foreach ($items as $item) {
                if ($item['status'] === 'critical') {
                    return 'critical';
                }
            }

            // 有高风险模块且分数低
            $has_high_risk = false;
            foreach ($risks as $risk) {
                if ($risk['tier'] === 'high_risk') {
                    $has_high_risk = true;
                    break;
                }
            }

            if ($score < 60 || $has_high_risk) {
                return 'warning';
            }

            if ($score >= 80) {
                return 'good';
            }

            return 'warning';
        }

        /**
         * 安全获取嵌套数组值
         *
         * @param array  $data
         * @param string ...$keys
         * @return array|null
         */
        public static function get_nested($data, ...$keys)
        {
            $current = $data;
            foreach ($keys as $key) {
                if (is_array($current) && isset($current[$key])) {
                    $current = $current[$key];
                } else {
                    return null;
                }
            }
            return $current;
        }

    }
}
