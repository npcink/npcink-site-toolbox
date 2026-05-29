<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MetadataAggregationTest extends TestCase {

    public function test_metadata_class_exists(): void {
        $this->assertTrue(class_exists('MaBox_Module_Metadata'));
    }

    public function test_legacy_registry_count_matches_aggregation(): void {
        MaBox_Module_Metadata::reset_cache();
        $legacy = require dirname(__DIR__, 2) . '/admin/modules/registry.php';
        $merged = MaBox_Module_Metadata::get_registry();

        $this->assertIsArray($legacy);
        $this->assertIsArray($merged);
        $this->assertCount(count($legacy), $merged, 'Merged registry should have the same module count as legacy');
    }

    public function test_manifest_overrides_legacy(): void {
        MaBox_Module_Metadata::reset_cache();
        $merged = MaBox_Module_Metadata::get_registry();

        $this->assertArrayHasKey('optimize.hide_top_toolbar', $merged);
        $meta = $merged['optimize.hide_top_toolbar'];

        $this->assertEquals('隐藏顶部工具条', $meta['label'], 'Manifest should override/add label field');
        $this->assertEquals('站点', $meta['group']);
        $this->assertEquals('optimize-site-hide_top_toolbar', $meta['feature_id']);
        $this->assertEquals('MaBox_Hide_Top_Toolbar', $meta['class'], 'Legacy class field should still be present');
    }

    public function test_manifest_adds_config_path(): void {
        MaBox_Module_Metadata::reset_cache();
        $merged = MaBox_Module_Metadata::get_registry();

        $this->assertArrayHasKey('optimize.cdn_replace', $merged);
        $meta = $merged['optimize.cdn_replace'];

        $this->assertEquals('optimize.site', $meta['config_path'], 'config_path from manifest should be preserved');
        $this->assertEquals('low', $meta['risk']['level'], 'Risk level from manifest should be present');
    }

    public function test_manifest_adds_always_load(): void {
        MaBox_Module_Metadata::reset_cache();
        $merged = MaBox_Module_Metadata::get_registry();

        $this->assertArrayHasKey('optimize.widgets', $merged);
        $meta = $merged['optimize.widgets'];

        $this->assertTrue($meta['always_load'], 'always_load from manifest should be preserved');
    }

    public function test_manifest_adds_high_risk(): void {
        MaBox_Module_Metadata::reset_cache();
        $merged = MaBox_Module_Metadata::get_registry();

        $this->assertArrayHasKey('domestic.login_security', $merged);
        $meta = $merged['domestic.login_security'];

        $this->assertEquals('high', $meta['risk']['level']);
        $this->assertEquals('自定义登录地址', $meta['risk']['title']);
        $this->assertTrue($meta['risk']['noDismiss']);
        $this->assertEquals('domestic.login_security', $meta['config_path']);
    }

    public function test_ui_metadata_excludes_internal_keys(): void {
        MaBox_Module_Metadata::reset_cache();
        $ui = MaBox_Module_Metadata::get_ui_metadata();

        $this->assertIsArray($ui);
        $this->assertNotEmpty($ui);

        foreach ($ui as $module_id => $entry) {
            $this->assertArrayNotHasKey('_option_key', $entry, "UI metadata should not contain _option_key for '{$module_id}'");
            $this->assertArrayNotHasKey('class', $entry, "UI metadata should not contain class for '{$module_id}'");
            $this->assertArrayNotHasKey('file', $entry, "UI metadata should not contain file path for '{$module_id}'");
            $this->assertArrayHasKey('id', $entry);
            $this->assertArrayHasKey('category', $entry);
            $this->assertArrayHasKey('scope', $entry);
            $this->assertArrayHasKey('label', $entry);
            $this->assertArrayHasKey('risk', $entry);
        }
    }

    public function test_get_module_returns_null_for_nonexistent(): void {
        MaBox_Module_Metadata::reset_cache();
        $this->assertNull(MaBox_Module_Metadata::get_module('nonexistent.module'));
    }

    public function test_get_module_returns_merged_data(): void {
        MaBox_Module_Metadata::reset_cache();
        $meta = MaBox_Module_Metadata::get_module('optimize.hide_top_toolbar');

        $this->assertIsArray($meta);
        $this->assertEquals('MaBox_Hide_Top_Toolbar', $meta['class']);
        $this->assertEquals('隐藏顶部工具条', $meta['label']);
    }

    public function test_required_keys_still_present_in_all_modules(): void {
        MaBox_Module_Metadata::reset_cache();
        $registry = MaBox_Module_Metadata::get_registry();
        $required = array('class', 'file', 'option_key', 'category', 'scope');

        foreach ($registry as $module_id => $meta) {
            foreach ($required as $key) {
                $this->assertArrayHasKey($key, $meta, "Module '{$module_id}' should have required key '{$key}'");
            }
        }
    }

    public function test_loader_get_registry_matches_metadata_get_registry(): void {
        MaBox_Module_Metadata::reset_cache();
        $from_loader = MaBox_Module_Loader::get_registry();
        $from_metadata = MaBox_Module_Metadata::get_registry();

        $this->assertEquals(array_keys($from_loader), array_keys($from_metadata), 'Module IDs should match between Loader and Metadata');
    }
}