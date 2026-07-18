<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_count_posts')) {
    function wp_count_posts($type = 'post', $perm = '')
    {
        $GLOBALS['_test_wp_count_posts_type'] = $type;

        unset($perm);

        return $GLOBALS['_test_wp_count_posts_result'] ?? (object) array();
    }
}
if (!function_exists('wp_count_comments')) {
    function wp_count_comments()
    {
        return (object) array('approved' => $GLOBALS['_test_site_stats_comments'] ?? 0);
    }
}
if (!function_exists('count_users')) {
    function count_users()
    {
        return array('total_users' => $GLOBALS['_test_site_stats_users'] ?? 0);
    }
}
if (!function_exists('wp_count_terms')) {
    function wp_count_terms($taxonomy)
    {
        unset($taxonomy);

        return $GLOBALS['_test_site_stats_categories'] ?? 0;
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($value)
    {
        return false;
    }
}
if (!function_exists('number_format_i18n')) {
    function number_format_i18n($number, $decimals = 0)
    {
        return number_format((float) $number, $decimals);
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default')
    {
        unset($domain);

        return esc_html($text);
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('get_block_wrapper_attributes')) {
    function get_block_wrapper_attributes($extra_attributes = array())
    {
        return 'class="wp-block-npcink-site-stats ' . esc_attr($extra_attributes['class'] ?? '') . '"';
    }
}

require_once dirname(__DIR__, 2) . '/includes/class-mabox-site-stats.php';

final class SiteStatsBlockTest extends TestCase
{
    public function test_block_uses_metadata_registration_without_a_new_build_target(): void
    {
        $source = $this->source('includes/class-mabox-site-stats.php');
        $editor = $this->source('blocks/site-stats/index.js');
        $main = $this->source('npcink-site-toolbox.php');
        $metadata = json_decode(
            $this->source('blocks/site-stats/block.json'),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        $this->assertSame('npcink/site-stats', $metadata['name']);
        $this->assertSame(3, $metadata['apiVersion']);
        $this->assertSame('npcink-site-toolbox', $metadata['category']);
        $this->assertSame('file:./index.js', $metadata['editorScript']);
        $this->assertSame('file:./style.css', $metadata['style']);
        $this->assertStringContainsString("dirname(__DIR__) . '/blocks/site-stats'", $source);
        $this->assertStringContainsString("array('render_callback' => array(__CLASS__, 'render_block'))", $source);
        $this->assertStringContainsString("blocks.registerBlockType( 'npcink/site-stats'", $editor);
        $this->assertStringContainsString('useBlockProps', $editor);
        $this->assertStringContainsString('TextControl', $editor);
        $this->assertStringContainsString('ToggleControl', $editor);
        $this->assertStringContainsString('Requires at least: 6.3', $main);
        $this->assertFileDoesNotExist($this->root() . '/vite/blocks');
    }

    public function test_dynamic_output_is_escaped_and_honors_visibility(): void
    {
        $GLOBALS['_test_wp_count_posts_result'] = (object) array('publish' => 1280);
        $GLOBALS['_test_site_stats_comments'] = 36;
        $GLOBALS['_test_site_stats_categories'] = 9;
        $GLOBALS['_test_site_stats_users'] = 7;

        $output = MaBox_Site_Stats::render_block(array(
            'title' => '<script>站点概览</script>',
            'showComments' => false,
        ));

        $this->assertStringContainsString('wp-block-npcink-site-stats', $output);
        $this->assertStringContainsString('&lt;script&gt;站点概览&lt;/script&gt;', $output);
        $this->assertStringNotContainsString('<script>', $output);
        $this->assertStringContainsString('1,280', $output);
        $this->assertStringContainsString('分类', $output);
        $this->assertStringContainsString('用户', $output);
        $this->assertStringNotContainsString('评论', $output);
    }

    public function test_empty_selection_has_an_actionable_message(): void
    {
        $output = MaBox_Site_Stats::render_block(array(
            'showPosts' => false,
            'showComments' => false,
            'showCategories' => false,
            'showUsers' => false,
        ));

        $this->assertStringContainsString('请至少选择一个统计项目。', $output);
        $this->assertStringNotContainsString('<dl', $output);
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
