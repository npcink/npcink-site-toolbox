<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PerformanceQueryBoundariesTest extends TestCase
{
    /**
     * @dataProvider performanceModuleProvider
     */
    public function test_live_direct_queries_have_one_narrow_justification_each(
        string $relativePath
    ): void {
        $source = $this->source($relativePath);
        $directQueries = substr_count($source, '$wpdb->get_var(')
            + substr_count($source, '$wpdb->get_results(');
        $justifications = substr_count(
            $source,
            'phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching'
        );

        $this->assertGreaterThan(0, $directQueries);
        $this->assertSame($directQueries, $justifications);
        $this->assertStringNotContainsString('phpcs:disable', $source);
        $this->assertStringNotContainsString('set_transient(', $source);
        $this->assertStringNotContainsString('wp_cache_set(', $source);
    }

    public function performanceModuleProvider(): array
    {
        return array(
            'media health' => array('admin/partials/performance/media_health/index.php'),
            'SEO checker'  => array('admin/partials/performance/seo_checker/index.php'),
        );
    }

    public function test_media_file_scan_is_batched_bounded_and_honestly_reported(): void
    {
        $source = $this->source('admin/partials/performance/media_health/index.php');

        $this->assertStringContainsString('const ATTACHMENT_SCAN_BATCH_SIZE = 100;', $source);
        $this->assertStringContainsString('const ATTACHMENT_SCAN_LIMIT = 500;', $source);
        $this->assertStringContainsString('while ($checked < self::ATTACHMENT_SCAN_LIMIT)', $source);
        $this->assertStringContainsString("update_meta_cache('post', \$image_ids);", $source);
        $this->assertStringContainsString("'attachment_scan' => array(", $source);
        $this->assertStringContainsString("'sampled' => \$attachment_scan['sampled']", $source);
        $this->assertStringContainsString(
            "sprintf('超大图片（最近 %d 个附件抽样）', \$attachment_scan['checked'])",
            $source
        );
        $this->assertStringContainsString(
            "sprintf('中文文件名（最近 %d 个附件抽样）', \$attachment_scan['checked'])",
            $source
        );
        $this->assertStringNotContainsString('SELECT ID, guid', $source);
        $this->assertStringNotContainsString('SELECT ID, post_name', $source);
        $this->assertStringNotContainsString('post_name REGEXP', $source);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/' . $relativePath);
        $this->assertIsString($source);

        return $source;
    }
}
