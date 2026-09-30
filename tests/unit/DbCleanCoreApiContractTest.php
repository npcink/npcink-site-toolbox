<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class DbCleanCoreApiContractTest extends TestCase
{
    public function test_bulk_deletes_use_bounded_id_queries_and_core_apis(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('private const BATCH_SIZE                = 100;', $source);
        $this->assertStringContainsString('LIMIT %d', $source);
        $this->assertStringContainsString('ID > %d', $source);
        $this->assertStringContainsString('comment_ID > %d', $source);
        $this->assertStringContainsString('wp_delete_post_revision($post_id)', $source);
        $this->assertStringContainsString('wp_delete_post($post_id, true)', $source);
        $this->assertStringContainsString('wp_delete_comment($comment_id, true)', $source);
        $this->assertStringNotContainsString('DELETE FROM', $source);
    }

    public function test_fresh_counts_are_merged_without_persistent_cache_workarounds(): void
    {
        $source = $this->source();

        $this->assertSame(1, substr_count($source, '$wpdb->get_row('));
        $this->assertStringContainsString('one merged query replaces repeated uncached counts', $source);
        $this->assertStringContainsString("SHOW TABLE STATUS LIKE %s", $source);
        $this->assertStringNotContainsString('information_schema', $source);
        $this->assertStringNotContainsString('wp_cache_set(', $source);
        $this->assertStringNotContainsString('set_transient(', $source);
        $this->assertStringContainsString("\$wpdb->esc_like('_transient_timeout_')", $source);
        $this->assertStringContainsString("\$wpdb->esc_like('_site_transient_timeout_')", $source);
    }

    public function test_transient_cleanup_uses_cache_aware_apis_and_handles_timeout_rows(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('delete_transient($key)', $source);
        $this->assertStringContainsString('delete_site_transient($key)', $source);
        $this->assertStringContainsString('delete_option($option_prefix . $key)', $source);
        $this->assertStringContainsString('delete_option($timeout_prefix . $key)', $source);
        $this->assertStringContainsString('CAST(option_value AS UNSIGNED) < %d', $source);

        $method = new ReflectionMethod(Npcink_Toolbox_Performance_Db_Clean::class, 'parse_transient_option_name');
        $method->setAccessible(true);
        $this->assertSame(
            array('key' => 'feed_cache', 'site' => false),
            $method->invoke(null, '_transient_timeout_feed_cache')
        );
        $this->assertSame(
            array('key' => 'update_plugins', 'site' => true),
            $method->invoke(null, '_site_transient_update_plugins')
        );
        $this->assertNull($method->invoke(null, '_transient_'));
    }

    public function test_transient_preview_and_cleanup_target_expired_timeout_rows_only(): void
    {
        $source = $this->source();
        $delete_start = strpos($source, 'private static function delete_transients()');
        $delete_end = strpos($source, 'private static function parse_transient_option_name', $delete_start);

        $this->assertIsInt($delete_start);
        $this->assertIsInt($delete_end);
        $delete_source = substr($source, $delete_start, $delete_end - $delete_start);

        $this->assertGreaterThanOrEqual(2, substr_count($source, 'CAST(option_value AS UNSIGNED) < %d'));
        $this->assertStringContainsString("\$wpdb->esc_like('_transient_timeout_')", $delete_source);
        $this->assertStringContainsString("\$wpdb->esc_like('_site_transient_timeout_')", $delete_source);
        $this->assertStringNotContainsString("\$wpdb->esc_like('_transient_') . '%'", $delete_source);
        $this->assertStringNotContainsString("\$wpdb->esc_like('_site_transient_') . '%'", $delete_source);
    }

    public function test_optimize_table_names_are_prefix_scoped_and_allowlisted(): void
    {
        $source = $this->source();

        $this->assertStringContainsString("\$wpdb->prepare('SHOW TABLES LIKE %s'", $source);
        $this->assertStringContainsString("preg_match('/\\A[A-Za-z0-9_]+\\z/'", $source);
        $this->assertStringContainsString("\$wpdb->query('OPTIMIZE TABLE `' . \$table_name . '`')", $source);

        $method = new ReflectionMethod(Npcink_Toolbox_Performance_Db_Clean::class, 'is_safe_table_name');
        $method->setAccessible(true);
        $this->assertTrue($method->invoke(null, 'wp_posts', 'wp_'));
        $this->assertFalse($method->invoke(null, 'other_posts', 'wp_'));
        $this->assertFalse($method->invoke(null, 'wp_posts;DROP_TABLE', 'wp_'));
        $this->assertFalse($method->invoke(null, 'wp-posts', 'wp_'));
    }

    public function test_direct_queries_have_line_level_reasons_without_blanket_suppression(): void
    {
        $source = $this->source();

        $this->assertStringNotContainsString('phpcs:disable', $source);
        $this->assertStringNotContainsString('phpcs:ignoreFile', $source);
        $this->assertStringNotContainsString('error_log(', $source);
        $this->assertGreaterThanOrEqual(
            6,
            substr_count($source, 'phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching')
        );
    }

    private function source(): string
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/admin/partials/performance/db_clean/index.php'
        );
        $this->assertIsString($source);

        return $source;
    }
}
