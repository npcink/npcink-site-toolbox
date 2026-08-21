<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/includes/interface-npcink-toolbox-module.php';
require_once dirname(__DIR__, 2) . '/admin/partials/function/auxiliary/census-single.php';

final class MiscOutputComplianceTest extends TestCase
{
    public function test_misc_outputs_use_their_html_context(): void
    {
        $comment_interval = $this->source('page/comment/comment_interval.php');
        $this->assertStringContainsString('wp_die(wp_kses($message, $allowed_html));', $comment_interval);

        $census = $this->source('function/auxiliary/census-single.php');
        $this->assertStringContainsString("esc_html(implode(',', \$options['option_id']))", $census);
        $this->assertStringContainsString('echo esc_html($args[0]);', $census);

        $google = $this->source('function/auxiliary/google_tonji.php');
        $this->assertStringContainsString('esc_attr(self::$option)', $google);

        $malice_search = $this->source('function/auxiliary/ban_malice_search.php');
        $this->assertStringContainsString('wp_die(wp_kses($message, $allowed_html));', $malice_search);

        $admin = file_get_contents(dirname(__DIR__, 2) . '/admin/class-npcink-toolbox-admin.php');
        $this->assertIsString($admin);
        $this->assertStringContainsString('wp_validate_redirect(', $admin);
        $this->assertStringNotContainsString('javascript:void', $admin);
        $this->assertStringNotContainsString('onclick=', $admin);

        $bing = $this->source('function/auxiliary/biying_tonji.php');
        $this->assertStringContainsString('esc_attr(self::$option)', $bing);

        $baidu = $this->source('function/auxiliary/baidu_tonji.php');
        $this->assertStringContainsString("wp_enqueue_script(", $baidu);
        $this->assertStringContainsString('rawurlencode(self::$option)', $baidu);
        $this->assertStringContainsString('NPCINK_SITE_TOOLBOX_VERSION', $baidu);
        $this->assertStringNotContainsString("echo '<script", $baidu);

        $compliance = $this->source('domestic/compliance/index.php');
        $this->assertStringContainsString('wp_kses_post($output)', $compliance);

        $comment_security = $this->source('domestic/comment_security/index.php');
        $this->assertStringContainsString("__('评论过于频繁，请 %d 秒后再试。', 'npcink-site-toolbox')", $comment_security);
    }

    public function test_census_setting_registers_its_real_sanitizer(): void
    {
        $source = $this->source('function/auxiliary/census-single.php');

        $this->assertStringContainsString(
            "'sanitize_callback' => array(__CLASS__, 'sanitize_options')",
            $source
        );
    }

    public function test_login_only_search_returns_an_explicit_forbidden_status(): void
    {
        $source = $this->source('page/function/login_search.php');

        $this->assertStringContainsString("array('response' => 403)", $source);
    }

    public function test_census_sanitizer_keeps_unique_positive_integer_user_ids(): void
    {
        $this->assertSame(
            array('option_id' => array(3, 7, 12)),
            Npcink_Toolbox_Census_Single::sanitize_options(array(
                'option_id' => array('3', '03', 3, 0, -7, '7.5', 'abc', 7, ' 12 '),
                'unexpected' => 'discard me',
            ))
        );
        $this->assertSame(
            array('option_id' => array()),
            Npcink_Toolbox_Census_Single::sanitize_options('invalid')
        );
    }

    private function source(string $relative_path): string
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/admin/partials/' . $relative_path);
        $this->assertIsString($source);

        return $source;
    }
}
