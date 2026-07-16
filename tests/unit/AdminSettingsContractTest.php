<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class AdminSettingsContractTest extends TestCase
{
    public function test_generated_contract_is_current_and_contains_no_sensitive_fields(): void
    {
        $root = dirname(__DIR__, 2);
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

        $contract = json_decode($json, true);
        $this->assertIsArray($contract);
        $this->assertSame(array(), $contract['defaults']['page']['function']['countdown']);
        $this->assertEquals(MaBox_Config_Schema::get_schema_ui_schema(), $contract['uiSchema']);
    }

    public function test_browser_defaults_match_runtime_secret_stripping(): void
    {
        $contract = MaBox_Config_Schema::get_admin_settings_contract();
        $runtime = MaBox_Config_Manager::get_browser_config(MaBox_Config_Schema::get_defaults());

        $this->assertEquals($runtime['data'], $contract['defaults']);
    }

    public function test_flat_schema_contract_excludes_sensitive_fields(): void
    {
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
                ),
                'secret' => array(
                    'type' => 'string',
                    'default' => 'must-not-export',
                    'sensitive' => true,
                    'label' => 'Secret field',
                    'feature_id' => 'flat-secret',
                    'risk' => array('level' => 'high'),
                ),
            ),
        ));

        try {
            $contract = MaBox_Config_Schema::get_admin_settings_contract();

            $this->assertSame(array('visible' => 'shown'), $contract['defaults']['flat']);
            $this->assertArrayNotHasKey('secret', $contract['defaults']['flat']);
            $this->assertSame('flat.visible', $contract['uiSchema']['flat-visible']['path']);
            $this->assertArrayNotHasKey('flat-secret', $contract['uiSchema']);
        } finally {
            $property->setValue(null, $original);
        }
    }
}
