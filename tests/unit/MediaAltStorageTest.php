<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class MaBox_Media_Alt_Storage_Test extends TestCase {

    /**
     * @dataProvider alt_handler_files
     */
    public function test_alt_handlers_use_wordpress_attachment_alt_meta(string $relative_path): void {
        $file = dirname(__FILE__) . '/../../' . $relative_path;
        $this->assertFileExists($file);

        $content = file_get_contents($file);

        $this->assertStringContainsString('_wp_attachment_image_alt', $content);
        $this->assertStringContainsString('update_post_meta($img->ID', $content);
        $this->assertStringNotContainsString("'post_excerpt' => \$alt", $content);
    }

    public function alt_handler_files(): array {
        return array(
            'media health' => array('admin/partials/performance/media_health/index.php'),
            'seo checker'  => array('admin/partials/performance/seo_checker/index.php'),
        );
    }
}
