<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class AdminSettingsContractTest extends TestCase
{
    private function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private function requireExporter(): void
    {
        require_once $this->root() . '/tests/support/export-admin-settings-contract.php';
    }

    /**
     * @param mixed $value
     */
    private function assertContainsNoRawSearchMetadata($value): void
    {
        if (!is_array($value)) {
            return;
        }

        $this->assertArrayNotHasKey('search', $value);
        foreach ($value as $child) {
            $this->assertContainsNoRawSearchMetadata($child);
        }
    }

    public function test_generated_artifacts_are_current_and_contain_no_sensitive_fields(): void
    {
        $root = $this->root();
        $script = $root . '/tests/support/export-admin-settings-contract.php';
        $output = array();
        $status = 1;

        exec(escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg($script) . ' --check 2>&1', $output, $status);

        $this->assertSame(0, $status, implode("\n", $output));

        $json = file_get_contents($root . '/vite/admin/src/generated/settings-contract.json');
        $this->assertIsString($json);
        $this->assertStringNotContainsString('appsecret', $json);
        $this->assertStringNotContainsString('access_key', $json);
        $this->assertStringNotContainsString('secret_key', $json);

        $contract = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($contract);
        $this->assertSame(array('defaults', 'searchIndex', 'uiSchema'), array_keys($contract));
        $this->assertSame(array(), $contract['defaults']['page']['function']['countdown']);
        $this->assertEquals(MaBox_Config_Schema::get_schema_ui_schema(), $contract['uiSchema']);
        $this->assertFileDoesNotExist(
            $root . '/vite/admin/src/tool/featureIndexData.ts',
            'The retired handwritten search index must not return as a second source of truth.'
        );
        $this->assertContainsNoRawSearchMetadata(MaBox_Config_Schema::get_schema());
        foreach ($contract['uiSchema'] as $entry) {
            $this->assertArrayNotHasKey('search', $entry);
        }

        $this->requireExporter();
        $types = file_get_contents($root . '/vite/admin/src/generated/settings-types.ts');
        $this->assertIsString($types);
        $this->assertSame(mabox_render_admin_settings_types(MaBox_Config_Schema::get_schema()), $types);
    }

    public function test_generated_search_index_matches_schema_and_locks_semantic_routes(): void
    {
        $this->requireExporter();

        $json = file_get_contents($this->root() . '/vite/admin/src/generated/settings-contract.json');
        $this->assertIsString($json);
        $generated = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($generated);

        $actual = $generated['searchIndex'];
        $expected = mabox_normalize_contract(MaBox_Config_Schema::get_admin_settings_contract()['searchIndex']);

        $this->assertCount(32, $actual);
        $this->assertSame($expected, $actual);

        $ids = array_column($actual, 'id');
        $this->assertCount(32, array_unique($ids));

        $valid_views = array('site', 'content', 'seo', 'china', 'maintenance');
        foreach ($actual as $item) {
            $this->assertContains($item['tabKey'], $valid_views);
            $this->assertFalse((bool) preg_match('/^\d+$/', $item['tabKey']));
            $this->assertNotSame('', $item['id']);
        }

        $by_id = array();
        foreach ($actual as $item) {
            $by_id[$item['id']] = $item;
        }
        $this->assertContains('page-feature-maintenance_tips', $by_id['page-function-maintenance_tips']['aliases']);
        $this->assertContains('domestic-compliance-police', $by_id['domestic-compliance-police_enabled']['aliases']);
        $this->assertContains('domestic-compliance-cookie', $by_id['domestic-compliance-cookie_enabled']['aliases']);
        $this->assertContains('domestic-compliance-copyright', $by_id['domestic-compliance-copyright_enabled']['aliases']);
        $this->assertContains('domestic-comment_security-blacklist_enabled', $by_id['domestic-comment-blacklist']['aliases']);
        $this->assertContains('domestic-comment_security-link_limit', $by_id['domestic-comment-link-limit']['aliases']);
        $this->assertContains('domestic-comment_security-ip_rate_limit', $by_id['domestic-comment-ip-rate']['aliases']);

        $retired_ids = array(
            'login-security-login_code',
            'page-function-batch_replace',
            'page-function-batch_replace_pairs',
            'domestic-login_security-fail_limit_enabled',
            'domestic-login_security-ip_lock_enabled',
            'domestic-login_security-custom_login_enabled',
            'domestic-login_security-ban_enumeration_enabled',
            'domestic-login_security-login_notify_enabled',
            'domestic-login_security-login_log_enabled',
            'domestic-login_security-ip_whitelist_enabled',
        );
        $this->assertSame(array(), array_values(array_intersect($ids, $retired_ids)));
    }

    public function test_generated_types_preserve_names_arrays_option_shape_and_secret_boundary(): void
    {
        $types = file_get_contents($this->root() . '/vite/admin/src/generated/settings-types.ts');
        $this->assertIsString($types);

        $constant_match = array();
        $matched = preg_match('/export const SECRET_PATHS = \[(.*?)\n\] as const;/s', $types, $constant_match);
        $this->assertSame(1, $matched);
        $path_matches = array();
        preg_match_all('/^\s+"([^"]+)",$/m', $constant_match[1], $path_matches);
        $this->assertSame(array(
            'domestic.wechat.appsecret',
            'performance.oss.access_key',
            'performance.oss.secret_key',
        ), $path_matches[1]);

        $expected_type_names = array(
            'OptimizeSite',
            'OptimizeMedium',
            'OptimizeAdmin',
            'PageComment',
            'PageFeature',
            'PageFunction',
            'PageJurisdiction',
            'FunctionAuxiliary',
            'FunctionSeo',
            'FunctionTips',
            'DomesticCompliance',
            'DomesticWechat',
            'DomesticCommentSecurity',
            'DomesticLoginSecurity',
            'PerformanceOss',
            'PerformanceSeoChecker',
            'PerformanceMediaHealth',
            'PerformanceSearchEnhance',
            'PerformanceDbClean',
            'Option',
        );
        foreach ($expected_type_names as $type_name) {
            $this->assertStringContainsString("export type {$type_name} = {", $types);
        }

        $this->assertStringContainsString('export type SecretPath = (typeof SECRET_PATHS)[number];', $types);
        $this->assertStringContainsString('  countdown: string[];', $types);
        $this->assertStringContainsString('  category_id: number[];', $types);
        $this->assertStringContainsString('  [key: string]: any;', $types);
        $this->assertStringContainsString('    config: FunctionTips;', $types);

        $types_without_secret_paths = preg_replace('/export const SECRET_PATHS = \[.*?\n\] as const;\n/s', '', $types);
        $this->assertIsString($types_without_secret_paths);
        $this->assertStringNotContainsString('appsecret', $types_without_secret_paths);
        $this->assertStringNotContainsString('access_key', $types_without_secret_paths);
        $this->assertStringNotContainsString('secret_key', $types_without_secret_paths);
    }

    public function test_type_generator_fails_closed_for_unknown_schema_types(): void
    {
        $this->requireExporter();
        $schema = MaBox_Config_Schema::get_schema();
        $schema['optimize']['site']['hide_top_toolbar']['type'] = 'object';

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unsupported Schema type object at optimize.site.hide_top_toolbar');
        mabox_render_admin_settings_types($schema);
    }

    public function test_atomic_replace_restores_all_targets_when_a_later_install_fails(): void
    {
        $this->requireExporter();

        $temporary_path = tempnam(sys_get_temp_dir(), 'mabox-settings-atomic-');
        $this->assertIsString($temporary_path);
        $this->assertTrue(unlink($temporary_path));
        $this->assertTrue(mkdir($temporary_path, 0700));

        $first = $temporary_path . '/first';
        $second = $temporary_path . '/second';
        $this->assertSame(9, file_put_contents($first, 'old-first'));
        $this->assertTrue(mkdir($second, 0700));

        try {
            $error = null;
            try {
                mabox_atomic_replace_generated_files(array(
                    $first => 'new-first',
                    $second => 'new-second',
                ));
            } catch (Throwable $caught) {
                $error = $caught;
            }

            $this->assertInstanceOf(RuntimeException::class, $error);
            $this->assertSame('old-first', file_get_contents($first));
            $this->assertDirectoryExists($second);

            $entries = scandir($temporary_path);
            $this->assertIsArray($entries);
            $entries = array_values(array_diff($entries, array('.', '..')));
            sort($entries, SORT_STRING);
            $this->assertSame(array('first', 'second'), $entries);
        } finally {
            if (is_file($first)) {
                unlink($first);
            }
            if (is_dir($second)) {
                rmdir($second);
            }
            if (is_dir($temporary_path)) {
                rmdir($temporary_path);
            }
        }
    }

    public function test_browser_defaults_match_runtime_secret_stripping(): void
    {
        $contract = MaBox_Config_Schema::get_admin_settings_contract();
        $runtime = MaBox_Config_Manager::get_browser_config(MaBox_Config_Schema::get_defaults());

        $this->assertEquals($runtime['data'], $contract['defaults']);
    }

    public function test_flat_schema_contract_and_types_exclude_sensitive_fields(): void
    {
        $this->requireExporter();

        $property = new ReflectionProperty(MaBox_Config_Schema::class, 'schema');
        $property->setAccessible(true);
        $original = $property->getValue();
        $property->setValue(null, array(
            'flat' => array(
                '_option_key' => 'test-flat-option',
                '_flat' => true,
                'visible' => array(
                    'type' => 'string',
                    'default' => 'shown',
                    'label' => 'Visible field',
                    'feature_id' => 'flat-visible',
                    'risk' => array('level' => 'none'),
                    'search' => array(
                        'id' => 'flat-visible',
                        'label' => 'Visible field',
                        'view' => 'site',
                        'tabLabel' => '站点与媒体',
                        'section' => '测试',
                        'keywords' => array('visible'),
                    ),
                ),
                'secret' => array(
                    'type' => 'string',
                    'default' => 'must-not-export',
                    'sensitive' => true,
                    'label' => 'Secret field',
                    'feature_id' => 'flat-secret',
                    'risk' => array('level' => 'high'),
                    'search' => array(
                        'id' => 'flat-secret',
                        'label' => 'Must not export',
                        'view' => 'site',
                        'tabLabel' => '站点与媒体',
                        'section' => '测试',
                        'keywords' => array('must-not-export'),
                    ),
                ),
            ),
        ));

        try {
            $contract = MaBox_Config_Schema::get_admin_settings_contract();

            $this->assertContainsNoRawSearchMetadata(MaBox_Config_Schema::get_schema());
            $this->assertSame(array('visible' => 'shown'), $contract['defaults']['flat']);
            $this->assertArrayNotHasKey('secret', $contract['defaults']['flat']);
            $this->assertSame('flat.visible', $contract['uiSchema']['flat-visible']['path']);
            $this->assertArrayNotHasKey('search', $contract['uiSchema']['flat-visible']);
            $this->assertArrayNotHasKey('flat-secret', $contract['uiSchema']);
            $this->assertSame(array(
                array(
                    'id' => 'flat-visible',
                    'label' => 'Visible field',
                    'tabKey' => 'site',
                    'tabLabel' => '站点与媒体',
                    'section' => '测试',
                    'keywords' => array('visible'),
                ),
            ), $contract['searchIndex']);

            $types = mabox_render_admin_settings_types(MaBox_Config_Schema::get_schema());
            $this->assertStringContainsString('export type Flat = {', $types);
            $this->assertStringContainsString('  visible: string;', $types);
            $this->assertStringContainsString('  flat: Flat;', $types);
            $this->assertStringContainsString('  "flat.secret",', $types);

            $without_secret_paths = preg_replace('/export const SECRET_PATHS = \[.*?\n\] as const;\n/s', '', $types);
            $this->assertIsString($without_secret_paths);
            $this->assertStringNotContainsString('secret:', $without_secret_paths);
            $this->assertStringNotContainsString('must-not-export', json_encode($contract, JSON_UNESCAPED_UNICODE));
            $this->assertStringNotContainsString('flat-secret', json_encode($contract, JSON_UNESCAPED_UNICODE));
        } finally {
            $property->setValue(null, $original);
        }
    }
}
