<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class KeywordLinkI18nTest extends TestCase
{
    public function test_link_title_placeholder_has_translator_context(): void
    {
        $source = $this->source();

        $this->assertStringContainsString(
            "/* translators: %s: Tag name used in the generated link title. */\n"
                . '                    $url = "<strong><a href=',
            $source
        );
    }

    public function test_link_limit_uses_wordpress_random_api(): void
    {
        $source = $this->source();

        $this->assertStringContainsString('wp_rand($match_num_from, $match_num_to)', $source);
        $this->assertSame(0, preg_match('/(?<![A-Za-z0-9_])rand\s*\(/', $source));
    }

    private function source(): string
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/admin/partials/page/function/single_keyword_add_link.php'
        );
        $this->assertIsString($source);

        return $source;
    }
}
