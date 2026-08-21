<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;
/**
 * WordPress 站点健康检测集成
 *
 * 在 WordPress 后台「工具 > 站点健康」中添加插件专属检测项。
 * 检测 PHP 版本、WP 版本、REST API、伪静态、对象缓存等。
 *
 * @since 2.4.0
 */
if (!class_exists('Npcink_Toolbox_Site_Health')) {
    class Npcink_Toolbox_Site_Health
    {
        /**
         * 注册站点健康检测项
         */
        public static function run()
        {
            add_filter('site_status_tests', array(__CLASS__, 'add_tests'));
            add_filter('site_status_test_result', array(__CLASS__, 'filter_result'));
        }

        /**
         * 添加自定义检测项
         */
        public static function add_tests($tests)
        {
            // 直接检测（不依赖 REST API）
            $tests['direct']['npcink_site_toolbox_php_version'] = array(
                'label' => __('PHP 版本', 'npcink-site-toolbox'),
                'test'  => array(__CLASS__, 'test_php_version'),
            );

            $tests['direct']['npcink_site_toolbox_wp_version'] = array(
                'label' => __('WordPress 版本', 'npcink-site-toolbox'),
                'test'  => array(__CLASS__, 'test_wp_version'),
            );

            $tests['direct']['npcink_site_toolbox_permalink'] = array(
                'label' => __('伪静态（固定链接）', 'npcink-site-toolbox'),
                'test'  => array(__CLASS__, 'test_permalink'),
            );

            $tests['direct']['npcink_site_toolbox_object_cache'] = array(
                'label' => __('对象缓存', 'npcink-site-toolbox'),
                'test'  => array(__CLASS__, 'test_object_cache'),
            );

            $tests['direct']['npcink_site_toolbox_rest_api'] = array(
                'label' => __('REST API 可用性', 'npcink-site-toolbox'),
                'test'  => array(__CLASS__, 'test_rest_api'),
            );

            $tests['direct']['npcink_site_toolbox_module_count'] = array(
                'label' => __('已激活模块数', 'npcink-site-toolbox'),
                'test'  => array(__CLASS__, 'test_module_count'),
            );

            $tests['direct']['npcink_site_toolbox_high_risk_modules'] = array(
                'label' => __('高风险模块检查', 'npcink-site-toolbox'),
                'test'  => array(__CLASS__, 'test_high_risk_modules'),
            );

            return $tests;
        }

        /**
         * 过滤检测结果（可选）
         */
        public static function filter_result($result)
        {
            return $result;
        }

        /**
         * 检测 PHP 版本
         */
        public static function test_php_version()
        {
            $recommended = '7.4';
            $current = PHP_VERSION;
            $is_ok = version_compare($current, $recommended, '>=');

            $result = array(
                'label'       => $is_ok
                    ? __('PHP 版本符合要求', 'npcink-site-toolbox')
                    : __('PHP 版本过低', 'npcink-site-toolbox'),
                'status'      => $is_ok ? 'good' : 'critical',
                'badge'       => array(
                    'label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
                    'color' => 'blue',
                ),
                'description' => sprintf(
                    '<p>%s</p>',
                    $is_ok
                        ? sprintf(
                            /* translators: 1: Current PHP version, 2: Minimum required PHP version. */
                            __('当前 PHP 版本 %1$s，满足最低要求（%2$s+）。', 'npcink-site-toolbox'),
                            $current,
                            $recommended
                        )
                        : sprintf(
                            /* translators: 1: Current PHP version, 2: Minimum required PHP version. */
                            __('当前 PHP 版本 %1$s，低于最低要求 %2$s。部分功能可能无法正常工作。', 'npcink-site-toolbox'),
                            $current,
                            $recommended
                        )
                ),
                'actions'     => sprintf(
                    '<p><a href="%s" target="_blank">%s</a></p>',
                    'https://wordpress.org/support/update-php/',
                    __('了解如何升级 PHP 版本', 'npcink-site-toolbox')
                ),
                'test'        => 'npcink_site_toolbox_php_version',
            );

            return $result;
        }

        /**
         * 检测 WordPress 版本
         */
        public static function test_wp_version()
        {
            $recommended = '6.0';
            $current = get_bloginfo('version');
            // 提取主版本号
            $main_version = preg_replace('/^(\d+\.\d+).*/', '$1', $current);
            $is_ok = version_compare($main_version, $recommended, '>=');

            $result = array(
                'label'       => $is_ok
                    ? __('WordPress 版本符合要求', 'npcink-site-toolbox')
                    : __('WordPress 版本过低', 'npcink-site-toolbox'),
                'status'      => $is_ok ? 'good' : 'recommended',
                'badge'       => array(
                    'label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
                    'color' => 'blue',
                ),
                'description' => sprintf(
                    '<p>%s</p>',
                    $is_ok
                        ? sprintf(
                            /* translators: 1: Current WordPress version, 2: Recommended WordPress version. */
                            __('当前 WordPress 版本 %1$s，建议使用 %2$s+ 以获得最佳体验。', 'npcink-site-toolbox'),
                            $current,
                            $recommended
                        )
                        : sprintf(
                            /* translators: 1: Current WordPress version, 2: Recommended WordPress version. */
                            __('当前 WordPress 版本 %1$s，建议升级至 %2$s+。', 'npcink-site-toolbox'),
                            $current,
                            $recommended
                        )
                ),
                'actions'     => sprintf(
                    '<p><a href="%s">%s</a></p>',
                    admin_url('update-core.php'),
                    __('检查更新', 'npcink-site-toolbox')
                ),
                'test'        => 'npcink_site_toolbox_wp_version',
            );

            return $result;
        }

        /**
         * 检测伪静态（固定链接）
         */
        public static function test_permalink()
        {
            $permalink_structure = get_option('permalink_structure');
            $is_ok = !empty($permalink_structure);

            $result = array(
                'label'       => $is_ok
                    ? __('伪静态已启用', 'npcink-site-toolbox')
                    : __('伪静态未启用', 'npcink-site-toolbox'),
                'status'      => $is_ok ? 'good' : 'recommended',
                'badge'       => array(
                    'label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
                    'color' => 'blue',
                ),
                'description' => sprintf(
                    '<p>%s</p>',
                    $is_ok
                        ? sprintf(
                            /* translators: %s: Current permalink structure. */
                            __('当前固定链接结构：%s', 'npcink-site-toolbox'),
                            esc_html($permalink_structure)
                        )
                        : __('当前使用默认固定链接（?p=123），不利于 SEO 和部分插件功能。', 'npcink-site-toolbox')
                ),
                'actions'     => sprintf(
                    '<p><a href="%s">%s</a></p>',
                    admin_url('options-permalink.php'),
                    __('设置固定链接', 'npcink-site-toolbox')
                ),
                'test'        => 'npcink_site_toolbox_permalink',
            );

            return $result;
        }

        /**
         * 检测对象缓存
         */
        public static function test_object_cache()
        {
            $is_ok = wp_using_ext_object_cache();

            $result = array(
                'label'       => $is_ok
                    ? __('对象缓存已启用', 'npcink-site-toolbox')
                    : __('对象缓存未启用', 'npcink-site-toolbox'),
                'status'      => $is_ok ? 'good' : 'recommended',
                'badge'       => array(
                    'label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
                    'color' => 'blue',
                ),
                'description' => sprintf(
                    '<p>%s</p>',
                    $is_ok
                        ? __('已启用外部对象缓存（如 Redis、Memcached），有助于提升性能。', 'npcink-site-toolbox')
                        : __('未启用外部对象缓存。对于高流量站点，建议安装 Redis 或 Memcached 对象缓存插件。', 'npcink-site-toolbox')
                ),
                'actions'     => $is_ok ? '' : sprintf(
                    '<p><a href="%s" target="_blank">%s</a></p>',
                    'https://wordpress.org/plugins/search/object+cache/',
                    __('浏览对象缓存插件', 'npcink-site-toolbox')
                ),
                'test'        => 'npcink_site_toolbox_object_cache',
            );

            return $result;
        }

        /**
         * 检测 REST API 可用性
         */
        public static function test_rest_api()
        {
            $rest_url = get_rest_url();
            $response = wp_remote_get($rest_url, array(
                'timeout' => 5,
                'sslverify' => false,
            ));

            $is_ok = !is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200;

            $result = array(
                'label'       => $is_ok
                    ? __('REST API 可用', 'npcink-site-toolbox')
                    : __('REST API 不可用', 'npcink-site-toolbox'),
                'status'      => $is_ok ? 'good' : 'critical',
                'badge'       => array(
                    'label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
                    'color' => 'blue',
                ),
                'description' => sprintf(
                    '<p>%s</p>',
                    $is_ok
                        ? sprintf(
                            /* translators: %s: REST API endpoint URL. */
                            __('REST API 端点 %s 响应正常。', 'npcink-site-toolbox'),
                            esc_html($rest_url)
                        )
                        : __('REST API 无法访问，可能导致插件后台功能异常。', 'npcink-site-toolbox')
                ),
                'actions'     => $is_ok ? '' : sprintf(
                    '<p>%s</p>',
                    __('请检查是否有安全插件或服务器配置阻止了 REST API 访问。', 'npcink-site-toolbox')
                ),
                'test'        => 'npcink_site_toolbox_rest_api',
            );

            return $result;
        }

        /**
         * 检测已激活模块数
         */
        public static function test_module_count()
        {
            if (!class_exists('Npcink_Toolbox_Module_Loader')) {
                return array(
                    'label'  => __('模块检测不可用', 'npcink-site-toolbox'),
                    'status' => 'recommended',
                    'badge'  => array(
                        'label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
                        'color' => 'blue',
                    ),
                    'description' => sprintf(
                        '<p>%s</p>',
                        __('模块加载器未初始化。', 'npcink-site-toolbox')
                    ),
                    'test'   => 'npcink_site_toolbox_module_count',
                );
            }

            $active = get_option(NPCINK_SITE_TOOLBOX_ACTIVE_MODULES, array());
            $count = count($active);
            $total = count(Npcink_Toolbox_Module_Loader::get_all_module_ids());

            $result = array(
                'label'       => sprintf(
                    /* translators: 1: Number of active modules, 2: Total number of available modules. */
                    __('已激活 %1$d / %2$d 个模块', 'npcink-site-toolbox'),
                    $count,
                    $total
                ),
                'status'      => 'good',
                'badge'       => array(
                    'label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
                    'color' => 'blue',
                ),
                'description' => sprintf(
                    '<p>%s</p>',
                    sprintf(
                        /* translators: 1: Number of active modules, 2: Total number of available modules. */
                        __('当前已激活 %1$d 个模块，共 %2$d 个可用模块。按需加载机制有助于减少不必要的资源消耗。', 'npcink-site-toolbox'),
                        $count,
                        $total
                    )
                ),
                'test'        => 'npcink_site_toolbox_module_count',
            );

            return $result;
        }

        /**
         * 检测高风险模块
         */
        public static function test_high_risk_modules()
        {
            if (!class_exists('Npcink_Toolbox_Module_Loader')) {
                return array(
                    'label'  => __('模块检测不可用', 'npcink-site-toolbox'),
                    'status' => 'recommended',
                    'badge'  => array(
                        'label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
                        'color' => 'blue',
                    ),
                    'description' => sprintf(
                        '<p>%s</p>',
                        __('模块加载器未初始化。', 'npcink-site-toolbox')
                    ),
                    'test'   => 'npcink_site_toolbox_high_risk_modules',
                );
            }

            $active = get_option(NPCINK_SITE_TOOLBOX_ACTIVE_MODULES, array());
            $tiers = Npcink_Toolbox_Module_Loader::get_tiers();
            $high_risk_active = array();

            if (isset($tiers['high_risk'])) {
                $high_risk_active = array_intersect($active, $tiers['high_risk']);
            }

            $experimental_active = array();
            if (isset($tiers['experimental'])) {
                $experimental_active = array_intersect($active, $tiers['experimental']);
            }

            $total_risky = count($high_risk_active) + count($experimental_active);

            if ($total_risky === 0) {
                return array(
                    'label'       => __('未启用高风险模块', 'npcink-site-toolbox'),
                    'status'      => 'good',
                    'badge'       => array(
                        'label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
                        'color' => 'blue',
                    ),
                    'description' => sprintf(
                        '<p>%s</p>',
                        __('当前未启用任何高风险或实验性模块，站点运行状态安全。', 'npcink-site-toolbox')
                    ),
                    'test'        => 'npcink_site_toolbox_high_risk_modules',
                );
            }

            $desc = '<p>';
            if (!empty($high_risk_active)) {
                $desc .= sprintf(
                    /* translators: 1: Number of active high-risk modules, 2: Comma-separated high-risk module IDs. */
                    __('<strong>高风险模块（%1$d 个）：</strong>%2$s<br>', 'npcink-site-toolbox'),
                    count($high_risk_active),
                    esc_html(implode(', ', $high_risk_active))
                );
            }
            if (!empty($experimental_active)) {
                $desc .= sprintf(
                    /* translators: 1: Number of active experimental modules, 2: Comma-separated experimental module IDs. */
                    __('<strong>实验性模块（%1$d 个）：</strong>%2$s<br>', 'npcink-site-toolbox'),
                    count($experimental_active),
                    esc_html(implode(', ', $experimental_active))
                );
            }
            $desc .= __('这些模块可能影响站点稳定性，建议在测试环境验证后再在生产环境启用。</p>', 'npcink-site-toolbox');

            $result = array(
                'label'       => sprintf(
                    /* translators: %d: Number of active high-risk or experimental modules. */
                    __('已启用 %d 个高风险/实验性模块', 'npcink-site-toolbox'),
                    $total_risky
                ),
                'status'      => $total_risky > 3 ? 'critical' : 'recommended',
                'badge'       => array(
                    'label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
                    'color' => 'blue',
                ),
                'description' => $desc,
                'test'        => 'npcink_site_toolbox_high_risk_modules',
            );

            return $result;
        }
    }
}
