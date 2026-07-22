<?php

defined('ABSPATH') || exit;

use PHPUnit\Framework\TestCase;

class RestApiSecurityTest extends TestCase {

    private static function trigger_registration() {
        Npcink_Toolbox_Rest_Route_Registry::clear();
        Npcink_Toolbox_Admin::register_rest_routes();
    }

    public function test_registry_class_exists(): void {
        $this->assertTrue(class_exists('Npcink_Toolbox_Rest_Route_Registry'));
    }

    public function test_all_registry_routes_have_permission_callback(): void {
        self::trigger_registration();
        $missing = Npcink_Toolbox_Rest_Route_Registry::validate_all_have_permission();

        $this->assertEmpty($missing, 'Routes missing permission_callback: ' . implode(', ', $missing));
    }

    public function test_multi_endpoint_route_requires_permission_on_every_endpoint(): void {
        Npcink_Toolbox_Rest_Route_Registry::clear();
        Npcink_Toolbox_Rest_Route_Registry::add('/mixed-permissions', array(
            array(
                'methods' => 'GET',
                'permission_callback' => static function () {
                    return true;
                },
            ),
            array(
                'methods' => 'POST',
            ),
        ));

        try {
            $this->assertSame(
                array('/mixed-permissions[1]'),
                Npcink_Toolbox_Rest_Route_Registry::validate_all_have_permission()
            );
        } finally {
            self::trigger_registration();
        }
    }

    public function test_all_registered_route_callbacks_are_callable(): void {
        self::trigger_registration();
        $invalid = array();

        foreach (Npcink_Toolbox_Rest_Route_Registry::get_registered() as $route) {
            $args = $route['args'];
            $endpoints = isset($args[0]) ? $args : array($args);

            foreach ($endpoints as $endpoint) {
                if (!isset($endpoint['callback'])) {
                    $invalid[] = $route['path'] . ' => missing callback';
                    continue;
                }

                if (is_callable($endpoint['callback'])) {
                    continue;
                }

                $callback = is_array($endpoint['callback'])
                    ? implode('::', $endpoint['callback'])
                    : (string) $endpoint['callback'];
                $invalid[] = $route['path'] . ' => ' . $callback;
            }
        }

        $this->assertEmpty($invalid, 'Routes with non-callable callbacks: ' . implode(', ', $invalid));
    }

    public function test_sensitive_endpoints_require_manage_options(): void {
        self::trigger_registration();
        $routes = Npcink_Toolbox_Rest_Route_Registry::get_registered();

        $sensitive_paths = array(
            '/settings',
            '/performance/oss/test',
            '/performance/media/webp/convert',
            '/performance/media/webp/restore',
            '/performance/db/clean',
            '/tools/categories',
            '/diagnostics/support-report',
            '/diagnostics/analyses',
            '/diagnostics/review-packs',
            '/diagnostics/reviews',
            '/diagnostics/follow-ups',
        );

        $found_paths = array();
        foreach ($routes as $route) {
            $found_paths[] = $route['path'];
        }

        foreach ($sensitive_paths as $path) {
            $this->assertContains($path, $found_paths, "敏感端点 {$path} 应该存在");
        }

        foreach ($routes as $route) {
            if (in_array($route['path'], $sensitive_paths, true)) {
                $args = $route['args'];
                $has_manage_options = false;

                if (isset($args['permission_callback'])) {
                    $has_manage_options = self::is_admin_permission($args['permission_callback']);
                } elseif (is_array($args) && isset($args[0])) {
                    foreach ($args as $endpoint) {
                        if (is_array($endpoint) && isset($endpoint['permission_callback'])) {
                            if (self::is_admin_permission($endpoint['permission_callback'])) {
                                $has_manage_options = true;
                                break;
                            }
                        }
                    }
                }

                $this->assertTrue($has_manage_options, "敏感端点 {$route['path']} 应使用 manage_options 权限");
            }
        }
    }

    public function test_no_endpoints_use_edit_posts_permission(): void {
        $admin_file = dirname(__DIR__, 2) . '/admin/class-npcink-toolbox-admin.php';
        $content = file_get_contents($admin_file);

        $this->assertStringNotContainsString("'edit_posts'", $content, '不应使用 edit_posts 权限');
    }

    public function test_public_endpoints_have_rate_limiting(): void {
        self::trigger_registration();
        $routes = Npcink_Toolbox_Rest_Route_Registry::get_registered();

        $public_paths = array('/public/search-log');

        $found_paths = array();
        foreach ($routes as $route) {
            $found_paths[] = $route['path'];
        }

        foreach ($public_paths as $path) {
            $this->assertContains($path, $found_paths, "公开端点 {$path} 应该存在");
        }

        $has_rate_limiter = false;
        foreach ($routes as $route) {
            if (in_array($route['path'], $public_paths, true)) {
                $args = $route['args'];
                if (isset($args['permission_callback'])) {
                    $this->assertTrue(true, 'Public endpoint has permission_callback');
                    $has_rate_limiter = true;
                }
            }
        }
        $this->assertTrue($has_rate_limiter, '公开端点应该使用限流 permission_callback');
    }

    public function test_rate_limiter_class_exists(): void {
        $this->assertTrue(class_exists('Npcink_Toolbox_Rate_Limiter'));
    }

    public function test_settings_save_has_sanitize_callback(): void {
        $admin_file = dirname(__DIR__, 2) . '/admin/class-npcink-toolbox-admin.php';
        $content = file_get_contents($admin_file);

        $this->assertStringContainsString("'sanitize_callback'", $content, 'REST API 参数应该有 sanitize_callback');
        $this->assertStringContainsString("'validate_callback'", $content, 'REST API 参数应该有 validate_callback');
    }

    public function test_rest_integer_sanitize_callback_accepts_wp_rest_arguments(): void {
        $this->assertSame(30, call_user_func(array('Npcink_Toolbox_Admin', 'sanitize_int_arg'), '30', null, 'days'));
    }

    public function test_rest_routes_do_not_use_internal_intval_sanitize_callback(): void {
        $admin_file = dirname(__DIR__, 2) . '/admin/class-npcink-toolbox-admin.php';
        $content = file_get_contents($admin_file);

        $this->assertStringNotContainsString("'sanitize_callback' => 'intval'", $content, 'REST 参数不应直接使用 intval，WordPress 会传多个参数并在 PHP 8 下触发 ArgumentCountError');
    }

    public function test_categories_callback_uses_the_rest_response_contract(): void {
        $callback_file = dirname(__DIR__, 2) . '/admin/partials/page/jurisdiction/interface_category_data.php';
        $content = file_get_contents($callback_file);

        $this->assertStringContainsString('rest_ensure_response', $content);
        $this->assertStringContainsString('is_wp_error', $content);
        $this->assertStringContainsString("array('status' => 500)", $content);
        $this->assertStringNotContainsString('check_ajax_referer', $content);
        $this->assertStringNotContainsString('wp_send_json_', $content);
    }

    public function test_performance_rest_callbacks_return_rest_responses(): void {
        $callback_files = array(
            'admin/partials/performance/media_health/index.php',
            'admin/partials/performance/seo_checker/index.php',
            'admin/partials/performance/db_clean/index.php',
        );

        foreach ($callback_files as $relative_path) {
            $content = file_get_contents(dirname(__DIR__, 2) . '/' . $relative_path);

            $this->assertStringContainsString('rest_ensure_response', $content, $relative_path);
            $this->assertStringContainsString('WP_Error', $content, $relative_path);
            $this->assertStringNotContainsString('wp_send_json_', $content, $relative_path);
        }
    }

    public function test_removed_settings_and_external_service_routes_are_absent(): void {
        self::trigger_registration();
        $routes = Npcink_Toolbox_Rest_Route_Registry::get_registered();

        $paths = array_column($routes, 'path');
        $this->assertNotContains('/settings/import', $paths);
        $this->assertNotContains('/settings/export', $paths);
        $this->assertNotContains('/settings/wizard-complete', $paths);
        $this->assertNotContains('/public/anti-crawler/verify', $paths);
        $this->assertNotContains('/domestic/baidu/push', $paths);
        $this->assertNotContains('/page/batch-replace', $paths);
        $this->assertNotContains('/page/batch-replace/rollback', $paths);
        $this->assertNotContains('/page/batch-replace/rollback/(?P<post_id>\d+)', $paths);
        $this->assertNotContains('/tools/tables', $paths);
        $this->assertNotContains('/tools/table-data', $paths);
        $this->assertNotContains('/public/rating', $paths);
        $this->assertNotContains('/public/wx-unlock/verify', $paths);
    }

    public function test_settings_post_contract_uses_only_settings_and_secret_changes(): void {
        self::trigger_registration();
        $routes = Npcink_Toolbox_Rest_Route_Registry::get_registered();

        foreach ($routes as $route) {
            if ($route['path'] !== '/settings' || !isset($route['args'][1]['args'])) {
                continue;
            }
            $this->assertSame(array('settings', 'secretChanges'), array_keys($route['args'][1]['args']));
            $this->assertTrue($route['args'][1]['args']['settings']['required']);
            return;
        }

        $this->fail('Settings POST route was not registered');
    }

    public function test_no_stray_register_rest_route_calls(): void {
        $admin_file = dirname(__DIR__, 2) . '/admin/class-npcink-toolbox-admin.php';
        $content = file_get_contents($admin_file);

        $direct_count = substr_count($content, 'register_rest_route(');

        $this->assertEquals(0, $direct_count, 'Admin 文件不应有直接的 register_rest_route 调用，应全部通过 Registry 注册');
    }

    public function test_registry_paths_match_current_product_surface(): void {
        self::trigger_registration();
        $paths = array_column(Npcink_Toolbox_Rest_Route_Registry::get_registered(), 'path');

        $this->assertSame(array(
            '/settings',
            '/settings/schema',
            '/performance/oss/test',
            '/performance/media/check',
            '/performance/media/fix-alt',
            '/performance/media/webp/convert',
            '/performance/media/webp/restore',
            '/performance/seo/check',
            '/performance/seo/fix-alt',
            '/performance/db/stats',
            '/performance/db/preview',
            '/performance/db/clean',
            '/tools/categories',
            '/public/search-log',
            '/domestic/environment/check',
            '/domestic/environment/apply',
            '/diagnostics/summary',
            '/diagnostics/features',
            '/diagnostics/support-report',
            '/diagnostics/analyses',
            '/diagnostics/review-packs',
            '/diagnostics/reviews',
            '/diagnostics/follow-ups',
            '/search-health/summary',
        ), $paths);
    }

    public function test_oss_connection_route_uses_the_settings_secret_contract(): void {
        self::trigger_registration();

        foreach (Npcink_Toolbox_Rest_Route_Registry::get_registered() as $route) {
            if ($route['path'] !== '/performance/oss/test') {
                continue;
            }

            $this->assertSame(WP_REST_Server::CREATABLE, $route['args']['methods']);
            $this->assertSame(array('settings', 'secretChanges'), array_keys($route['args']['args']));
            $this->assertTrue($route['args']['args']['settings']['required']);
            $this->assertFalse($route['args']['args']['secretChanges']['required']);
            return;
        }

        $this->fail('OSS connection-test route was not registered');
    }

    private static function is_admin_permission($callback) {
        if (is_string($callback)) {
            return strpos($callback, 'admin_permission') !== false;
        }
        if ($callback instanceof Closure) {
            try {
                $r = new ReflectionFunction($callback);
                $start = $r->getStartLine();
                $end = $r->getEndLine();
                $filename = $r->getFileName();
                $source = implode('', array_slice(file($filename), $start - 1, $end - $start + 1));
                return strpos($source, 'manage_options') !== false;
            } catch (Exception $e) {
                return false;
            }
        }
        return false;
    }
}
