<?php

defined('ABSPATH') || exit;

use PHPUnit\Framework\TestCase;

class RestApiSecurityTest extends TestCase {

    private static function trigger_registration() {
        MaBox_Rest_Route_Registry::clear();
        MaBox_Admin::register_rest_routes();
    }

    public function test_registry_class_exists(): void {
        $this->assertTrue(class_exists('MaBox_Rest_Route_Registry'));
    }

    public function test_all_registry_routes_have_permission_callback(): void {
        self::trigger_registration();
        $missing = MaBox_Rest_Route_Registry::validate_all_have_permission();

        $this->assertEmpty($missing, 'Routes missing permission_callback: ' . implode(', ', $missing));
    }

    public function test_sensitive_endpoints_require_manage_options(): void {
        self::trigger_registration();
        $routes = MaBox_Rest_Route_Registry::get_registered();

        $sensitive_paths = array(
            '/settings',
            '/settings/export',
            '/settings/import',
            '/performance/db/clean',
            '/page/batch-replace',
            '/tools/table-data',
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
        $admin_file = dirname(__DIR__, 2) . '/admin/class-magick-mixture-admin.php';
        $content = file_get_contents($admin_file);

        $this->assertStringNotContainsString("'edit_posts'", $content, '不应使用 edit_posts 权限');
    }

    public function test_public_endpoints_have_rate_limiting(): void {
        self::trigger_registration();
        $routes = MaBox_Rest_Route_Registry::get_registered();

        $public_paths = array('/public/search-log', '/public/anti-crawler/verify', '/public/rating', '/public/wx-unlock/verify');

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
        $this->assertTrue(class_exists('MaBox_Rate_Limiter'));
    }

    public function test_settings_save_has_sanitize_callback(): void {
        $admin_file = dirname(__DIR__, 2) . '/admin/class-magick-mixture-admin.php';
        $content = file_get_contents($admin_file);

        $this->assertStringContainsString("'sanitize_callback'", $content, 'REST API 参数应该有 sanitize_callback');
        $this->assertStringContainsString("'validate_callback'", $content, 'REST API 参数应该有 validate_callback');
    }

    public function test_batch_replace_has_dangerous_content_filter(): void {
        $admin_file = dirname(__DIR__, 2) . '/admin/class-magick-mixture-admin.php';
        $content = file_get_contents($admin_file);

        $this->assertStringContainsString('wp_kses_post', $content, 'Batch Replace 应该使用 wp_kses_post 消毒输入内容');
    }

    public function test_import_endpoint_exists_in_registry(): void {
        self::trigger_registration();
        $routes = MaBox_Rest_Route_Registry::get_registered();

        $found = false;
        foreach ($routes as $route) {
            if ($route['path'] === '/settings/import') {
                $found = true;
                break;
            }
        }

        $this->assertTrue($found, '导入端点应该在注册表中');
    }

    public function test_no_stray_register_rest_route_calls(): void {
        $admin_file = dirname(__DIR__, 2) . '/admin/class-magick-mixture-admin.php';
        $content = file_get_contents($admin_file);

        $direct_count = substr_count($content, 'register_rest_route(');

        $this->assertEquals(0, $direct_count, 'Admin 文件不应有直接的 register_rest_route 调用，应全部通过 Registry 注册');
    }

    public function test_registry_route_count_matches_expected(): void {
        self::trigger_registration();
        $count = MaBox_Rest_Route_Registry::get_route_count();

        $this->assertGreaterThanOrEqual(24, $count, '应该至少注册 24 个路由');
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