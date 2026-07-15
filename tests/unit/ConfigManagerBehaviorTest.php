<?php

use PHPUnit\Framework\TestCase;

/**
 * MaBox_Config_Manager 行为测试
 *
 * 验证配置合并、拆分、迁移等核心逻辑。
 */
class ConfigManagerBehaviorTest extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        // 每个用例前清理静态缓存，避免测试间污染
        $reflection = new ReflectionClass('MaBox_Config_Manager');
        $property = $reflection->getProperty('merged_cache');
        $property->setValue(null, null);

        // 清理全局 option store
        $GLOBALS['_test_option_store'] = array();
        $GLOBALS['_test_update_option_failures'] = array();
    }

    /**
     * 测试空配置返回空数组
     */
    public function test_empty_config_returns_empty_array(): void {
        $method = new ReflectionMethod('MaBox_Config_Manager', 'get_merged_config');
        $result = $method->invoke(null);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * 测试合并多模块配置
     */
    public function test_merge_multiple_modules(): void {
        $GLOBALS['_test_option_store'] = array(
            'Magick_ToolBox_Option_Optimize' => array('enabled' => true, 'cdn' => array('enabled' => false)),
            'Magick_ToolBox_Option_Page'     => array('comment' => array('sensitive_words' => true)),
            'Magick_ToolBox_Option_Function' => array('maintenance' => array('enabled' => false)),
        );

        $method = new ReflectionMethod('MaBox_Config_Manager', 'get_merged_config');
        $result = $method->invoke(null);

        $this->assertArrayHasKey('optimize', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayHasKey('function', $result);
        $this->assertTrue($result['optimize']['enabled']);
    }

    /**
     * 测试获取单个模块配置
     */
    public function test_get_single_module_config(): void {
        $GLOBALS['_test_option_store'] = array(
            'Magick_ToolBox_Option_Optimize' => array('enabled' => true),
        );

        $method = new ReflectionMethod('MaBox_Config_Manager', 'get_module_config');
        
        $result = $method->invoke(null, 'optimize');
        $this->assertTrue($result['enabled']);

        $result = $method->invoke(null, 'nonexistent');
        $this->assertEmpty($result);
    }

    /**
     * 测试配置缓存（单次请求内）
     */
    public function test_config_cache_within_request(): void {
        $GLOBALS['_test_option_store'] = array('Magick_ToolBox_Option_Optimize' => array('enabled' => true));

        $method = new ReflectionMethod('MaBox_Config_Manager', 'get_merged_config');
        
        // 第一次调用
        $result1 = $method->invoke(null);
        // 第二次调用应该使用缓存
        $result2 = $method->invoke(null);

        $this->assertSame($result1, $result2);
        $this->assertIsArray($result1);
    }

    public function test_same_value_is_not_treated_as_save_failure(): void {
        $current = array('enabled' => true);
        $GLOBALS['_test_option_store']['Magick_ToolBox_Option_Optimize'] = $current;

        $result = MaBox_Config_Manager::save_full_config(array('optimize' => $current));

        $this->assertTrue($result['success']);
        $this->assertSame(array('optimize'), $result['saved_modules']);
    }

    public function test_cross_module_failure_rolls_back_changed_modules(): void {
        $GLOBALS['_test_option_store'] = array(
            'Magick_ToolBox_Option_Optimize' => array('enabled' => false),
            'Magick_ToolBox_Option_Page' => array('feature' => array('reading_progress' => false)),
        );
        $GLOBALS['_test_update_option_failures']['Magick_ToolBox_Option_Page'] = true;

        $result = MaBox_Config_Manager::save_full_config(array(
            'optimize' => array('enabled' => true),
            'page' => array('feature' => array('reading_progress' => true)),
        ));

        $this->assertFalse($result['success']);
        $this->assertSame(array('page'), $result['failed_modules']);
        $this->assertSame(array('enabled' => false), $GLOBALS['_test_option_store']['Magick_ToolBox_Option_Optimize']);
        $this->assertSame(
            array('feature' => array('reading_progress' => false)),
            $GLOBALS['_test_option_store']['Magick_ToolBox_Option_Page']
        );
    }

    public function test_cross_module_failure_removes_newly_created_module_option(): void {
        $GLOBALS['_test_option_store'] = array(
            'Magick_ToolBox_Option_Page' => array('feature' => array('reading_progress' => false)),
        );
        $GLOBALS['_test_update_option_failures']['Magick_ToolBox_Option_Page'] = true;

        $result = MaBox_Config_Manager::save_full_config(array(
            'optimize' => array('enabled' => true),
            'page' => array('feature' => array('reading_progress' => true)),
        ));

        $this->assertFalse($result['success']);
        $this->assertArrayNotHasKey('Magick_ToolBox_Option_Optimize', $GLOBALS['_test_option_store']);
    }
}
