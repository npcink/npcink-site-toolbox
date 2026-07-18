<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default')
    {
        unset($domain);

        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}

final class BlockPatternsContractTest extends TestCase
{
    /**
     * @return array<string,array{string}>
     */
    public function patternProvider(): array
    {
        return array(
            'resource download' => array('resource-download-card'),
            'key takeaway'      => array('key-takeaway-card'),
            'source note'       => array('source-copyright-note'),
        );
    }

    public function test_registration_is_attached_to_init(): void
    {
        $core = $this->source('includes/class-magick-mixture.php');
        $autoload = $this->source('includes/autoload.php');

        $this->assertStringContainsString(
            "add_action('init', array('MaBox_Block_Patterns', 'register'))",
            $core
        );
        $this->assertStringContainsString(
            "'MaBox_Block_Patterns' => 'includes/class-mabox-block-patterns.php'",
            $autoload
        );
    }

    public function test_block_and_pattern_categories_share_the_product_identity(): void
    {
        $core = $this->source('includes/class-magick-mixture.php');
        $this->assertStringContainsString(
            "add_filter('block_categories_all', array('MaBox_Block_Patterns', 'add_block_category'))",
            $core
        );

        $categories = array(
            array('slug' => 'text', 'title' => '文本'),
        );
        $with_toolbox = MaBox_Block_Patterns::add_block_category($categories);

        $this->assertSame(
            array(
                'slug'  => 'npcink-site-toolbox',
                'title' => 'Npcink Site Toolbox',
            ),
            $with_toolbox[1]
        );
        $this->assertSame(
            $with_toolbox,
            MaBox_Block_Patterns::add_block_category($with_toolbox),
            'The shared category must not be duplicated.'
        );

        foreach (MaBox_Block_Patterns::definitions() as $definition) {
            $this->assertContains('npcink-site-toolbox', $definition['categories']);
        }
    }

    /**
     * @dataProvider patternProvider
     */
    public function test_pattern_uses_only_parseable_core_blocks(string $slug): void
    {
        $definitions = MaBox_Block_Patterns::definitions();
        $this->assertArrayHasKey($slug, $definitions);

        $path = $this->root() . '/patterns/' . $definitions[$slug]['file'];
        $content = include $path;
        $this->assertIsString($content);

        $matchCount = preg_match_all('/<!--\s+wp:([a-z0-9-]+(?:\/[a-z0-9-]+)?)/', $content, $matches);
        $this->assertIsInt($matchCount);
        $this->assertGreaterThan(0, $matchCount);
        $this->assertSame(
            substr_count($content, '<!-- wp:'),
            substr_count($content, '<!-- /wp:'),
            $slug . ' has unbalanced block delimiters'
        );

        foreach ($matches[1] as $name) {
            $this->assertMatchesRegularExpression(
                '/^(?:core\/)?[a-z0-9-]+$/',
                $name,
                $slug . ' contains a non-core block'
            );
        }
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents($this->root() . '/' . $relativePath);
        $this->assertIsString($source);

        return $source;
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
