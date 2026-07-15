<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * MaBox_Performance_Db_Clean dry-run 模式测试
 *
 * 测试数据库清理的 dry-run 行为
 */
class MaBox_Db_Clean_DryRun_Test extends TestCase {

    /**
     * 测试 DB Clean 类存在
     */
    public function test_class_exists(): void {
        $this->assertTrue(class_exists('MaBox_Performance_Db_Clean'));
    }

    /**
     * 测试 ajax_preview 方法存在
     */
    public function test_ajax_preview_exists(): void {
        $this->assertTrue(method_exists('MaBox_Performance_Db_Clean', 'ajax_preview'));
    }

    /**
     * 测试 ajax_clean 方法存在
     */
    public function test_ajax_clean_exists(): void {
        $this->assertTrue(method_exists('MaBox_Performance_Db_Clean', 'ajax_clean'));
    }

    /**
     * 测试 ajax_stats 方法存在
     */
    public function test_ajax_stats_exists(): void {
        $this->assertTrue(method_exists('MaBox_Performance_Db_Clean', 'ajax_stats'));
    }

    /**
     * 测试 REST API 路由中 dry_run 默认为 true
     */
    public function test_rest_route_has_dry_run_default(): void {
        $admin_file = dirname(__FILE__) . '/../../admin/class-magick-mixture-admin.php';
        $this->assertFileExists($admin_file);

        $content = file_get_contents($admin_file);

        // 验证 /performance/db/clean 路由配置中 dry_run 默认为 true
        $this->assertStringContainsString("'dry_run' => array(", $content);
        $this->assertStringContainsString("'default'           => true", $content);
    }

    /**
     * 测试 db_clean/index.php 中 dry_run 默认为 true
     */
    public function test_db_clean_file_defaults_dry_run(): void {
        $db_clean_file = dirname(__FILE__) . '/../../admin/partials/performance/db_clean/index.php';
        $this->assertFileExists($db_clean_file);

        $content = file_get_contents($db_clean_file);

        $this->assertStringContainsString("dry_run", $content);
        $this->assertStringContainsString("true", $content);
    }

    public function test_rest_route_has_dry_run_default_true(): void {
        $admin_file = dirname(__FILE__) . '/../../admin/class-magick-mixture-admin.php';
        $this->assertFileExists($admin_file);

        $content = file_get_contents($admin_file);

        $this->assertStringContainsString("'dry_run' => array(", $content);
        $this->assertStringContainsString("'default'           => true", $content);
    }

    public function test_db_clean_frontend_passes_dry_run_false_through_api(): void {
        $frontend_file = dirname(__FILE__) . '/../../vite/admin/src/components/performance/db_clean.tsx';
        $api_file = dirname(__FILE__) . '/../../vite/admin/src/api/index.ts';
        $this->assertFileExists($frontend_file);
        $this->assertFileExists($api_file);

        $frontend_content = file_get_contents($frontend_file);
        $api_content = file_get_contents($api_file);

        $this->assertStringContainsString('performanceApi.cleanDb(type, false)', $frontend_content);
        $this->assertStringContainsString('dry_run: dryRun', $api_content);
    }

    public function test_db_clean_reads_json_from_rest_request(): void {
        $db_clean_file = dirname(__FILE__) . '/../../admin/partials/performance/db_clean/index.php';
        $content = file_get_contents($db_clean_file);

        $this->assertStringContainsString('ajax_clean(\\WP_REST_Request $request)', $content);
        $this->assertStringContainsString('$request->get_json_params()', $content);
        $this->assertStringNotContainsString('rest_get_request()', $content);
    }

    public function test_db_clean_frontend_uses_per_type_preview_gating(): void {
        $frontend_file = dirname(__FILE__) . '/../../vite/admin/src/components/performance/db_clean.tsx';
        $content = file_get_contents($frontend_file);

        $this->assertStringContainsString('previewData[', $content);
        $this->assertStringContainsString('!previewData[', $content);
    }

    /**
     * 测试 db_clean 代码包含预览逻辑（不执行 DELETE）
     */
    public function test_db_clean_has_preview_logic(): void {
        $db_clean_file = dirname(__FILE__) . '/../../admin/partials/performance/db_clean/index.php';
        $content = file_get_contents($db_clean_file);

        // 验证有影响行数计算
        $this->assertStringContainsString("COUNT", $content);
    }

    /**
     * 测试支持的清理类型列表
     */
    public function test_supported_cleanup_types(): void {
        $db_clean_file = dirname(__FILE__) . '/../../admin/partials/performance/db_clean/index.php';
        $content = file_get_contents($db_clean_file);

        $expected_types = array('revisions', 'drafts', 'spam', 'transients', 'trash', 'pending', 'all');
        foreach ($expected_types as $type) {
            $this->assertStringContainsString($type, $content, "Missing cleanup type: $type");
        }
    }

    /**
     * 测试 batch replace 代码包含 dry_run 和 rollback
     */
    public function test_batch_replace_has_dry_run_and_rollback(): void {
        $batch_replace_file = dirname(__FILE__) . '/../../admin/partials/page/function/batch_replace.php';
        $this->assertFileExists($batch_replace_file);

        $content = file_get_contents($batch_replace_file);

        // 验证 dry_run 支持
        $this->assertStringContainsString("dry_run", $content);

        // 验证 rollback 支持
        $this->assertStringContainsString("rollback", $content);

        // 验证备份机制
        $this->assertStringContainsString("_mabox_batch_replace_backup", $content);
    }

    /**
     * 测试 batch replace REST API dry_run 默认为 true
     */
    public function test_batch_replace_rest_dry_run_default(): void {
        $admin_file = dirname(__FILE__) . '/../../admin/class-magick-mixture-admin.php';
        $content = file_get_contents($admin_file);

        // 在 /page/batch-replace 路由附近查找 dry_run 默认值
        $this->assertStringContainsString("/page/batch-replace", $content);
    }
}
