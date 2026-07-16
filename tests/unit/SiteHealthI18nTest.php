<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class SiteHealthI18nTest extends TestCase
{
    /**
     * @dataProvider placeholderTranslationProvider
     */
    public function test_placeholder_translations_have_exact_translator_context(
        string $format,
        string $translator_context
    ): void {
        $source = $this->source();
        $pattern = '~'
            . preg_quote('/* translators: ' . $translator_context . ' */', '~')
            . '\\s*'
            . preg_quote("__('" . $format . "', 'magick-toolbox')", '~')
            . '~u';

        $this->assertSame(
            1,
            preg_match($pattern, $source),
            sprintf('Missing exact translator context for format: %s', $format)
        );
    }

    /**
     * @dataProvider orderedPlaceholderProvider
     */
    public function test_ordered_formats_preserve_argument_order(
        string $format,
        string $first_argument,
        string $second_argument
    ): void {
        $source = $this->source();
        $pattern = '~sprintf\\(\\s*'
            . '/\\* translators: [^*]+ \\*/\\s*'
            . preg_quote("__('" . $format . "', 'magick-toolbox')", '~')
            . ',\\s*'
            . preg_quote($first_argument, '~')
            . ',\\s*'
            . preg_quote($second_argument, '~')
            . '\\s*\\)~u';

        $this->assertSame(
            1,
            preg_match($pattern, $source),
            sprintf('Ordered format or sprintf argument order changed: %s', $format)
        );
    }

    public function test_every_placeholder_translation_has_adjacent_translator_context(): void
    {
        $source = $this->source();
        $matches = array();

        preg_match_all(
            "/__\\('([^'\\n]*%[^'\\n]*)', 'magick-toolbox'\\)/u",
            $source,
            $matches,
            PREG_OFFSET_CAPTURE
        );

        $this->assertGreaterThanOrEqual(11, count($matches[0]));

        foreach ($matches[0] as $match) {
            $prefix = substr($source, 0, $match[1]);
            $this->assertMatchesRegularExpression(
                '/\\/\\* translators: [^*\\r\\n]+ \\*\\/\\s*$/',
                $prefix,
                sprintf('Placeholder translation lacks adjacent context: %s', $match[0])
            );
        }
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function placeholderTranslationProvider(): array
    {
        return array(
            'supported PHP version' => array(
                '当前 PHP 版本 %1$s，满足最低要求（%2$s+）。',
                '1: Current PHP version, 2: Minimum required PHP version.',
            ),
            'unsupported PHP version' => array(
                '当前 PHP 版本 %1$s，低于最低要求 %2$s。部分功能可能无法正常工作。',
                '1: Current PHP version, 2: Minimum required PHP version.',
            ),
            'supported WordPress version' => array(
                '当前 WordPress 版本 %1$s，建议使用 %2$s+ 以获得最佳体验。',
                '1: Current WordPress version, 2: Recommended WordPress version.',
            ),
            'unsupported WordPress version' => array(
                '当前 WordPress 版本 %1$s，建议升级至 %2$s+。',
                '1: Current WordPress version, 2: Recommended WordPress version.',
            ),
            'permalink structure' => array(
                '当前固定链接结构：%s',
                '%s: Current permalink structure.',
            ),
            'REST endpoint' => array(
                'REST API 端点 %s 响应正常。',
                '%s: REST API endpoint URL.',
            ),
            'active module label' => array(
                '已激活 %1$d / %2$d 个模块',
                '1: Number of active modules, 2: Total number of available modules.',
            ),
            'active module description' => array(
                '当前已激活 %1$d 个模块，共 %2$d 个可用模块。按需加载机制有助于减少不必要的资源消耗。',
                '1: Number of active modules, 2: Total number of available modules.',
            ),
            'high-risk modules' => array(
                '<strong>高风险模块（%1$d 个）：</strong>%2$s<br>',
                '1: Number of active high-risk modules, 2: Comma-separated high-risk module IDs.',
            ),
            'experimental modules' => array(
                '<strong>实验性模块（%1$d 个）：</strong>%2$s<br>',
                '1: Number of active experimental modules, 2: Comma-separated experimental module IDs.',
            ),
            'risky module label' => array(
                '已启用 %d 个高风险/实验性模块',
                '%d: Number of active high-risk or experimental modules.',
            ),
        );
    }

    /**
     * @return array<string, array{string, string, string}>
     */
    public function orderedPlaceholderProvider(): array
    {
        return array(
            'supported PHP version' => array(
                '当前 PHP 版本 %1$s，满足最低要求（%2$s+）。',
                '$current',
                '$recommended',
            ),
            'unsupported PHP version' => array(
                '当前 PHP 版本 %1$s，低于最低要求 %2$s。部分功能可能无法正常工作。',
                '$current',
                '$recommended',
            ),
            'supported WordPress version' => array(
                '当前 WordPress 版本 %1$s，建议使用 %2$s+ 以获得最佳体验。',
                '$current',
                '$recommended',
            ),
            'unsupported WordPress version' => array(
                '当前 WordPress 版本 %1$s，建议升级至 %2$s+。',
                '$current',
                '$recommended',
            ),
            'active module label' => array(
                '已激活 %1$d / %2$d 个模块',
                '$count',
                '$total',
            ),
            'active module description' => array(
                '当前已激活 %1$d 个模块，共 %2$d 个可用模块。按需加载机制有助于减少不必要的资源消耗。',
                '$count',
                '$total',
            ),
            'high-risk modules' => array(
                '<strong>高风险模块（%1$d 个）：</strong>%2$s<br>',
                'count($high_risk_active)',
                'esc_html(implode(\', \', $high_risk_active))',
            ),
            'experimental modules' => array(
                '<strong>实验性模块（%1$d 个）：</strong>%2$s<br>',
                'count($experimental_active)',
                'esc_html(implode(\', \', $experimental_active))',
            ),
        );
    }

    private function source(): string
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/includes/class-magick-site-health.php'
        );
        $this->assertIsString($source);

        return $source;
    }
}
