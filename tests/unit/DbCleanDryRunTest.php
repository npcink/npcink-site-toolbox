<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ARRAY_A')) {
    define('ARRAY_A', 'ARRAY_A');
}

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

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        private $params;

        public function __construct(array $params = array())
        {
            $this->params = $params;
        }

        public function get_json_params()
        {
            return $this->params;
        }
    }
}

/**
 * Npcink_Toolbox_Performance_Db_Clean dry-run 模式测试
 *
 * 测试数据库清理的 dry-run 行为
 */
class Npcink_Toolbox_Db_Clean_DryRun_Test extends TestCase {

    protected function setUp(): void {
        parent::setUp();
        $GLOBALS['_test_user_meta_store'] = array();
    }

    /**
     * 测试 DB Clean 类存在
     */
    public function test_class_exists(): void {
        $this->assertTrue(class_exists('Npcink_Toolbox_Performance_Db_Clean'));
    }

    /**
     * 测试 ajax_preview 方法存在
     */
    public function test_ajax_preview_exists(): void {
        $this->assertTrue(method_exists('Npcink_Toolbox_Performance_Db_Clean', 'ajax_preview'));
    }

    /**
     * 测试 ajax_clean 方法存在
     */
    public function test_ajax_clean_exists(): void {
        $this->assertTrue(method_exists('Npcink_Toolbox_Performance_Db_Clean', 'ajax_clean'));
    }

    /**
     * 测试 ajax_stats 方法存在
     */
    public function test_ajax_stats_exists(): void {
        $this->assertTrue(method_exists('Npcink_Toolbox_Performance_Db_Clean', 'ajax_stats'));
    }

    /**
     * 测试 REST API 路由中 dry_run 默认为 true
     */
    public function test_rest_route_has_dry_run_default(): void {
        $admin_file = dirname(__FILE__) . '/../../admin/class-npcink-toolbox-admin.php';
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
        $admin_file = dirname(__FILE__) . '/../../admin/class-npcink-toolbox-admin.php';
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

        $this->assertStringContainsString('performanceApi.cleanDb(type, preview.preview_token)', $frontend_content);
        $this->assertStringContainsString('preview_token: previewToken', $api_content);
    }

    public function test_db_clean_reads_json_from_rest_request(): void {
        $db_clean_file = dirname(__FILE__) . '/../../admin/partials/performance/db_clean/index.php';
        $content = file_get_contents($db_clean_file);

        $this->assertStringContainsString('ajax_preview(\\WP_REST_Request $request)', $content);
        $this->assertStringContainsString('ajax_clean(\\WP_REST_Request $request)', $content);
        $this->assertStringContainsString('$request->get_json_params()', $content);
        $this->assertStringNotContainsString('rest_get_request()', $content);
    }

    public function test_db_clean_frontend_uses_per_type_preview_gating(): void {
        $frontend_file = dirname(__FILE__) . '/../../vite/admin/src/components/performance/db_clean.tsx';
        $content = file_get_contents($frontend_file);

        $this->assertStringContainsString('previewData[', $content);
        $this->assertStringContainsString('!previewData[', $content);
        $this->assertStringNotContainsString('handlePreview("all")', $content);
        $this->assertStringNotContainsString('handleClean("all")', $content);
        $this->assertStringNotContainsString('previewData["all"]', $content);
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

        $allowed_types = "\$allowed_types = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'pending', 'trash');";
        $this->assertStringContainsString($allowed_types, $content);
        $this->assertStringNotContainsString("'optimize', 'all', 'pending'", $content);
    }

    public function test_rest_routes_reject_bulk_all_type(): void {
        $admin_file = dirname(__FILE__) . '/../../admin/class-npcink-toolbox-admin.php';
        $content = file_get_contents($admin_file);

        $allowed_types = "\$allowed = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'pending', 'trash');";
        $this->assertSame(2, substr_count($content, $allowed_types));
        $this->assertStringNotContainsString("'optimize', 'all', 'pending'", $content);
    }

    public function test_destructive_cleanup_requires_one_time_preview_token(): void {
        $db_clean_file = dirname(__FILE__) . '/../../admin/partials/performance/db_clean/index.php';
        $content = file_get_contents($db_clean_file);

        $this->assertStringContainsString("private const PREVIEW_TTL = 300", $content);
        $this->assertStringContainsString('issue_preview_token($type, $preview)', $content);
        $this->assertStringContainsString('consume_preview_token($type, $preview_token)', $content);
        $this->assertStringContainsString("'rest_db_preview_conflict'", $content);
        $this->assertStringContainsString('mark_preview_consumed($token', $content);
        $this->assertLessThan(
            strpos($content, "if (class_exists('Npcink_Toolbox_Audit_Logger'))"),
            strpos($content, 'consume_preview_token($type, $preview_token)')
        );
    }

    public function test_cleanup_without_preview_token_is_rejected_before_database_queries(): void {
        $wpdb = new DbCleanWpdbStub(array());
        $GLOBALS['wpdb'] = $wpdb;

        $response = Npcink_Toolbox_Performance_Db_Clean::ajax_clean(new WP_REST_Request(array(
            'type' => 'revisions',
            'dry_run' => false,
        )));

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('rest_db_preview_required', $response->get_error_code());
        $this->assertSame(409, $response->get_error_data()['status']);
        $this->assertSame(0, $wpdb->row_queries);
        $this->assertSame(0, $wpdb->column_queries);
    }

    public function test_changed_preview_is_rejected_and_consumed_before_cleanup(): void {
        $wpdb = new DbCleanWpdbStub(array(
            $this->countRow(12),
            $this->countRow(13),
        ));
        $GLOBALS['wpdb'] = $wpdb;

        $preview = Npcink_Toolbox_Performance_Db_Clean::ajax_preview(new WP_REST_Request(array(
            'type' => 'revisions',
        )));
        $token = $preview['data']['preview_token'];
        $response = Npcink_Toolbox_Performance_Db_Clean::ajax_clean(new WP_REST_Request(array(
            'type' => 'revisions',
            'dry_run' => false,
            'preview_token' => $token,
        )));

        $this->assertInstanceOf(WP_Error::class, $response);
        $this->assertSame('rest_db_preview_conflict', $response->get_error_code());
        $this->assertSame(409, $response->get_error_data()['status']);
        $this->assertSame(2, $wpdb->row_queries);
        $this->assertSame(0, $wpdb->column_queries, 'Conflict must fail before deletion queries');
        $consumed = get_user_meta(0, 'npcink_site_toolbox_consumed_db_previews', true);
        $this->assertArrayHasKey(hash('sha256', $token), $consumed);
    }

    public function test_preview_token_is_one_time_even_after_successful_zero_item_cleanup(): void {
        $wpdb = new DbCleanWpdbStub(array(
            $this->countRow(0),
            $this->countRow(0),
        ));
        $GLOBALS['wpdb'] = $wpdb;

        $preview = Npcink_Toolbox_Performance_Db_Clean::ajax_preview(new WP_REST_Request(array(
            'type' => 'revisions',
        )));
        $token = $preview['data']['preview_token'];
        $request = new WP_REST_Request(array(
            'type' => 'revisions',
            'dry_run' => false,
            'preview_token' => $token,
        ));

        $success = Npcink_Toolbox_Performance_Db_Clean::ajax_clean($request);
        $replay = Npcink_Toolbox_Performance_Db_Clean::ajax_clean($request);

        $this->assertIsArray($success);
        $this->assertTrue($success['success']);
        $this->assertSame(0, $success['data']['deleted']);
        $this->assertInstanceOf(WP_Error::class, $replay);
        $this->assertSame('rest_db_preview_expired', $replay->get_error_code());
    }

    private function countRow(int $revisions): array {
        return array(
            'revisions' => $revisions,
            'drafts' => 0,
            'spam' => 0,
            'transients' => 0,
            'pending' => 0,
            'trash' => 0,
        );
    }

}

class DbCleanWpdbStub
{
    public $posts = 'wp_posts';
    public $comments = 'wp_comments';
    public $options = 'wp_options';
    public $prefix = 'wp_';
    public $row_queries = 0;
    public $column_queries = 0;
    private $rows;

    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function esc_like($value)
    {
        return $value;
    }

    public function prepare($query, ...$args)
    {
        return $query;
    }

    public function get_row($query, $output)
    {
        ++$this->row_queries;
        return array_shift($this->rows) ?: array();
    }

    public function get_col($query)
    {
        ++$this->column_queries;
        return array();
    }
}
