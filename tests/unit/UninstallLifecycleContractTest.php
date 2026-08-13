<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class UninstallLifecycleContractTest extends TestCase
{
    public function test_uninstall_cleans_runtime_attachment_state_but_preserves_webp_recovery_data(): void
    {
        $source = $this->source('uninstall.php');

        $this->assertStringContainsString(
            "delete_metadata('post', 0, '_npcink_site_toolbox_oss_offloaded', '', true);",
            $source
        );
        $this->assertStringContainsString(
            "delete_metadata('post', 0, '_npcink_site_toolbox_webp_lock', '', true);",
            $source
        );
        $this->assertStringNotContainsString(
            "delete_metadata('post', 0, '_npcink_site_toolbox_webp_backup_v1'",
            $source
        );
        $this->assertStringNotContainsString('wp_delete_file(', $source);
        $this->assertStringNotContainsString('delete_post(', $source);
    }

    public function test_public_and_built_in_help_explain_the_uninstall_media_boundary(): void
    {
        $readme = $this->source('readme.txt');
        $privacy = $this->source('admin/partials/privacy/index.php');
        $faq = $this->source('docs-site/guide/faq.md');

        foreach (array($readme, $privacy, $faq) as $source) {
            $this->assertStringContainsString('WebP', $source);
        }
        $this->assertStringContainsString('does not delete or rewrite media files', $readme);
        $this->assertStringContainsString('uninstalling', $readme);
        $this->assertStringContainsString("'uninstall'", $privacy);
        $this->assertStringContainsString('卸载', $privacy);
        $this->assertStringContainsString('卸载不会删除或改写媒体文件', $faq);
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/' . $relativePath);
        $this->assertIsString($source, $relativePath);

        return $source;
    }
}
