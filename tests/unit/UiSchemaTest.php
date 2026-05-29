<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class UiSchemaTest extends TestCase {

    public function test_ui_schema_has_no_option_keys(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();

        $this->assertIsArray($ui);
        $this->assertNotEmpty($ui);

        $serialized = serialize($ui);
        $this->assertStringNotContainsString('_option_key', $serialized, 'uiSchema must not contain _option_key');
        $this->assertStringNotContainsString('MAGICK_MIXTURE_OPTION', $serialized, 'uiSchema must not expose option constants');
    }

    public function test_risky_features_map_to_frontend_ids(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();

        $risky_feature_ids = array(
            'optimize-medium-no_auto_size',
            'performance-db_clean-enabled',
            'optimize-medium-medium_add_svg',
            'domestic-login_security-custom_login_enabled',
            'domestic-login_security-ip_lock_enabled',
        );

        $found_feature_ids = array();
        foreach ($ui as $key => $entry) {
            if (isset($entry['feature_id'])) {
                $found_feature_ids[] = $entry['feature_id'];
            }
        }

        foreach ($risky_feature_ids as $fid) {
            $this->assertContains($fid, $found_feature_ids, "Risky feature ID '{$fid}' must exist in uiSchema");
        }
    }

    public function test_ui_schema_structure(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();

        foreach ($ui as $key => $entry) {
            $this->assertArrayHasKey('path', $entry, "UI entry '{$key}' must have path");
            $this->assertArrayHasKey('type', $entry, "UI entry '{$key}' must have type");
        }
    }

    public function test_risk_level_values(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();
        $valid_levels = array('none', 'low', 'high');

        foreach ($ui as $key => $entry) {
            if (isset($entry['risk']) && isset($entry['risk']['level'])) {
                $this->assertContains(
                    $entry['risk']['level'],
                    $valid_levels,
                    "Risk level for '{$key}' must be one of: none, low, high"
                );
            }
        }
    }

    public function test_high_risk_features_have_no_dismiss(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();

        foreach ($ui as $key => $entry) {
            if (isset($entry['risk']) && isset($entry['risk']['level']) && $entry['risk']['level'] === 'high') {
                $this->assertTrue(
                    !empty($entry['risk']['noDismiss']),
                    "High risk feature '{$key}' must have noDismiss=true"
                );
            }
        }
    }

    public function test_get_schema_still_includes_internal_keys(): void {
        $schema = MaBox_Config_Schema::get_schema();

        $serialized = serialize($schema);
        $this->assertStringContainsString('_option_key', $serialized, 'Full schema should still contain _option_key');
    }

    public function test_ui_schema_returns_array(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();

        $this->assertIsArray($ui);
        $this->assertNotEmpty($ui);
    }

    public function test_risky_features_have_risk_tags(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();

        $risky_count = 0;
        foreach ($ui as $key => $entry) {
            if (isset($entry['risk']) && !empty($entry['risk']['level']) && $entry['risk']['level'] !== 'none') {
                $risky_count++;
                $this->assertArrayHasKey('risk_tags', $entry, "Risky feature '{$key}' must have risk_tags");
                $this->assertIsArray($entry['risk_tags'], "risk_tags for '{$key}' must be an array");
                $this->assertNotEmpty($entry['risk_tags'], "risk_tags for '{$key}' must not be empty");
            }
        }
        $this->assertGreaterThan(0, $risky_count, 'There should be at least one risky feature with risk_tags');
    }

    public function test_risk_tags_derived_from_level(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();

        $module_overridden = array();
        if (class_exists('MaBox_Module_Metadata')) {
            $module_ui = MaBox_Module_Metadata::get_ui_metadata();
            foreach ($module_ui as $module_id => $meta) {
                if (!empty($meta['risk_tags']) && !empty($meta['feature_id'])) {
                    $module_overridden[$meta['feature_id']] = $meta['risk_tags'];
                }
            }
        }

        foreach ($ui as $key => $entry) {
            if (!isset($entry['risk']) || !isset($entry['risk']['level']) || $entry['risk']['level'] === 'none') {
                continue;
            }
            $feature_id = isset($entry['feature_id']) ? $entry['feature_id'] : $key;
            if (isset($module_overridden[$feature_id])) {
                continue;
            }
            $level = $entry['risk']['level'];
            if ($level === 'high') {
                $this->assertContains('安全', $entry['risk_tags'], "High risk feature '{$key}' should have '安全' in risk_tags");
            } elseif ($level === 'low') {
                $this->assertContains('谨慎', $entry['risk_tags'], "Low risk feature '{$key}' should have '谨慎' in risk_tags");
            }
        }
    }

    public function test_preset_tags_is_array_when_present(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();
        $found = false;

        foreach ($ui as $key => $entry) {
            if (isset($entry['preset_tags'])) {
                $found = true;
                $this->assertIsArray($entry['preset_tags'], "preset_tags for '{$key}' must be an array");
            }
        }
        if (!$found) {
            $this->markTestSkipped('No preset_tags found in uiSchema to test');
        }
    }

    public function test_depends_on_is_array_when_present(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();
        $found = false;

        foreach ($ui as $key => $entry) {
            if (isset($entry['depends_on'])) {
                $found = true;
                $this->assertIsArray($entry['depends_on'], "depends_on for '{$key}' must be an array");
            }
        }
        if (!$found) {
            $this->markTestSkipped('No depends_on found in uiSchema to test');
        }
    }

    public function test_ui_schema_no_private_fields_leak(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();
        $forbidden_keys = array('_option_key', '_flat', 'sanitize', 'default', 'min', 'max', 'enum');

        foreach ($ui as $key => $entry) {
            foreach ($forbidden_keys as $fk) {
                $this->assertArrayNotHasKey($fk, $entry, "UI entry '{$key}' must not contain private field '{$fk}'");
            }
        }
    }

    public function test_risk_tags_differ_from_preset_tags(): void {
        $ui = MaBox_Config_Schema::get_ui_schema();
        $found = false;

        foreach ($ui as $key => $entry) {
            if (isset($entry['risk_tags']) && isset($entry['preset_tags'])) {
                if (!empty($entry['risk_tags']) && !empty($entry['preset_tags'])) {
                    $found = true;
                    $overlap = array_intersect($entry['risk_tags'], $entry['preset_tags']);
                    $this->assertEmpty($overlap, "risk_tags and preset_tags for '{$key}' should not overlap");
                }
            }
        }
        if (!$found) {
            $this->markTestSkipped('No entries with both risk_tags and preset_tags to test');
        }
    }
}
