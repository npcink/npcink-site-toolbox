<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../../includes/class-mabox-diagnostics.php';

/**
 * MaBox_Diagnostics 单元测试
 *
 * 验证诊断聚合层的分数计算、状态判定、推荐项、风险统计和服务提示。
 *
 * @since 2.5.0
 */
class DiagnosticsTest extends TestCase {

    /**
     * 测试类存在
     */
    public function test_class_exists(): void {
        $this->assertTrue(class_exists('MaBox_Diagnostics'));
    }

    /**
     * 测试 get_summary 返回标准结构（空配置场景）
     */
    public function test_summary_structure_with_empty_config(): void {
        $this->mockWordPressFunctions(array());

        $summary = MaBox_Diagnostics::get_summary();

        $this->assertIsArray($summary);
        $this->assertArrayHasKey('score', $summary);
        $this->assertArrayHasKey('status', $summary);
        $this->assertArrayHasKey('items', $summary);
        $this->assertArrayHasKey('recommendations', $summary);
        $this->assertArrayHasKey('risks', $summary);
        $this->assertArrayHasKey('service_hints', $summary);

        $this->assertIsInt($summary['score']);
        $this->assertGreaterThanOrEqual(0, $summary['score']);
        $this->assertLessThanOrEqual(100, $summary['score']);

        $this->assertContains($summary['status'], array('good', 'warning', 'critical'));
        $this->assertIsArray($summary['items']);
        $this->assertIsArray($summary['recommendations']);
        $this->assertIsArray($summary['risks']);
        $this->assertIsArray($summary['service_hints']);
    }

    /**
     * 测试分数计算：基础分 + 完整优化配置
     */
    public function test_calculate_score_with_full_optimization(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'calculate_score');

        $config = array(
            'function' => array('seo' => array('seo_home' => true, 'seo_single' => true, 'seo_category' => true)),
            'login'    => array('security' => array('login_code' => true)),
            'optimize' => array(
                'site'   => array('remove_RSS_version' => true, 'hide_top_toolbar' => true),
                'medium' => array('img_add_tag' => true, 'upload_auto_name' => true),
            ),
            'page'     => array('function' => array('search_limit' => true)),
        );

        $env = array(
            'php_version'        => '8.1',
            'wp_version'         => '6.4',
            'permalink'          => '/%postname%/',
            'object_cache'       => true,
            'rest_api_available' => true,
        );

        $score = $method->invoke(null, $config, $env);
        // 基础 60 + 5*3(seo) + 5(login) + 5(remove_RSS) + 3(toolbar) + 5(search) + 3(img_alt) + 4(upload_name) - 3(no CDN) = 97
        $this->assertEquals(97, $score);
    }

    /**
     * 测试分数计算：缺少 CDN 适配减分
     */
    public function test_calculate_score_without_cdn_adaptation(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'calculate_score');

        $config = array();

        $env = array(
            'php_version'        => '8.1',
            'wp_version'         => '6.4',
            'permalink'          => '/%postname%/',
            'object_cache'       => true,
            'rest_api_available' => true,
        );

        $score = $method->invoke(null, $config, $env);
        // 基础 60 - 3(no CDN) = 57
        $this->assertEquals(57, $score);
    }

    /**
     * 测试分数计算：环境风险减分
     */
    public function test_calculate_score_with_environment_risks(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'calculate_score');

        $config = array();
        $env = array(
            'php_version'        => '7.2',
            'wp_version'         => '5.8',
            'permalink'          => '',
            'object_cache'       => false,
            'rest_api_available' => false,
        );

        $score = $method->invoke(null, $config, $env);
        // 基础 60 - 10(php) - 5(wp) - 5(permalink) - 3(cache) - 10(rest) - 3(no CDN) = 24
        $this->assertEquals(24, $score);
    }

    /**
     * 测试状态判定：有 critical 项时为 critical
     */
    public function test_determine_status_critical(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'determine_status');

        $items = array(
            array('status' => 'good'),
            array('status' => 'critical'),
        );

        $status = $method->invoke(null, 85, array(), $items);
        $this->assertEquals('critical', $status);
    }

    /**
     * 测试状态判定：有高风险模块且分数低时为 warning
     */
    public function test_determine_status_warning_with_risk(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'determine_status');

        $risks = array(array('tier' => 'high_risk'));
        $items = array(array('status' => 'good'));

        $status = $method->invoke(null, 85, $risks, $items);
        $this->assertEquals('warning', $status);
    }

    /**
     * 测试状态判定：高分无风险为 good
     */
    public function test_determine_status_good(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'determine_status');

        $status = $method->invoke(null, 85, array(), array(array('status' => 'good')));
        $this->assertEquals('good', $status);
    }

    /**
     * 测试推荐项：空配置时应有多条推荐
     */
    public function test_recommendations_with_empty_config(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'get_recommendations');

        $recommendations = $method->invoke(null, array());
        $this->assertIsArray($recommendations);
        $this->assertGreaterThanOrEqual(4, count($recommendations));
    }

    /**
     * 测试推荐项：完整配置时推荐项为空或很少
     */
    public function test_recommendations_with_full_config(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'get_recommendations');

        $config = array(
            'optimize' => array(
                'site'   => array('remove_RSS_version' => true, 'hide_top_toolbar' => true),
                'medium' => array('img_add_tag' => true),
            ),
            'page'     => array('function' => array('search_limit' => true)),
            'function' => array('seo' => array('seo_home' => true)),
            'login'    => array('security' => array('login_code' => true)),
            'performance' => array('search_enhance' => array('hotwords_enabled' => true)),
        );

        $recommendations = $method->invoke(null, $config);
        $this->assertIsArray($recommendations);
        $this->assertCount(0, $recommendations);
    }

    /**
     * 测试风险项：空配置不产生配置风险
     */
    public function test_risks_empty_for_empty_config(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'get_risks');

        $config = array();

        $risks = $method->invoke(null, $config, array(), array());
        $this->assertIsArray($risks);
        $this->assertEmpty($risks);
    }

    /**
     * 测试风险项：模块分层风险被正确统计
     */
    public function test_risks_include_tier_risks(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'get_risks');

        $config = array();
        $active_modules = array('optimize.ban_update');
        $tiers = array(
            'high_risk'     => array('optimize.ban_update'),
        );

        $risks = $method->invoke(null, $config, $active_modules, $tiers);

        $tiers_list = array_column($risks, 'tier');
        $this->assertContains('high_risk', $tiers_list);
    }

    /**
     * 测试服务提示：critical 项时生成提示
     */
    public function test_service_hints_for_critical_items(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'get_service_hints');

        $items = array(array('status' => 'critical'));
        $hints = $method->invoke(null, $items, array(), array('object_cache' => false));

        $this->assertIsArray($hints);
        $this->assertNotEmpty($hints);

        $types = array_column($hints, 'type');
        $this->assertContains('critical_environment', $types);
    }

    /**
     * 测试服务提示：无 critical 无风险时提示为空
     */
    public function test_service_hints_empty_when_healthy(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'get_service_hints');

        $items = array(array('status' => 'good'));
        $hints = $method->invoke(null, $items, array(), array('object_cache' => true));

        $this->assertIsArray($hints);
        $this->assertEmpty($hints);
    }

    /**
     * 测试分数边界值不越界
     */
    public function test_score_boundaries(): void {
        $method = new ReflectionMethod('MaBox_Diagnostics', 'calculate_score');

        // 全部正向配置 + 完美环境
        $max_config = array(
            'function' => array('seo' => array('seo_home' => true, 'seo_single' => true, 'seo_category' => true)),
            'login'    => array('security' => array('login_code' => true)),
            'optimize' => array(
                'site'   => array('remove_RSS_version' => true, 'hide_top_toolbar' => true),
                'medium' => array('img_add_tag' => true, 'upload_auto_name' => true),
            ),
            'page'     => array('function' => array('search_limit' => true)),
        );
        $max_env = array(
            'php_version'        => '8.2',
            'wp_version'         => '6.5',
            'permalink'          => '/%postname%/',
            'object_cache'       => true,
            'rest_api_available' => true,
        );
        $this->assertLessThanOrEqual(100, $method->invoke(null, $max_config, $max_env));

        // 全部负向配置 + 最差环境
        $min_config = array();
        $min_env = array(
            'php_version'        => '5.6',
            'wp_version'         => '5.0',
            'permalink'          => '',
            'object_cache'       => false,
            'rest_api_available' => false,
        );
        $this->assertGreaterThanOrEqual(0, $method->invoke(null, $min_config, $min_env));
    }

    /**
     * 辅助：Mock WordPress 全局函数
     */
    private function mockWordPressFunctions(array $options): void {
        // 在纯单元测试环境中，这些函数已在 bootstrap.php 中 mock
        // 只需设置全局数据存储
        if (!function_exists('get_bloginfo')) {
            function get_bloginfo($show = '') {
                return '6.4';
            }
        }
        if (!function_exists('wp_remote_get')) {
            function wp_remote_get($url, $args = array()) {
                return array('response' => array('code' => 200));
            }
        }
        if (!function_exists('wp_remote_retrieve_response_code')) {
            function wp_remote_retrieve_response_code($response) {
                return is_array($response) && isset($response['response']['code']) ? $response['response']['code'] : 200;
            }
        }
        if (!function_exists('is_wp_error')) {
            function is_wp_error($thing) {
                return false;
            }
        }
        if (!function_exists('wp_using_ext_object_cache')) {
            function wp_using_ext_object_cache() {
                return false;
            }
        }
        if (!function_exists('home_url')) {
            function home_url($path = '') {
                return 'https://example.com' . $path;
            }
        }
        if (!function_exists('get_rest_url')) {
            function get_rest_url($blog_id = null, $path = '/', $scheme = 'rest') {
                return 'https://example.com/wp-json/';
            }
        }
        if (!function_exists('__')) {
            function __($text, $domain = 'default') {
                return $text;
            }
        }

        $GLOBALS['_test_option_store'] = array_merge(array(
            MAGICK_TOOLBOX_ACTIVE_MODULES => array(),
        ), $options);
    }
}
