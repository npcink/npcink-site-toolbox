<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ImageAddTagTest extends TestCase
{
    public function test_runtime_uses_the_wordpress_html_tag_processor(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('WP_HTML_Tag_Processor', $source);
        $this->assertStringContainsString("next_tag('IMG')", $source);
        $this->assertStringContainsString("get_attribute('alt')", $source);
        $this->assertStringContainsString("set_attribute('alt'", $source);
        $this->assertStringNotContainsString("preg_match_all('/<img ", $source);
    }

    public function test_runtime_preserves_meaningful_existing_alt_text(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            'trim( (string) $existing_alt)',
            $source
        );
    }

    private function source(): string
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/admin/partials/optimize/medium/image_add_tag.php');
        $this->assertIsString($source);

        return $source;
    }
}
