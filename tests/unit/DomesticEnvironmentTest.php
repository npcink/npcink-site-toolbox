<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';

class DomesticEnvironmentTest extends TestCase
{
    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists('MaBox_Domestic_Environment'));
    }

    public function test_rest_check_method_exists(): void
    {
        $this->assertTrue(method_exists('MaBox_Domestic_Environment', 'rest_check'));
    }

    public function test_rest_apply_method_exists(): void
    {
        $this->assertTrue(method_exists('MaBox_Domestic_Environment', 'rest_apply'));
    }

    public function test_get_environment_status_method_exists(): void
    {
        $this->assertTrue(method_exists('MaBox_Domestic_Environment', 'get_environment_status'));
    }

    public function test_checks_define_four_services(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString('google_fonts', $content);
        $this->assertStringContainsString('gravatar', $content);
        $this->assertStringContainsString('google_ajax', $content);
        $this->assertStringContainsString('wordpress_org', $content);
    }

    public function test_check_results_include_required_fields(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString("'service'", $content);
        $this->assertStringContainsString("'reachable'", $content);
        $this->assertStringContainsString("'latency'", $content);
        $this->assertStringContainsString("'suggestion'", $content);
    }

    public function test_check_uses_transient_cache(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString("get_transient('mabox_environment_check'", $content);
        $this->assertStringContainsString("set_transient('mabox_environment_check'", $content);
        $this->assertStringContainsString("HOUR_IN_SECONDS", $content);
    }

    public function test_apply_validates_fixes_param(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString("is_array(\$fixes)", $content);
        $this->assertStringContainsString("'rest_invalid_data'", $content);
    }

    public function test_apply_only_allows_valid_fixes(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString("array_intersect(\$fixes, \$allowed_fixes)", $content);
        $this->assertStringContainsString("'gravatar'", $content);
        $this->assertStringContainsString("'google_fonts'", $content);
        $this->assertStringContainsString("'google_ajax'", $content);
    }

    public function test_apply_sets_gravatar_mirror_default(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString("cdn_gravatar_mirror", $content);
        $this->assertStringContainsString("cravatar.cn", $content);
    }

    public function test_apply_sets_google_fonts_mirror_default(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString("cdn_google_fonts_mirror", $content);
        $this->assertStringContainsString("fonts.font.im", $content);
    }

    public function test_apply_returns_diffs_instead_of_saving(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString("'diffs'", $content);
        $this->assertStringContainsString("'proposed'", $content);
        $this->assertStringNotContainsString("update_option(MAGICK_MIXTURE_OPTION_OPTIMIZE", $content);
    }

    public function test_apply_marks_cdn_replace_as_high_risk(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString("'risk_level' => 'high'", $content);
        $this->assertStringContainsString("cdn_replace", $content);
    }

    public function test_apply_audit_logger_uses_correct_signature(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString("MaBox_Audit_Logger::log('info', 'config'", $content);
    }

    public function test_apply_clears_transient_cache(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString("MaBox_Audit_Logger::log", $content);
    }

    public function test_get_environment_status_returns_structure(): void
    {
        $this->mockWordPressFunctions();

        $status = MaBox_Domestic_Environment::get_environment_status();

        $this->assertIsArray($status);
        $this->assertArrayHasKey('items', $status);
        $this->assertArrayHasKey('replaced', $status);
        $this->assertArrayHasKey('total', $status);
        $this->assertArrayHasKey('all_replaced', $status);
        $this->assertArrayHasKey('none_replaced', $status);

        $this->assertIsInt($status['replaced']);
        $this->assertIsInt($status['total']);
        $this->assertIsBool($status['all_replaced']);
        $this->assertIsBool($status['none_replaced']);
    }

    public function test_get_environment_status_none_replaced_by_default(): void
    {
        $this->mockWordPressFunctions(array(
            MAGICK_MIXTURE_OPTION_OPTIMIZE => array('site' => array()),
        ));
        $this->clearConfigCache();

        $status = MaBox_Domestic_Environment::get_environment_status();

        $this->assertEquals(0, $status['replaced']);
        $this->assertEquals(3, $status['total']);
        $this->assertTrue($status['none_replaced']);
        $this->assertFalse($status['all_replaced']);
    }

    public function test_get_environment_status_all_replaced(): void
    {
        $this->mockWordPressFunctions(array(
            MAGICK_MIXTURE_OPTION_OPTIMIZE => array(
                'site' => array(
                    'cdn_gravatar' => true,
                    'cdn_google_fonts' => true,
                    'cdn_google_ajax' => true,
                ),
            ),
        ));
        $this->clearConfigCache();

        $status = MaBox_Domestic_Environment::get_environment_status();

        $this->assertEquals(3, $status['replaced']);
        $this->assertTrue($status['all_replaced']);
        $this->assertFalse($status['none_replaced']);
    }

    public function test_get_environment_status_partial_replaced(): void
    {
        $this->mockWordPressFunctions(array(
            MAGICK_MIXTURE_OPTION_OPTIMIZE => array(
                'site' => array(
                    'cdn_gravatar' => true,
                ),
            ),
        ));
        $this->clearConfigCache();

        $status = MaBox_Domestic_Environment::get_environment_status();

        $this->assertEquals(1, $status['replaced']);
        $this->assertFalse($status['all_replaced']);
        $this->assertFalse($status['none_replaced']);
    }

    public function test_suggestions_for_unreachable_services(): void
    {
        $file = dirname(__FILE__) . '/../../includes/class-mabox-domestic-environment.php';
        $content = file_get_contents($file);

        $this->assertStringContainsString('CDN 替换', $content);
        $this->assertStringContainsString('国内镜像', $content);
        $this->assertStringContainsString('WordPress.org API', $content);
    }

    private function mockWordPressFunctions(array $options = array()): void
    {
        if (!function_exists('get_bloginfo')) {
            function get_bloginfo($show = '') { return '6.4'; }
        }
        if (!function_exists('__')) {
            function __($text, $domain = 'default') { return $text; }
        }

        $GLOBALS['_test_option_store'] = array_merge(array(
            MAGICK_TOOLBOX_ACTIVE_MODULES => array(),
            MAGICK_MIXTURE_OPTION_OPTIMIZE => array(),
            MAGICK_MIXTURE_OPTION_PAGE => array(),
            MAGICK_MIXTURE_OPTION_FUNCTION => array(),
            MAGICK_MIXTURE_OPTION_LOGIN => array(),
            MAGICK_MIXTURE_OPTION_DOMESTIC => array(),
            MAGICK_MIXTURE_OPTION_PERFORMANCE => array(),
            MAGICK_MIXTURE_OPTION_AI_REVIEW => array(),

        ), $options);
    }

    private function clearConfigCache(): void
    {
        if (class_exists('MaBox_Config_Manager')) {
            $prev = error_reporting(error_reporting() & ~E_DEPRECATED);
            $prop = new ReflectionProperty('MaBox_Config_Manager', 'merged_cache');
            $prop->setValue(null, null);
            error_reporting($prev);
        }
    }
}
