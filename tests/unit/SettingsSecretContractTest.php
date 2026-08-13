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

class SettingsSecretContractTest extends TestCase
{
    private const CANARY = 'mabox-canary-secret-2026';

    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['_test_option_store'] = array();
        $GLOBALS['_test_update_option_failures'] = array();
        Npcink_Toolbox_Config_Manager::clear_cache();
    }

    public function test_schema_is_the_single_source_for_secret_paths(): void
    {
        $schema = Npcink_Toolbox_Config_Schema::get_schema();
        $expected = array(
            'domestic.wechat.appsecret',
            'performance.oss.access_key',
            'performance.oss.secret_key',
        );

        $this->assertSame($expected, Npcink_Toolbox_Config_Manager::get_secret_paths());
        $this->assertTrue($schema['domestic']['wechat']['appsecret']['sensitive']);
        $this->assertTrue($schema['performance']['oss']['access_key']['sensitive']);
        $this->assertTrue($schema['performance']['oss']['secret_key']['sensitive']);
        $this->assertSame('', $schema['domestic']['wechat']['appsecret']['default']);
        $this->assertSame('', $schema['performance']['oss']['access_key']['default']);
        $this->assertSame('', $schema['performance']['oss']['secret_key']['default']);
        $this->assertStringNotContainsString(self::CANARY, serialize(Npcink_Toolbox_Config_Schema::get_defaults()));
    }

    public function test_array_item_contracts_do_not_change_defaults_or_ui_schema(): void
    {
        $schema = Npcink_Toolbox_Config_Schema::get_schema();
        $defaults = Npcink_Toolbox_Config_Schema::get_defaults();
        $ui_schema = Npcink_Toolbox_Config_Schema::get_ui_schema();

        $this->assertSame(array('type' => 'string'), $schema['page']['function']['countdown']['items']);
        $this->assertSame('number', $schema['page']['jurisdiction']['category_id']['items']['type']);
        $this->assertTrue($schema['page']['jurisdiction']['category_id']['items']['finite']);
        $this->assertSame(array(), $defaults['page']['function']['countdown']);
        $this->assertSame(array(), $defaults['page']['jurisdiction']['category_id']);
        $this->assertStringNotContainsString('items', serialize($ui_schema));
    }

    public function test_browser_config_removes_secret_keys_and_reports_status(): void
    {
        $config = $this->configWithSecrets(self::CANARY);

        $browser = Npcink_Toolbox_Config_Manager::get_browser_config($config);

        $this->assertArrayNotHasKey('appsecret', $browser['data']['domestic']['wechat']);
        $this->assertArrayNotHasKey('access_key', $browser['data']['performance']['oss']);
        $this->assertArrayNotHasKey('secret_key', $browser['data']['performance']['oss']);
        $this->assertTrue($browser['secretStatus']['domestic.wechat.appsecret']['configured']);
        $this->assertTrue($browser['secretStatus']['performance.oss.access_key']['configured']);
        $this->assertTrue($browser['secretStatus']['performance.oss.secret_key']['configured']);
        $this->assertStringNotContainsString(self::CANARY, serialize($browser));
    }

    public function test_browser_config_drops_retired_batch_replacement_fields(): void
    {
        $config = Npcink_Toolbox_Config_Schema::get_defaults();
        $config['page']['function']['batch_replace'] = true;
        $config['page']['function']['batch_replace_pairs'] = array(
            array('find' => 'old', 'replace' => 'new'),
        );

        $browser = Npcink_Toolbox_Config_Manager::get_browser_config($config);

        $this->assertArrayNotHasKey('batch_replace', $browser['data']['page']['function']);
        $this->assertArrayNotHasKey('batch_replace_pairs', $browser['data']['page']['function']);
        $this->assertFalse(Npcink_Toolbox_Config_Schema::validate_browser_settings($config)['valid']);
    }

    public function test_rest_get_settings_never_returns_canary_values(): void
    {
        $config = $this->configWithSecrets(self::CANARY);
        $GLOBALS['_test_option_store']['npcink_site_toolbox_domestic'] = $config['domestic'];
        $GLOBALS['_test_option_store']['npcink_site_toolbox_performance'] = $config['performance'];
        Npcink_Toolbox_Config_Manager::clear_cache();

        $response = Npcink_Toolbox_Admin::rest_get_settings(null);

        $this->assertIsArray($response);
        $this->assertStringNotContainsString(self::CANARY, serialize($response));
        $this->assertArrayNotHasKey('appsecret', $response['data']['domestic']['wechat']);
        $this->assertArrayNotHasKey('access_key', $response['data']['performance']['oss']);
        $this->assertArrayNotHasKey('secret_key', $response['data']['performance']['oss']);
    }

    public function test_fresh_install_rest_get_returns_complete_non_sensitive_defaults(): void
    {
        $GLOBALS['_test_option_store'] = array();
        Npcink_Toolbox_Config_Manager::clear_cache();

        $response = Npcink_Toolbox_Admin::rest_get_settings(null);
        $expected_modules = array_keys(Npcink_Toolbox_Config_Schema::get_defaults());

        $this->assertSame($expected_modules, array_keys($response['data']));
        $this->assertArrayHasKey('wechat', $response['data']['domestic']);
        $this->assertArrayHasKey('appid', $response['data']['domestic']['wechat']);
        $this->assertArrayHasKey('oss', $response['data']['performance']);
        $this->assertArrayHasKey('enabled', $response['data']['performance']['oss']);
        $this->assertArrayNotHasKey('appsecret', $response['data']['domestic']['wechat']);
        $this->assertArrayNotHasKey('access_key', $response['data']['performance']['oss']);
        $this->assertArrayNotHasKey('secret_key', $response['data']['performance']['oss']);
        foreach ($response['secretStatus'] as $status) {
            $this->assertFalse($status['configured']);
        }
        $this->assertSame(array('success', 'data', 'secretStatus', 'revision'), array_keys($response));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $response['revision']);
    }

    public function test_rest_save_rejects_stale_revision_without_writes(): void
    {
        $settings = $this->browserSettings();
        $current_revision = Npcink_Toolbox_Config_Manager::get_config_revision();

        $GLOBALS['_test_option_store']['npcink_site_toolbox_optimize'] = array('changed_elsewhere' => true);
        Npcink_Toolbox_Config_Manager::clear_cache();
        $before = $GLOBALS['_test_option_store'];

        $response = Npcink_Toolbox_Admin::rest_save_settings(new SettingsContractRequest(array(
            'settings' => $settings,
            'secretChanges' => array(),
            'revision' => $current_revision,
        ), false));

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('rest_settings_conflict', $response->get_error_code());
        $this->assertSame(409, $response->get_error_data()['status']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $response->get_error_data()['revision']);
        $this->assertSame($before, $GLOBALS['_test_option_store']);
    }

    public function test_rest_save_requires_a_valid_revision(): void
    {
        foreach (array(null, '', 'not-a-revision') as $revision) {
            $body = array(
                'settings' => $this->browserSettings(),
                'secretChanges' => array(),
            );
            if ($revision !== null) {
                $body['revision'] = $revision;
            }

            $response = Npcink_Toolbox_Admin::rest_save_settings(new SettingsContractRequest($body, false));

            $this->assertInstanceOf(WP_Error::class, $response);
            $this->assertSame('rest_invalid_data', $response->get_error_code());
            $this->assertSame(400, $response->get_error_data()['status']);
        }
    }

    public function test_missing_secret_change_keeps_existing_values(): void
    {
        $result = Npcink_Toolbox_Config_Manager::merge_secret_changes(
            $this->browserSettings(),
            array(),
            $this->configWithSecrets(self::CANARY)
        );

        $this->assertTrue($result['success']);
        $this->assertSame(self::CANARY, $result['data']['domestic']['wechat']['appsecret']);
        $this->assertSame(self::CANARY, $result['data']['performance']['oss']['access_key']);
        $this->assertSame(self::CANARY, $result['data']['performance']['oss']['secret_key']);
    }

    public function test_replace_and_clear_are_applied_independently(): void
    {
        $result = Npcink_Toolbox_Config_Manager::merge_secret_changes(
            $this->browserSettings(),
            array(
                'domestic.wechat.appsecret' => array('operation' => 'replace', 'value' => 'new-wechat-secret'),
                'performance.oss.secret_key' => array('operation' => 'clear'),
            ),
            $this->configWithSecrets(self::CANARY)
        );

        $this->assertTrue($result['success']);
        $this->assertSame('new-wechat-secret', $result['data']['domestic']['wechat']['appsecret']);
        $this->assertSame(self::CANARY, $result['data']['performance']['oss']['access_key']);
        $this->assertSame('', $result['data']['performance']['oss']['secret_key']);
    }

    /**
     * @dataProvider invalidSecretChangesProvider
     */
    public function test_invalid_secret_changes_are_rejected(array $changes): void
    {
        $result = Npcink_Toolbox_Config_Manager::merge_secret_changes(
            $this->browserSettings(),
            $changes,
            $this->configWithSecrets(self::CANARY)
        );

        $this->assertFalse($result['success']);
        $this->assertArrayNotHasKey('data', $result);
    }

    public function invalidSecretChangesProvider(): array
    {
        return array(
            'unknown path' => array(array('unknown.secret' => array('operation' => 'clear'))),
            'unknown operation' => array(array('domestic.wechat.appsecret' => array('operation' => 'keep'))),
            'empty replacement' => array(array('domestic.wechat.appsecret' => array('operation' => 'replace', 'value' => ''))),
            'whitespace replacement' => array(array('domestic.wechat.appsecret' => array('operation' => 'replace', 'value' => '   '))),
            'control character' => array(array('domestic.wechat.appsecret' => array('operation' => 'replace', 'value' => "bad\nsecret"))),
            'oversized replacement' => array(array('domestic.wechat.appsecret' => array('operation' => 'replace', 'value' => str_repeat('x', 4097)))),
            'extra operation field' => array(array('domestic.wechat.appsecret' => array('operation' => 'clear', 'value' => 'ignored'))),
        );
    }

    public function test_direct_secret_in_settings_is_rejected(): void
    {
        $settings = $this->browserSettings();
        $settings['domestic']['wechat']['appsecret'] = 'smuggled-secret';

        $result = Npcink_Toolbox_Config_Manager::merge_secret_changes(
            $settings,
            array(),
            $this->configWithSecrets(self::CANARY)
        );

        $this->assertFalse($result['success']);
        $this->assertStringNotContainsString('smuggled-secret', serialize($GLOBALS['_test_option_store']));
    }

    public function test_rest_rejects_direct_secret_and_unknown_top_level_fields(): void
    {
        $settings = $this->browserSettings();
        $settings['domestic']['wechat']['appsecret'] = 'smuggled-secret';

        $direct_secret = Npcink_Toolbox_Admin::rest_save_settings(new SettingsContractRequest(array(
            'settings' => $settings,
            'secretChanges' => array(),
        )));
        $unknown_top_level = Npcink_Toolbox_Admin::rest_save_settings(new SettingsContractRequest(array(
            'settings' => $this->browserSettings(),
            'secretChanges' => array(),
            'legacyConfig' => array(),
        )));

        $this->assertInstanceOf(WP_Error::class, $direct_secret);
        $this->assertSame('rest_invalid_data', $direct_secret->get_error_code());
        $this->assertSame(400, $direct_secret->get_error_data()['status']);
        $this->assertInstanceOf(WP_Error::class, $unknown_top_level);
        $this->assertSame('rest_invalid_data', $unknown_top_level->get_error_code());
        $this->assertStringNotContainsString('smuggled-secret', serialize($GLOBALS['_test_option_store']));
    }

    public function test_incomplete_unknown_and_wrong_typed_settings_are_rejected_without_writes(): void
    {
        foreach ($this->invalidSettingsTrees() as $label => $settings) {
            $GLOBALS['_test_option_store'] = array(
                'npcink_site_toolbox_optimize' => array('sentinel' => $label),
            );
            Npcink_Toolbox_Config_Manager::clear_cache();
            $before = $GLOBALS['_test_option_store'];

            $manager_result = Npcink_Toolbox_Config_Manager::merge_secret_changes($settings, array(), array());
            $rest_result = Npcink_Toolbox_Admin::rest_save_settings(new SettingsContractRequest(array(
                'settings' => $settings,
                'secretChanges' => array(),
            )));

            $this->assertFalse($manager_result['success'], "{$label} should fail manager validation");
            $this->assertInstanceOf(WP_Error::class, $rest_result, "{$label} should fail REST validation");
            $this->assertSame('rest_invalid_data', $rest_result->get_error_code(), $label);
            $this->assertSame(400, $rest_result->get_error_data()['status'], $label);
            $this->assertSame($before, $GLOBALS['_test_option_store'], "{$label} must not write options");
        }
    }

    public function test_login_security_accepts_and_canonicalizes_exact_ipv4_and_ipv6_proxy_addresses(): void
    {
        $settings = $this->browserSettings();
        $settings['domestic']['login_security']['trusted_proxies'] = implode("\r\n", array(
            '203.0.113.10',
            '2001:0db8:0000:0000:0000:0000:0000:0010',
            '203.0.113.10',
        ));
        $expected = "203.0.113.10\n2001:db8::10";

        $browser_validation = Npcink_Toolbox_Config_Schema::validate_browser_settings($settings);
        $this->assertTrue($browser_validation['valid']);

        $merge = Npcink_Toolbox_Config_Manager::merge_secret_changes($settings, array(), array());
        $this->assertTrue($merge['success']);
        $sanitized = Npcink_Toolbox_Config_Schema::validate_full_config($merge['data']);
        $this->assertTrue($sanitized['valid']);
        $this->assertSame($expected, $sanitized['data']['domestic']['login_security']['trusted_proxies']);

        $response = Npcink_Toolbox_Admin::rest_save_settings(new SettingsContractRequest(array(
            'settings' => $settings,
            'secretChanges' => array(),
        )));

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertSame(
            $expected,
            $GLOBALS['_test_option_store']['npcink_site_toolbox_domestic']['login_security']['trusted_proxies']
        );
    }

    public function test_login_security_rejects_invalid_proxy_lists_and_non_integer_limits_without_writes(): void
    {
        $invalid_cases = array(
            'CIDR proxy' => array('trusted_proxies', '10.0.0.0/24'),
            'proxy domain' => array('trusted_proxies', 'proxy.example.com'),
            'proxy wildcard' => array('trusted_proxies', '10.0.0.*'),
            'mixed valid and invalid proxies' => array('trusted_proxies', "203.0.113.10\nnot-an-ip"),
            'fractional attempt count' => array('attempt_limit_count', 2.5),
            'floating point whole attempt window' => array('attempt_window_minutes', 15.0),
            'fractional lock duration' => array('lock_duration_minutes', 30.5),
            'attempt count below minimum' => array('attempt_limit_count', 1),
            'attempt count above maximum' => array('attempt_limit_count', 21),
            'attempt window below minimum' => array('attempt_window_minutes', 0),
            'lock duration above maximum' => array('lock_duration_minutes', 1441),
        );

        foreach ($invalid_cases as $label => $case) {
            $settings = $this->browserSettings();
            $settings['domestic']['login_security'][$case[0]] = $case[1];
            $GLOBALS['_test_option_store'] = array(
                'npcink_site_toolbox_domestic' => array('sentinel' => $label),
            );
            Npcink_Toolbox_Config_Manager::clear_cache();
            $before = $GLOBALS['_test_option_store'];

            $browser_validation = Npcink_Toolbox_Config_Schema::validate_browser_settings($settings);
            $manager_result = Npcink_Toolbox_Config_Manager::merge_secret_changes($settings, array(), array());
            $rest_result = Npcink_Toolbox_Admin::rest_save_settings(new SettingsContractRequest(array(
                'settings' => $settings,
                'secretChanges' => array(),
            )));

            $this->assertFalse($browser_validation['valid'], "{$label} should fail browser boundary validation");
            $this->assertFalse($manager_result['success'], "{$label} should fail manager validation");
            $this->assertInstanceOf(WP_Error::class, $rest_result, "{$label} should fail REST validation");
            $this->assertSame('rest_invalid_data', $rest_result->get_error_code(), $label);
            $this->assertSame(400, $rest_result->get_error_data()['status'], $label);
            $this->assertSame($before, $GLOBALS['_test_option_store'], "{$label} must not write options");
        }
    }

    public function test_complete_settings_with_keep_replace_and_clear_save_successfully(): void
    {
        $current = $this->configWithSecrets(self::CANARY);
        foreach (Npcink_Toolbox_Config_Manager::get_module_map() as $module => $option_name) {
            $GLOBALS['_test_option_store'][$option_name] = $current[$module];
        }
        Npcink_Toolbox_Config_Manager::clear_cache();

        $settings = $this->browserSettings();
        $settings['page']['function']['countdown'] = array('2026-07-15 12:00:00');
        $settings['page']['jurisdiction']['category_id'] = array(1, 2.5);
        $settings['page']['jurisdiction']['tag_id'] = array(3);
        $settings['page']['jurisdiction']['page_id'] = array(4);
        $settings['page']['jurisdiction']['single_id'] = array(5);

        $response = Npcink_Toolbox_Admin::rest_save_settings(new SettingsContractRequest(array(
            'settings' => $settings,
            'secretChanges' => array(
                'domestic.wechat.appsecret' => array('operation' => 'replace', 'value' => 'new-wechat-secret'),
                'performance.oss.secret_key' => array('operation' => 'clear'),
            ),
        )));

        $this->assertIsArray($response);
        $this->assertTrue($response['success']);
        $this->assertSame(
            'new-wechat-secret',
            $GLOBALS['_test_option_store']['npcink_site_toolbox_domestic']['wechat']['appsecret']
        );
        $this->assertSame(
            self::CANARY,
            $GLOBALS['_test_option_store']['npcink_site_toolbox_performance']['oss']['access_key']
        );
        $this->assertSame(
            '',
            $GLOBALS['_test_option_store']['npcink_site_toolbox_performance']['oss']['secret_key']
        );
        $this->assertSame(
            array('2026-07-15 12:00:00'),
            $GLOBALS['_test_option_store']['npcink_site_toolbox_page']['function']['countdown']
        );
        $this->assertSame(
            array(1, 2.5),
            $GLOBALS['_test_option_store']['npcink_site_toolbox_page']['jurisdiction']['category_id']
        );
    }

    public function test_rest_reports_unconfirmed_rollback_without_claiming_settings_were_restored(): void
    {
        $current = $this->configWithSecrets(self::CANARY);
        foreach (Npcink_Toolbox_Config_Manager::get_module_map() as $module => $option_name) {
            $GLOBALS['_test_option_store'][$option_name] = $current[$module];
        }
        Npcink_Toolbox_Config_Manager::clear_cache();

        $settings = $this->browserSettings();
        $settings['optimize']['site']['hide_top_toolbar'] = !$settings['optimize']['site']['hide_top_toolbar'];
        $settings['page']['feature']['reading_progress'] = !$settings['page']['feature']['reading_progress'];
        $GLOBALS['_test_update_option_failures'] = new SettingsRollbackFailureSequence();

        $response = Npcink_Toolbox_Admin::rest_save_settings(new SettingsContractRequest(array(
            'settings' => $settings,
            'secretChanges' => array(),
        )));

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('rest_save_failed', $response->get_error_code());
        $this->assertSame(500, $response->get_error_data()['status']);
        $this->assertStringContainsString('optimize', $response->get_error_message());
        $this->assertStringContainsString('请重新读取并核对设置后再保存', $response->get_error_message());
        $this->assertStringNotContainsString('已恢复为之前的设置', $response->get_error_message());
        $this->assertSame(
            $settings['optimize']['site']['hide_top_toolbar'],
            $GLOBALS['_test_option_store']['npcink_site_toolbox_optimize']['site']['hide_top_toolbar']
        );
    }

    public function test_admin_localization_does_not_embed_settings_or_defaults(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/admin/class-npcink-toolbox-admin.php');
        $this->assertStringNotContainsString("'option' => Npcink_Toolbox_Config_Manager::get_merged_config()", $source);
        $this->assertStringNotContainsString("'defaults' => Npcink_Toolbox_Config_Schema::get_defaults()", $source);
        $this->assertStringNotContainsString('wizard_completed', $source);
    }

    private function browserSettings(): array
    {
        $settings = Npcink_Toolbox_Config_Manager::get_browser_config(array())['data'];
        $settings['domestic']['wechat']['jssdk_enabled'] = true;
        $settings['domestic']['wechat']['appid'] = 'wx-app-id';
        $settings['performance']['oss']['enabled'] = true;
        $settings['performance']['oss']['bucket'] = 'bucket';
        return $settings;
    }

    private function invalidSettingsTrees(): array
    {
        $valid = $this->browserSettings();

        $missing_module = $valid;
        unset($missing_module['optimize']);

        $missing_submodule = $valid;
        unset($missing_submodule['domestic']['wechat']);

        $missing_field = $valid;
        unset($missing_field['performance']['oss']['bucket']);

        $unknown_module = $valid;
        $unknown_module['legacy'] = array();

        $unknown_field = $valid;
        $unknown_field['domestic']['wechat']['legacy_secret'] = 'value';

        $wrong_type = $valid;
        $wrong_type['performance']['oss']['enabled'] = 'true';

        $wrong_countdown_item = $valid;
        $wrong_countdown_item['page']['function']['countdown'] = array(array('nested' => 'value'));

        $wrong_number_item = $valid;
        $wrong_number_item['page']['jurisdiction']['category_id'] = array('not-a-number');

        $non_finite_number = $valid;
        $non_finite_number['page']['jurisdiction']['category_id'] = array(INF, NAN);

        return array(
            'empty settings' => array(),
            'missing module' => $missing_module,
            'missing submodule' => $missing_submodule,
            'missing field' => $missing_field,
            'unknown module' => $unknown_module,
            'unknown field' => $unknown_field,
            'wrong type' => $wrong_type,
            'wrong countdown item' => $wrong_countdown_item,
            'wrong number item' => $wrong_number_item,
            'non-finite number item' => $non_finite_number,
        );
    }

    private function configWithSecrets(string $secret): array
    {
        $config = $this->browserSettings();
        $config['domestic']['wechat']['appsecret'] = $secret;
        $config['performance']['oss']['access_key'] = $secret;
        $config['performance']['oss']['secret_key'] = $secret;
        return $config;
    }
}

class SettingsContractRequest
{
    private $body;

    public function __construct(array $body, bool $add_revision = true)
    {
        if ($add_revision && !array_key_exists('revision', $body)) {
            $body['revision'] = Npcink_Toolbox_Config_Manager::get_config_revision();
        }
        $this->body = $body;
    }

    public function get_json_params()
    {
        return $this->body;
    }
}

class SettingsRollbackFailureSequence implements ArrayAccess
{
    private $calls = array();

    public function offsetExists($offset): bool
    {
        return true;
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        $this->calls[$offset] = isset($this->calls[$offset]) ? $this->calls[$offset] + 1 : 1;

        if ($offset === 'npcink_site_toolbox_page') {
            return true;
        }

        return $offset === 'npcink_site_toolbox_optimize' && $this->calls[$offset] >= 2;
    }

    public function offsetSet($offset, $value): void
    {
    }

    public function offsetUnset($offset): void
    {
    }
}
