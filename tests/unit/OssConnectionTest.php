<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private $code;
        private $message;
        private $data;

        public function __construct($code = '', $message = '', $data = array())
        {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code()
        {
            return $this->code;
        }

        public function get_error_message()
        {
            return $this->message;
        }

        public function get_error_data()
        {
            return $this->data;
        }
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($value)
    {
        return is_object($value) && is_a($value, 'WP_Error');
    }
}

if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response)
    {
        return isset($response['response']['code']) ? (int) $response['response']['code'] : 0;
    }
}

if (!function_exists('wp_remote_request')) {
    function wp_remote_request($url, $args = array())
    {
        $GLOBALS['_test_oss_remote_calls'][] = array('url' => $url, 'args' => $args);
        return $GLOBALS['_test_oss_remote_response'];
    }
}

if (!function_exists('wp_remote_post')) {
    function wp_remote_post($url, $args = array())
    {
        $GLOBALS['_test_oss_remote_calls'][] = array('url' => $url, 'args' => $args);
        return $GLOBALS['_test_oss_remote_response'];
    }
}

if (!function_exists('wp_generate_password')) {
    function wp_generate_password($length = 12, $special_chars = true, $extra_special_chars = false)
    {
        unset($special_chars, $extra_special_chars);
        return str_repeat('a', (int) $length);
    }
}

final class OssConnectionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['_test_option_store'] = array();
        $GLOBALS['_test_oss_remote_calls'] = array();
        $GLOBALS['_test_oss_remote_response'] = array(
            'response' => array('code' => 200),
            'body' => '',
        );
        Npcink_Toolbox_Config_Manager::clear_cache();
    }

    public function test_connection_uses_pending_credentials_without_saving_or_enabling(): void
    {
        $settings = $this->browserSettings();
        $before = $GLOBALS['_test_option_store'];

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $settings,
                'secretChanges' => array(
                    'performance.oss.access_key' => array(
                        'operation' => 'replace',
                        'value' => 'pending-access-key',
                    ),
                    'performance.oss.secret_key' => array(
                        'operation' => 'replace',
                        'value' => 'pending-secret-key',
                    ),
                ),
            ))
        );

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertSame('aliyun', $response['data']['provider']);
        $this->assertSame('www/npcink-site-toolbox/connection-test.txt', $response['data']['objectKey']);
        $this->assertIsInt($response['data']['latencyMs']);
        $this->assertGreaterThanOrEqual(0, $response['data']['latencyMs']);
        $this->assertSame($before, $GLOBALS['_test_option_store']);
        $this->assertFalse($settings['performance']['oss']['enabled']);

        $this->assertCount(1, $GLOBALS['_test_oss_remote_calls']);
        $call = $GLOBALS['_test_oss_remote_calls'][0];
        $this->assertSame(
            'https://bucket-a.oss-cn-hangzhou.aliyuncs.com/www/npcink-site-toolbox/connection-test.txt',
            $call['url']
        );
        $this->assertSame('PUT', $call['args']['method']);
        $this->assertSame("Npcink Site Toolbox object storage connection test.\n", $call['args']['body']);
        $this->assertStringStartsWith('OSS pending-access-key:', $call['args']['headers']['Authorization']);
        $this->assertStringNotContainsString('pending-secret-key', serialize($response));
    }

    public function test_connection_can_use_stored_credentials_without_returning_them(): void
    {
        $stored = Npcink_Toolbox_Config_Schema::get_defaults()['performance'];
        $stored['oss'] = array_merge($stored['oss'], array(
            'access_key' => 'stored-access-key',
            'secret_key' => 'stored-secret-key',
        ));
        $GLOBALS['_test_option_store'][NPCINK_SITE_TOOLBOX_OPTION_PERFORMANCE] = $stored;
        Npcink_Toolbox_Config_Manager::clear_cache();

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $this->browserSettings(),
                'secretChanges' => array(),
            ))
        );

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertStringStartsWith(
            'OSS stored-access-key:',
            $GLOBALS['_test_oss_remote_calls'][0]['args']['headers']['Authorization']
        );
        $this->assertStringNotContainsString('stored-access-key', serialize($response));
        $this->assertStringNotContainsString('stored-secret-key', serialize($response));
    }

    public function test_connection_dispatches_tencent_cos_with_the_fixed_object_key(): void
    {
        $settings = $this->browserSettings();
        $settings['performance']['oss']['provider'] = 'tencent';
        $settings['performance']['oss']['bucket'] = 'npcink-media-1250000000';
        $settings['performance']['oss']['region'] = 'ap-beijing';

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $settings,
                'secretChanges' => $this->replacementCredentials(),
            ))
        );

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertSame('tencent', $response['data']['provider']);
        $this->assertSame(
            'https://npcink-media-1250000000.cos.ap-beijing.myqcloud.com/www/npcink-site-toolbox/connection-test.txt',
            $GLOBALS['_test_oss_remote_calls'][0]['url']
        );
        $this->assertStringStartsWith(
            'q-sign-algorithm=sha1&q-ak=pending-access-key',
            $GLOBALS['_test_oss_remote_calls'][0]['args']['headers']['Authorization']
        );
    }

    public function test_connection_dispatches_qiniu_without_requiring_region(): void
    {
        $settings = $this->browserSettings();
        $settings['performance']['oss']['provider'] = 'qiniu';
        $settings['performance']['oss']['region'] = '';

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $settings,
                'secretChanges' => $this->replacementCredentials(),
            ))
        );

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertSame('qiniu', $response['data']['provider']);
        $this->assertSame('https://up.qiniup.com/', $GLOBALS['_test_oss_remote_calls'][0]['url']);
        $this->assertStringContainsString(
            "name=\"key\"\r\n\r\nwww/npcink-site-toolbox/connection-test.txt",
            $GLOBALS['_test_oss_remote_calls'][0]['args']['body']
        );
    }

    /**
     * @dataProvider aliyunEndpointProvider
     */
    public function test_aliyun_connection_accepts_console_endpoint_and_region_shortcuts(
        string $endpoint,
        string $expectedHost
    ): void {
        $settings = $this->browserSettings();
        $settings['performance']['oss']['endpoint'] = $endpoint;

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $settings,
                'secretChanges' => $this->replacementCredentials(),
            ))
        );

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertStringStartsWith(
            'https://bucket-a.' . $expectedHost . '/www/',
            $GLOBALS['_test_oss_remote_calls'][0]['url']
        );
    }

    public function aliyunEndpointProvider(): array
    {
        return array(
            'console endpoint' => array(
                'oss-cn-shanghai.aliyuncs.com',
                'oss-cn-shanghai.aliyuncs.com',
            ),
            'endpoint with scheme' => array(
                'https://oss-cn-shanghai.aliyuncs.com/',
                'oss-cn-shanghai.aliyuncs.com',
            ),
            'region shortcut' => array(
                'cn-shanghai',
                'oss-cn-shanghai.aliyuncs.com',
            ),
            'short dedicated region' => array(
                'oss-cn-shanghai',
                'oss-cn-shanghai.aliyuncs.com',
            ),
            'internal endpoint' => array(
                'oss-cn-shanghai-internal.aliyuncs.com',
                'oss-cn-shanghai-internal.aliyuncs.com',
            ),
            'dual-stack endpoint' => array(
                'cn-shanghai.oss.aliyuncs.com',
                'cn-shanghai.oss.aliyuncs.com',
            ),
        );
    }

    /**
     * @dataProvider invalidAliyunTargetProvider
     */
    public function test_aliyun_connection_rejects_unsafe_endpoint_or_path(
        string $endpoint,
        string $path
    ): void {
        $settings = $this->browserSettings();
        $settings['performance']['oss']['endpoint'] = $endpoint;
        $settings['performance']['oss']['path'] = $path;

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $settings,
                'secretChanges' => $this->replacementCredentials(),
            ))
        );

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('npcink_oss_invalid_config', $response->get_error_code());
        $this->assertSame(array(), $GLOBALS['_test_oss_remote_calls']);
    }

    public function invalidAliyunTargetProvider(): array
    {
        return array(
            'arbitrary host' => array('https://example.com', 'www'),
            'loopback host' => array('https://127.0.0.1', 'www'),
            'bucket domain instead of endpoint' => array(
                'bucket-a.oss-cn-shanghai.aliyuncs.com',
                'www',
            ),
            'endpoint with path' => array(
                'https://oss-cn-shanghai.aliyuncs.com/private',
                'www',
            ),
            'parent path traversal' => array(
                'oss-cn-shanghai.aliyuncs.com',
                '../www',
            ),
            'backslash path' => array(
                'oss-cn-shanghai.aliyuncs.com',
                'www\\private',
            ),
        );
    }

    public function test_connection_write_does_not_require_a_public_access_url(): void
    {
        $settings = $this->browserSettings();
        $settings['performance']['oss']['domain'] = '';

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $settings,
                'secretChanges' => $this->replacementCredentials(),
            ))
        );

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertCount(1, $GLOBALS['_test_oss_remote_calls']);
    }

    public function test_connection_rejects_incomplete_target_before_outbound_request(): void
    {
        $settings = $this->browserSettings();
        $settings['performance']['oss']['endpoint'] = '';

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $settings,
                'secretChanges' => $this->replacementCredentials(),
            ))
        );

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('npcink_oss_invalid_config', $response->get_error_code());
        $this->assertSame(400, $response->get_error_data()['status']);
        $this->assertStringContainsString('Endpoint', $response->get_error_message());
        $this->assertSame(array(), $GLOBALS['_test_oss_remote_calls']);
    }

    public function test_tencent_connection_requires_the_appid_bucket_suffix(): void
    {
        $settings = $this->browserSettings();
        $settings['performance']['oss']['provider'] = 'tencent';
        $settings['performance']['oss']['bucket'] = 'npcink-media';
        $settings['performance']['oss']['region'] = 'ap-beijing';

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $settings,
                'secretChanges' => $this->replacementCredentials(),
            ))
        );

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('npcink_oss_invalid_config', $response->get_error_code());
        $this->assertStringContainsString('APPID', $response->get_error_message());
        $this->assertSame(array(), $GLOBALS['_test_oss_remote_calls']);
    }

    public function test_tencent_connection_rejects_an_overlong_request_domain(): void
    {
        $settings = $this->browserSettings();
        $settings['performance']['oss']['provider'] = 'tencent';
        $settings['performance']['oss']['bucket'] = str_repeat('a', 22) . '-1250000000';
        $settings['performance']['oss']['region'] = 'ap-beijing';

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $settings,
                'secretChanges' => $this->replacementCredentials(),
            ))
        );

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('npcink_oss_invalid_config', $response->get_error_code());
        $this->assertStringContainsString('60 个字符', $response->get_error_message());
        $this->assertSame(array(), $GLOBALS['_test_oss_remote_calls']);
    }

    public function test_connection_failure_returns_a_sanitized_error(): void
    {
        $GLOBALS['_test_oss_remote_response'] = array(
            'response' => array('code' => 403),
            'body' => 'provider debug: pending-secret-key',
        );

        $response = Npcink_Toolbox_Performance_Oss::rest_test_connection(
            new OssConnectionRequest(array(
                'settings' => $this->browserSettings(),
                'secretChanges' => $this->replacementCredentials(),
            ))
        );

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('npcink_oss_connection_failed', $response->get_error_code());
        $this->assertSame(502, $response->get_error_data()['status']);
        $this->assertStringContainsString('无法写入测试对象', $response->get_error_message());
        $this->assertStringNotContainsString('pending-secret-key', serialize($response));
        $this->assertStringNotContainsString('provider debug', serialize($response));
    }

    private function browserSettings(): array
    {
        $settings = Npcink_Toolbox_Config_Manager::get_browser_config(array())['data'];
        $settings['performance']['oss'] = array_merge($settings['performance']['oss'], array(
            'enabled' => false,
            'provider' => 'aliyun',
            'bucket' => 'bucket-a',
            'path' => 'www',
            'endpoint' => 'oss-cn-hangzhou.aliyuncs.com',
            'region' => '',
            'domain' => 'https://cdn.example.com',
        ));
        return $settings;
    }

    private function replacementCredentials(): array
    {
        return array(
            'performance.oss.access_key' => array(
                'operation' => 'replace',
                'value' => 'pending-access-key',
            ),
            'performance.oss.secret_key' => array(
                'operation' => 'replace',
                'value' => 'pending-secret-key',
            ),
        );
    }
}

final class OssConnectionRequest
{
    private $body;

    public function __construct(array $body)
    {
        $this->body = $body;
    }

    public function get_json_params()
    {
        return $this->body;
    }
}
