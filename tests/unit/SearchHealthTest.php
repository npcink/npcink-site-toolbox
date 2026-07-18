<?php
defined('ABSPATH') || exit;

use PHPUnit\Framework\TestCase;

require_once dirname(__FILE__) . '/../../includes/class-npcink-toolbox-search-health.php';

class SearchHealthTest extends TestCase
{
    private static $method_get_summary;
    private static $method_log;
    private static $method_normalize;

    public static function setUpBeforeClass(): void
    {
        self::$method_get_summary = new ReflectionMethod('Npcink_Toolbox_Search_Health', 'get_summary');
        self::$method_log = new ReflectionMethod('Npcink_Toolbox_Search_Health', 'log_search_term');
        self::$method_normalize = new ReflectionMethod('Npcink_Toolbox_Search_Health', 'normalize_entry');
    }

    protected function setUp(): void
    {
        global $_test_option_store;
        $_test_option_store = array();
    }

    public function test_class_exists(): void
    {
        $this->assertTrue(class_exists('Npcink_Toolbox_Search_Health'));
    }

    public function test_empty_data_summary(): void
    {
        $summary = self::$method_get_summary->invoke(null, 30);
        $this->assertIsArray($summary);
        $this->assertEquals(30, $summary['range_days']);
        $this->assertEquals(0, $summary['total_searches']);
        $this->assertEquals(0, $summary['unique_terms']);
        $this->assertEmpty($summary['top_terms']);
        $this->assertEmpty($summary['no_result_terms']);
        $this->assertEmpty($summary['suspicious_terms']);
        $this->assertIsArray($summary['recommendations']);
    }

    public function test_log_and_aggregate(): void
    {
        self::$method_log->invoke(null, 'wordpress', true);
        self::$method_log->invoke(null, 'wordpress', true);
        self::$method_log->invoke(null, 'php', true);

        $summary = self::$method_get_summary->invoke(null, 30);
        $this->assertEquals(3, $summary['total_searches']);
        $this->assertEquals(2, $summary['unique_terms']);
        $this->assertEquals('wordpress', $summary['top_terms'][0]['term']);
        $this->assertEquals(2, $summary['top_terms'][0]['count']);
    }

    public function test_no_result_tracking(): void
    {
        self::$method_log->invoke(null, 'missing-page', false);
        self::$method_log->invoke(null, 'missing-page', false);
        self::$method_log->invoke(null, 'existing-page', true);

        $summary = self::$method_get_summary->invoke(null, 30);
        $this->assertEquals(3, $summary['total_searches']);
        $this->assertCount(1, $summary['no_result_terms']);
        $this->assertEquals('missing-page', $summary['no_result_terms'][0]['term']);
        $this->assertEquals(2, $summary['no_result_terms'][0]['no_result_count']);
    }

    public function test_old_data_migration(): void
    {
        global $_test_option_store;
        $today = current_time('Y-m-d');
        $_test_option_store['npcink_site_toolbox_search_log'] = array(
            $today => array(
                'legacy-term' => 5,
            ),
        );

        $summary = self::$method_get_summary->invoke(null, 30);
        $this->assertEquals(5, $summary['total_searches']);
        $this->assertCount(1, $summary['top_terms']);
        $this->assertEquals('legacy-term', $summary['top_terms'][0]['term']);
        $this->assertEquals(5, $summary['top_terms'][0]['count']);
        $this->assertEquals(0, $summary['top_terms'][0]['no_result_count']);
    }

    public function test_calendar_day_cutoff_is_independent_of_runtime_timezone_and_dst(): void
    {
        $original_timezone = date_default_timezone_get();

        try {
            foreach (array('America/New_York', 'Europe/Berlin', 'Asia/Shanghai') as $runtime_timezone) {
                date_default_timezone_set($runtime_timezone);

                $this->assertSame('2024-03-09', $this->calendarDateDaysAgo('2024-03-10', 1));
                $this->assertSame('2024-11-02', $this->calendarDateDaysAgo('2024-11-03', 1));
                $this->assertSame('2024-02-29', $this->calendarDateDaysAgo('2024-03-01', 1));
            }
        } finally {
            date_default_timezone_set($original_timezone);
        }
    }

    public function test_summary_includes_cutoff_day_and_excludes_previous_day(): void
    {
        global $_test_option_store;
        $today = current_time('Y-m-d');
        $cutoff_date = $this->calendarDateDaysAgo($today, 30);
        $expired_date = $this->calendarDateDaysAgo($today, 31);
        $_test_option_store['npcink_site_toolbox_search_log'] = array(
            $expired_date => array('expired-term' => array('count' => 5, 'no_result_count' => 0, 'last_searched_at' => '')),
            $cutoff_date => array('boundary-term' => array('count' => 2, 'no_result_count' => 0, 'last_searched_at' => '')),
        );

        $summary = self::$method_get_summary->invoke(null, 30);

        $this->assertEquals(2, $summary['total_searches']);
        $this->assertSame(array('boundary-term'), array_column($summary['top_terms'], 'term'));
    }

    public function test_prune_old_entries(): void
    {
        global $_test_option_store;
        $today = current_time('Y-m-d');
        $cutoff_date = $this->calendarDateDaysAgo($today, 30);
        $expired_date = $this->calendarDateDaysAgo($today, 31);
        $_test_option_store['npcink_site_toolbox_search_log'] = array(
            $expired_date => array('old-term' => array('count' => 10, 'no_result_count' => 0, 'last_searched_at' => '')),
            $cutoff_date => array('boundary-term' => array('count' => 4, 'no_result_count' => 0, 'last_searched_at' => '')),
            $today => array('new-term' => array('count' => 3, 'no_result_count' => 0, 'last_searched_at' => '')),
        );

        self::$method_log->invoke(null, 'trigger-prune', true);

        $log = $_test_option_store['npcink_site_toolbox_search_log'];
        $this->assertArrayNotHasKey($expired_date, $log);
        $this->assertArrayHasKey($cutoff_date, $log);
        $this->assertArrayHasKey($today, $log);
    }

    public function test_days_parameter_clamped(): void
    {
        $summary = self::$method_get_summary->invoke(null, 0);
        $this->assertEquals(1, $summary['range_days']);

        $summary = self::$method_get_summary->invoke(null, 999);
        $this->assertEquals(365, $summary['range_days']);
    }

    public function test_empty_term_rejected(): void
    {
        global $_test_option_store;
        self::$method_log->invoke(null, '', true);
        self::$method_log->invoke(null, '   ', true);

        $this->assertArrayNotHasKey('npcink_site_toolbox_search_log', $_test_option_store);
    }

    public function test_normalize_int_entry(): void
    {
        $entry = self::$method_normalize->invoke(null, 5);
        $this->assertEquals(5, $entry['count']);
        $this->assertEquals(0, $entry['no_result_count']);
    }

    public function test_normalize_array_entry(): void
    {
        $entry = self::$method_normalize->invoke(null, array('count' => 3, 'no_result_count' => 1, 'last_searched_at' => '2026-01-01'));
        $this->assertEquals(3, $entry['count']);
        $this->assertEquals(1, $entry['no_result_count']);
    }

    public function test_suspicious_detection(): void
    {
        for ($i = 0; $i < 150; $i++) {
            self::$method_log->invoke(null, 'bot-term', true);
        }
        self::$method_log->invoke(null, 'normal', true);

        $summary = self::$method_get_summary->invoke(null, 30);
        $suspicious_terms = array_column($summary['suspicious_terms'], 'term');
        $this->assertContains('bot-term', $suspicious_terms);
    }

    public function test_recommendations_include_search_limit(): void
    {
        $summary = self::$method_get_summary->invoke(null, 30);
        $rec_ids = array_column($summary['recommendations'], 'id');
        $this->assertContains('rec_search_rate_limit', $rec_ids);
    }

    public function test_recommendation_placeholders_have_translator_context(): void
    {
        $source = file_get_contents(dirname(__FILE__) . '/../../includes/class-npcink-toolbox-search-health.php');
        $this->assertIsString($source);

        $this->assertStringContainsString(
            "/* translators: %.0f: Percentage of searches with no results. */\n"
                . "                        'reason' => sprintf(__('超过 %.0f%% 的搜索无结果，建议为热门无结果词补充相关内容。'",
            $source
        );
        $this->assertStringContainsString(
            "/* translators: %.0f: Percentage of searches with no results. */\n"
                . "                        'reason' => sprintf(__('约 %.0f%% 的搜索无结果，可考虑补充相关内容。'",
            $source
        );
        $this->assertStringContainsString(
            "/* translators: %d: Number of suspicious high-frequency search terms. */\n"
                . "                    'reason' => sprintf(__('发现 %d 个异常高频搜索词",
            $source
        );
    }

    public function test_rest_summary_structure(): void
    {
        $this->assertTrue(method_exists('Npcink_Toolbox_Search_Health', 'rest_get_summary'));

        $request = new class {
            public function get_param($key) {
                return $key === 'days' ? 30 : null;
            }
        };
        $response = Npcink_Toolbox_Search_Health::rest_get_summary($request);

        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('data', $response);
        $this->assertEquals(30, $response['data']['range_days']);
    }

    public function test_rest_log_search_writes_with_keyword(): void
    {
        require_once dirname(__FILE__) . '/../../admin/partials/performance/search_enhance/index.php';
        $request = new class {
            public function get_param($key) {
                return 'test-keyword';
            }
        };
        Npcink_Toolbox_Performance_Search_Enhance::rest_log_search($request);

        $summary = self::$method_get_summary->invoke(null, 30);
        $this->assertEquals(1, $summary['total_searches']);
        $top = array_column($summary['top_terms'], 'term');
        $this->assertContains('test-keyword', $top);
    }

    public function test_rest_log_search_empty_keyword_not_written(): void
    {
        require_once dirname(__FILE__) . '/../../admin/partials/performance/search_enhance/index.php';
        global $_test_option_store;
        $_test_option_store = array();
        $request = new class {
            public function get_param($key) {
                return '';
            }
        };
        Npcink_Toolbox_Performance_Search_Enhance::rest_log_search($request);

        $this->assertArrayNotHasKey('npcink_site_toolbox_search_log', $_test_option_store);
    }

    public function test_rest_log_search_oversized_keyword_not_written(): void
    {
        require_once dirname(__FILE__) . '/../../admin/partials/performance/search_enhance/index.php';
        global $_test_option_store;
        $_test_option_store = array();
        $long_term = str_repeat('a', 201);
        $request = new class {
            public $keyword;
            public function get_param($key) {
                return $this->keyword;
            }
        };
        $request->keyword = $long_term;
        Npcink_Toolbox_Performance_Search_Enhance::rest_log_search($request);

        $this->assertArrayNotHasKey('npcink_site_toolbox_search_log', $_test_option_store);
    }

    public function test_increment_no_result_count_separate_from_total(): void
    {
        self::$method_log->invoke(null, 'wordpress', true);
        self::$method_log->invoke(null, 'wordpress', true);

        $method = new ReflectionMethod('Npcink_Toolbox_Search_Health', 'increment_no_result_count');
        $method->invoke(null, 'wordpress');

        $summary = self::$method_get_summary->invoke(null, 30);
        $this->assertEquals(2, $summary['total_searches']);
        $found = null;
        foreach ($summary['top_terms'] as $item) {
            if ($item['term'] === 'wordpress') {
                $found = $item;
            }
        }
        $this->assertNotNull($found);
        $this->assertEquals(2, $found['count']);
        $this->assertEquals(1, $found['no_result_count']);
    }

    public function test_hotwords_registers_no_result_hook_once(): void
    {
        $source = file_get_contents(dirname(__FILE__) . '/../../admin/partials/performance/search_enhance/index.php');

        $this->assertIsString($source);
        $this->assertSame(
            1,
            substr_count($source, "add_action('loop_no_results', array(__CLASS__, 'mark_no_result'));")
        );
    }

    public function test_increment_no_result_count_on_unlogged_term_skipped(): void
    {
        $method = new ReflectionMethod('Npcink_Toolbox_Search_Health', 'increment_no_result_count');
        $method->invoke(null, 'unlogged-term');

        $summary = self::$method_get_summary->invoke(null, 30);
        $this->assertEquals(0, $summary['total_searches']);
    }

    public function test_no_result_increment_empty_term_skipped(): void
    {
        self::$method_log->invoke(null, 'valid-term', true);
        $method = new ReflectionMethod('Npcink_Toolbox_Search_Health', 'increment_no_result_count');
        $method->invoke(null, '');

        $summary = self::$method_get_summary->invoke(null, 30);
        $this->assertEquals(1, $summary['total_searches']);
        foreach ($summary['top_terms'] as $item) {
            if ($item['term'] === 'valid-term') {
                $this->assertEquals(0, $item['no_result_count']);
            }
        }
    }

    private function calendarDateDaysAgo(string $site_date, int $days): string
    {
        $method = new ReflectionMethod('Npcink_Toolbox_Search_Health', 'calendar_date_days_ago');
        $method->setAccessible(true);

        return $method->invoke(null, $site_date, $days);
    }
}
