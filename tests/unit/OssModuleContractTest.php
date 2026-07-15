<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class OssModuleContractTest extends TestCase
{
    private static $pluginDir;

    public static function setUpBeforeClass(): void
    {
        self::$pluginDir = dirname(__DIR__, 2);

        require_once self::$pluginDir . '/includes/interface-mabox-module.php';
        require_once self::$pluginDir . '/admin/partials/performance/oss/index.php';
    }

    public function test_oss_module_implements_module_interface(): void
    {
        $this->assertTrue(
            is_subclass_of('MaBox_Performance_Oss', 'MaBox_Module_Interface'),
            'MaBox_Performance_Oss should implement MaBox_Module_Interface'
        );
    }

    public function test_oss_module_can_run_with_default_disabled_config(): void
    {
        $this->assertNull(MaBox_Performance_Oss::run());
    }
}
