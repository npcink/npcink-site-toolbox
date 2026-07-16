<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UpdaterRemovalTest extends TestCase
{
    private const MODULE_ID = 'optimize.ban_update';
    private const SEARCH_ID = 'optimize-site-renew';

    public function test_update_blocker_file_class_and_autoload_mapping_are_removed(): void
    {
        $root = $this->root();

        $this->assertFileDoesNotExist($root . '/admin/partials/optimize/site/ban_update.php');
        $this->assertFalse(class_exists('MaBox_Ban_Update'));

        $autoload = file_get_contents($root . '/includes/autoload.php');
        $this->assertIsString($autoload);
        $this->assertStringNotContainsString('MaBox_Ban_Update', $autoload);
        $this->assertStringNotContainsString('optimize/site/ban_update.php', $autoload);
    }

    public function test_registry_and_tiers_exclude_the_update_blocker(): void
    {
        $registry = require $this->root() . '/admin/modules/registry.php';
        $tiers = require $this->root() . '/admin/modules/tiers.php';

        $this->assertCount(56, $registry);
        $this->assertArrayNotHasKey(self::MODULE_ID, $registry);

        foreach ($tiers as $tier => $module_ids) {
            $this->assertNotContains(self::MODULE_ID, $module_ids, $tier);
        }
    }

    public function test_schema_defaults_and_search_index_exclude_the_update_blocker(): void
    {
        $schema = MaBox_Config_Schema::get_schema();
        $defaults = MaBox_Config_Schema::get_defaults();
        $contract = MaBox_Config_Schema::get_admin_settings_contract();

        $this->assertArrayNotHasKey('renew', $schema['optimize']['site']);
        $this->assertArrayNotHasKey('renew', $defaults['optimize']['site']);

        $search_ids = array_column($contract['searchIndex'], 'id');
        $this->assertCount(32, $search_ids);
        $this->assertNotContains(self::SEARCH_ID, $search_ids);
    }

    public function test_generated_frontend_contract_excludes_the_update_blocker(): void
    {
        $contract_source = file_get_contents(
            $this->root() . '/vite/admin/src/generated/settings-contract.json'
        );
        $this->assertIsString($contract_source);

        $contract = json_decode($contract_source, true, 512, JSON_THROW_ON_ERROR);
        $this->assertIsArray($contract);
        $this->assertArrayNotHasKey('renew', $contract['defaults']['optimize']['site']);

        $search_ids = array_column($contract['searchIndex'], 'id');
        $this->assertCount(32, $search_ids);
        $this->assertNotContains(self::SEARCH_ID, $search_ids);

        $types = file_get_contents(
            $this->root() . '/vite/admin/src/generated/settings-types.ts'
        );
        $this->assertIsString($types);
        $this->assertDoesNotMatchRegularExpression('/^\s+renew:\s*boolean;/m', $types);
    }

    public function test_frontend_source_has_no_retired_search_token(): void
    {
        $component = file_get_contents(
            $this->root() . '/vite/admin/src/components/optimize/site.tsx'
        );
        $this->assertIsString($component);
        $this->assertStringNotContainsString(self::SEARCH_ID, $component);
        $this->assertStringNotContainsString('formData.renew', $component);
    }

    public function test_current_documentation_does_not_advertise_update_blocker(): void
    {
        $documents = array(
            '功能清单.md' => '站点优化（8 项）',
            '技术架构与功能文档.md' => '站点优化 (18 项)',
            'docs-site/features/overview.md' => '站点优化（7 项）',
        );

        foreach ($documents as $relative_path => $expected_heading) {
            $source = file_get_contents($this->root() . '/' . $relative_path);
            $this->assertIsString($source);
            $this->assertStringContainsString($expected_heading, $source, $relative_path);
            $this->assertStringNotContainsString('禁用自动更新', $source, $relative_path);
            $this->assertStringNotContainsString('disable-auto-update', $source, $relative_path);
        }

        $this->assertFileDoesNotExist(
            $this->root() . '/docs-site/features/site-optimization/disable-auto-update.md'
        );
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
