<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_cache_get_last_changed')) {
    function wp_cache_get_last_changed($group)
    {
        return isset($GLOBALS['_test_last_changed'][$group])
            ? $GLOBALS['_test_last_changed'][$group]
            : '0';
    }
}

final class DatabaseQueryHardeningWpdbStub
{
    public $posts = 'wp_posts';
    public $query_count = 0;

    public function prepare($query, ...$args)
    {
        return $query . '|' . implode('|', $args);
    }

    public function get_results($query)
    {
        $this->query_count++;

        return array(
            (object) array(
                'post_date'   => '2026-07-17',
                'post_author' => 7,
                'cnt'         => 2,
            ),
        );
    }
}

final class DatabaseQueryHardeningTest extends TestCase
{
    private $wpdb;

    protected function setUp(): void
    {
        parent::setUp();
        $this->wpdb = isset($GLOBALS['wpdb']) ? $GLOBALS['wpdb'] : null;
        $GLOBALS['_test_cache_store'] = array();
        $GLOBALS['_test_last_changed'] = array('posts' => 'posts-v1');
    }

    protected function tearDown(): void
    {
        $GLOBALS['wpdb'] = $this->wpdb;
        parent::tearDown();
    }

    public function test_article_aggregate_cache_is_versioned_by_posts_last_changed(): void
    {
        $wpdb = new DatabaseQueryHardeningWpdbStub();
        $GLOBALS['wpdb'] = $wpdb;

        $expected = array(array('17', 2));
        $this->assertSame(
            $expected,
            Npcink_Toolbox_Census_Single::get_article_counts(array('2026-07-17'), array(7))
        );
        $this->assertSame(
            $expected,
            Npcink_Toolbox_Census_Single::get_article_counts(array('2026-07-17'), array(7))
        );
        $this->assertSame(1, $wpdb->query_count, 'Unchanged posts version should reuse the aggregate.');

        $GLOBALS['_test_last_changed']['posts'] = 'posts-v2';
        $this->assertSame(
            $expected,
            Npcink_Toolbox_Census_Single::get_article_counts(array('2026-07-17'), array(7))
        );
        $this->assertSame(2, $wpdb->query_count, 'A posts change must invalidate the aggregate key.');
    }

    public function test_today_user_count_uses_wp_user_query_date_contract(): void
    {
        $source = $this->source('includes/class-npcink-toolbox-tool.php');

        $this->assertStringContainsString('$today_users              = new WP_User_Query(array(', $source);
        $this->assertStringContainsString("'date_query'  => array(", $source);
        $this->assertStringContainsString("'count_total' => true", $source);
        $this->assertStringContainsString('$today_users->get_total()', $source);
        $this->assertStringNotContainsString('$wpdb', $source);
    }

    public function test_realtime_comment_checks_have_only_precise_db_suppressions(): void
    {
        $files = array(
            'admin/partials/page/comment/only_comment_once.php',
            'admin/partials/domestic/comment_security/index.php',
        );

        foreach ($files as $file) {
            $source = $this->source($file);
            $this->assertSame(1, substr_count($source, 'phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching'), $file);
            $this->assertStringContainsString('must read current comment rows; persistent cache could be stale.', $source, $file);
            $this->assertStringNotContainsString('phpcs:disable', $source, $file);
            $this->assertStringNotContainsString('wp_cache_set(', $source, $file);
        }
    }

    public function test_uninstall_dynamic_prefix_queries_are_bounded(): void
    {
        $source = $this->source('uninstall.php');

        $this->assertSame(2, substr_count($source, '$wpdb->esc_like('));
        $this->assertStringContainsString("array('npcink_site_toolbox_category_title_', 'npcink_site_toolbox_category_keywords_')", $source);
        $this->assertStringContainsString("array('_transient_npcink_site_toolbox_', '_transient_timeout_npcink_site_toolbox_')", $source);
        $this->assertStringNotContainsString('DROP TABLE', $source);
        $this->assertStringNotContainsString('link_counter', $source);
        $this->assertStringNotContainsString('%i', $source, 'WordPress 6.0 does not support identifier placeholders.');
        $this->assertSame(2, substr_count($source, '// phpcs:ignore WordPress.DB.'));
        $this->assertStringNotContainsString('phpcs:disable', $source);
    }

    public function test_aggregate_query_uses_a_single_precise_direct_query_suppression(): void
    {
        $source = $this->source('admin/partials/function/auxiliary/census-single.php');

        $this->assertStringContainsString("wp_cache_get_last_changed('posts')", $source);
        $this->assertStringContainsString("wp_cache_get(\$cache_key, 'npcink_site_toolbox')", $source);
        $this->assertStringContainsString("wp_cache_set(\$cache_key, \$results, 'npcink_site_toolbox', HOUR_IN_SECONDS)", $source);
        $this->assertSame(1, substr_count($source, 'phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery'));
        $this->assertStringNotContainsString('phpcs:disable', $source);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/' . $relativePath);
        $this->assertIsString($source);

        return $source;
    }
}
