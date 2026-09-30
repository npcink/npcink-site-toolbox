<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/admin/partials/performance/media_health/webp_batch.php';

final class MediaWebpBatchTest extends TestCase
{
    public function test_attachment_ids_are_unique_positive_integers_and_bounded_to_five(): void
    {
        $this->assertTrue(Npcink_Toolbox_Webp_Batch::validate_attachment_ids(array(1, 2, 3, 4, 5)));
        $this->assertTrue(Npcink_Toolbox_Webp_Batch::validate_attachment_ids(array('6', 7)));
        $this->assertFalse(Npcink_Toolbox_Webp_Batch::validate_attachment_ids(array()));
        $this->assertFalse(Npcink_Toolbox_Webp_Batch::validate_attachment_ids(array(1, 2, 3, 4, 5, 6)));
        $this->assertFalse(Npcink_Toolbox_Webp_Batch::validate_attachment_ids(array(1, 1)));
        $this->assertFalse(Npcink_Toolbox_Webp_Batch::validate_attachment_ids(array(0)));
        $this->assertFalse(Npcink_Toolbox_Webp_Batch::validate_attachment_ids(array('01')));
    }

    public function test_normalization_never_expands_the_batch(): void
    {
        $this->assertSame(
            array(1, 2, 3, 4, 5),
            Npcink_Toolbox_Webp_Batch::normalize_attachment_ids(array(1, 2, 3, 4, 5, 6))
        );
    }

    public function test_source_contract_keeps_jpeg_backups_and_supports_restore(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/admin/partials/performance/media_health/webp_batch.php'
        );
        $this->assertIsString($source);

        $this->assertStringContainsString("const MAX_BATCH_SIZE        = 5;", $source);
        $this->assertStringContainsString("const SOURCE_MAX_FILE_BYTES = 20971520;", $source);
        $this->assertStringContainsString("const SOURCE_MAX_PIXELS     = 20000000;", $source);
        $this->assertStringContainsString("(string) \$mime_type !== 'image/jpeg'", $source);
        $this->assertStringContainsString('BACKUP_META_KEY', $source);
        $this->assertStringContainsString('generated_files', $source);
        $this->assertStringContainsString('private static function restore_one(', $source);
        $this->assertStringContainsString("'image/webp'", $source);
        $this->assertStringNotContainsString("'image/png'", $source);
        $this->assertStringNotContainsString('wp_delete_attachment(', $source);
        $this->assertSame(0, preg_match('/(?<![A-Za-z0-9_])unlink\s*\(/', $source));
    }
}
