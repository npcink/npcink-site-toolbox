<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../../includes/class-mabox-diagnostics.php';

class DiagnosticsSanitizeTest extends TestCase
{
    public function test_sanitize_for_export_method_exists(): void
    {
        $this->assertTrue(method_exists('MaBox_Diagnostics', 'sanitize_for_export'));
    }

    public function test_sanitize_hides_api_keys(): void
    {
        $data = array(
            'domestic' => array(
                'baidu_push' => array(
                    'active_push_enabled' => true,
                    'site' => 'example.com',
                    'token' => 'secret_baidu_token_12345',
                ),
            ),
        );

        $result = MaBox_Diagnostics::sanitize_for_export($data);

        $this->assertEquals('secret_baidu_token_12345', $data['domestic']['baidu_push']['token'],
            'Original data should not be modified');

        $this->assertEquals('***已隐藏***', $result['domestic']['baidu_push']['token']);
        $this->assertTrue($result['domestic']['baidu_push']['active_push_enabled']);
        $this->assertEquals('example.com', $result['domestic']['baidu_push']['site']);
    }

    public function test_sanitize_hides_secret_keys(): void
    {
        $data = array(
            'performance' => array(
                'oss' => array(
                    'enabled' => true,
                    'access_key' => 'AKIABCDEF123456',
                    'secret_key' => 'SK_SECRET_VALUE_789',
                    'bucket' => 'my-bucket',
                ),
            ),
        );

        $result = MaBox_Diagnostics::sanitize_for_export($data);

        $this->assertEquals('***已隐藏***', $result['performance']['oss']['access_key']);
        $this->assertEquals('***已隐藏***', $result['performance']['oss']['secret_key']);
        $this->assertTrue($result['performance']['oss']['enabled']);
        $this->assertEquals('my-bucket', $result['performance']['oss']['bucket']);
    }

    public function test_sanitize_hides_wechat_appsecret(): void
    {
        $data = array(
            'domestic' => array(
                'wechat' => array(
                    'jssdk_enabled' => true,
                    'appid' => 'wx123456',
                    'appsecret' => 'SECRET_WECHAT',
                ),
            ),
        );

        $result = MaBox_Diagnostics::sanitize_for_export($data);

        $this->assertEquals('***已隐藏***', $result['domestic']['wechat']['appsecret']);
        $this->assertEquals('wx123456', $result['domestic']['wechat']['appid']);
    }

    public function test_sanitize_does_not_modify_non_sensitive_data(): void
    {
        $data = array(
            'optimize' => array(
                'site' => array(
                    'hide_top_toolbar' => true,
                    'remove_RSS_version' => true,
                    'cdn_replace' => false,
                ),
                'medium' => array(
                    'img_add_tag' => true,
                ),
            ),
        );

        $result = MaBox_Diagnostics::sanitize_for_export($data);

        $this->assertEquals($data, $result);
    }

    public function test_sanitize_handles_empty_values(): void
    {
        $data = array(
            'domestic' => array(
                'baidu_push' => array(
                    'token' => '',
                    'site' => '',
                ),
            ),
        );

        $result = MaBox_Diagnostics::sanitize_for_export($data);

        $this->assertEquals('', $result['domestic']['baidu_push']['token']);
    }

    public function test_sanitize_handles_non_array_input(): void
    {
        $this->assertEquals('string_value', MaBox_Diagnostics::sanitize_for_export('string_value'));
        $this->assertEquals(42, MaBox_Diagnostics::sanitize_for_export(42));
        $this->assertEquals(null, MaBox_Diagnostics::sanitize_for_export(null));
    }

    public function test_sanitize_handles_deeply_nested_data(): void
    {
        $data = array(
            'level1' => array(
                'level2' => array(
                    'level3' => array(
                        'api_key' => 'NESTED_SECRET',
                        'normal_field' => 'visible',
                    ),
                ),
            ),
        );

        $result = MaBox_Diagnostics::sanitize_for_export($data);

        $this->assertEquals('***已隐藏***', $result['level1']['level2']['level3']['api_key']);
        $this->assertEquals('visible', $result['level1']['level2']['level3']['normal_field']);
    }

    public function test_sanitize_does_not_mutate_original(): void
    {
        $data = array(
            'test' => array(
                'api_key' => 'ORIGINAL_SECRET',
            ),
        );

        $original_data = $data;
        MaBox_Diagnostics::sanitize_for_export($data);

        $this->assertEquals('ORIGINAL_SECRET', $data['test']['api_key'],
            'sanitize_for_export should not mutate the original array');
    }
}
