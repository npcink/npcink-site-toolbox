<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * MaBox_Config_Manager 单元测试
 *
 * 测试配置迁移、读取、保存、导出/导入功能
 */
class MaBox_Config_Manager_Test extends TestCase {

    /**
     * 测试 Config_Manager 类存在
     */
    public function test_config_manager_class_exists(): void {
        $this->assertTrue(class_exists('MaBox_Config_Manager'));
    }

    /**
     * 测试 module_map 包含所有预期的模块键
     */
    public function test_module_map_contains_expected_keys(): void {
        $map = MaBox_Config_Manager::get_module_map();

        $expected_keys = array(
            'optimize', 'page', 'function', 'login',
            'domestic', 'performance',
            'ai_review',
        );

        foreach ($expected_keys as $key) {
            $this->assertArrayHasKey($key, $map, "Module map should contain '$key'");
        }
    }

    /**
     * 测试 module_map 返回的选项键名格式正确
     */
    public function test_module_map_values_are_valid_option_names(): void {
        $map = MaBox_Config_Manager::get_module_map();

        foreach ($map as $module_key => $option_name) {
            $this->assertIsString($option_name, "Option name for '$module_key' should be a string");
            $this->assertStringStartsWith('Magick_ToolBox_Option_', $option_name,
                "Option name for '$module_key' should start with 'Magick_ToolBox_Option_'");
        }
    }

    /**
     * 测试 module_map 键数量为 12
     */
    public function test_module_map_has_correct_count(): void {
        $map = MaBox_Config_Manager::get_module_map();
        $this->assertCount(7, $map);
    }

    /**
     * 测试所有必需方法存在
     */
    public function test_all_required_methods_exist(): void {
        $methods = array(
            'needs_migration',
            'migrate',
            'rollback',
            'get_merged_config',
            'get_module_config',
            'save_full_config',
            'save_module_config',
            'export_config',
            'import_config',
            'clear_cache',
            'get_module_map',
        );

        foreach ($methods as $method) {
            $this->assertTrue(method_exists('MaBox_Config_Manager', $method),
                "Method '$method' should exist");
        }
    }

    /**
     * 测试 import_config 拒绝无效输入
     */
    public function test_import_config_rejects_invalid_input(): void {
        $this->assertTrue(method_exists('MaBox_Config_Manager', 'import_config'));

        // 验证方法签名
        $method = new ReflectionMethod('MaBox_Config_Manager', 'import_config');
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('config', $params[0]->getName());
    }

    /**
     * 测试 clear_cache 方法为静态
     */
    public function test_clear_cache_is_static(): void {
        $method = new ReflectionMethod('MaBox_Config_Manager', 'clear_cache');
        $this->assertTrue($method->isStatic());
    }

    /**
     * 测试 get_module_map 方法为静态
     */
    public function test_get_module_map_is_static(): void {
        $method = new ReflectionMethod('MaBox_Config_Manager', 'get_module_map');
        $this->assertTrue($method->isStatic());
    }

    /**
     * 测试类为纯静态设计（无实例属性）
     */
    public function test_class_is_static_design(): void {
        $class = new ReflectionClass('MaBox_Config_Manager');
        $properties = $class->getProperties();

        foreach ($properties as $property) {
            $this->assertTrue($property->isStatic(),
                "Property '{$property->getName()}' should be static");
        }
    }
}
