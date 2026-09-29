<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__, 2) . '/');
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}
if (!function_exists('is_feed')) {
    function is_feed()
    {
        return !empty($GLOBALS['_test_is_feed']);
    }
}
if (!function_exists('get_the_tags')) {
    function get_the_tags()
    {
        return $GLOBALS['_test_post_tags'] ?? false;
    }
}
if (!function_exists('get_tag_link')) {
    function get_tag_link($term_id)
    {
        return 'https://example.com/tag/' . (int) $term_id;
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return (string) $url;
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('wp_rand')) {
    function wp_rand($min = 0, $max = 0)
    {
        return $min;
    }
}

require_once dirname(__DIR__, 2) . '/includes/interface-npcink-toolbox-module.php';
require_once dirname(__DIR__, 2) . '/admin/partials/page/function/single_keyword_add_link.php';

final class KeywordLinkBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['_test_is_feed'] = false;
        $GLOBALS['_test_post_tags'] = array();
    }

    private function tearDownTags(): void
    {
        $GLOBALS['_test_post_tags'] = array();
    }

    private static function tag(string $name, int $id): stdClass
    {
        $tag = new stdClass();
        $tag->name = $name;
        $tag->term_id = $id;
        return $tag;
    }

    public function test_feed_output_is_left_untouched(): void
    {
        $GLOBALS['_test_is_feed'] = true;
        $GLOBALS['_test_post_tags'] = array(self::tag('WordPress', 7));
        $content = '本文介绍 WordPress 的用法。';

        $this->assertSame($content, Npcink_Toolbox_Single_Keyword_Add_Link::tag_link($content));
    }

    public function test_code_and_pre_blocks_are_never_linked(): void
    {
        $GLOBALS['_test_post_tags'] = array(self::tag('WordPress', 7));
        $content = '先看代码 <code>WordPress 调用示例</code>，再读 <pre lang="php">WordPress 函数</pre>，正文再谈 WordPress 用法。';

        $result = Npcink_Toolbox_Single_Keyword_Add_Link::tag_link($content);

        $this->assertStringContainsString('<code>WordPress 调用示例</code>', $result);
        $this->assertStringContainsString('<pre lang="php">WordPress 函数</pre>', $result);
        $this->assertStringContainsString('href="https://example.com/tag/7"', $result);
        $this->assertSame(
            1,
            substr_count($result, 'href="https://example.com/tag/7"'),
            'Only the body occurrence should be linked'
        );
    }

    public function test_generated_links_carry_noopener_and_target_blank(): void
    {
        $GLOBALS['_test_post_tags'] = array(self::tag('WordPress', 7));

        $result = Npcink_Toolbox_Single_Keyword_Add_Link::tag_link('WordPress 入门教程');

        $this->assertStringContainsString('target="_blank" rel="noopener"', $result);
    }

    public function test_block_delimiters_survive_the_filter(): void
    {
        $GLOBALS['_test_post_tags'] = array(self::tag('WordPress', 7));
        $content = "<!-- wp:latest-posts {\"postsToShow\":3} /-->\n<p>WordPress 动态区块演示。</p>";

        $result = Npcink_Toolbox_Single_Keyword_Add_Link::tag_link($content);

        $this->assertStringContainsString('<!-- wp:latest-posts {"postsToShow":3} /-->', $result);
    }

    public function test_content_without_matching_tags_is_returned_as_is(): void
    {
        $GLOBALS['_test_post_tags'] = false;
        $content = '<p>没有任何标签。</p>';

        $this->assertSame($content, Npcink_Toolbox_Single_Keyword_Add_Link::tag_link($content));
    }

    public function test_filter_no_longer_runs_kses_or_hijacks_block_rendering(): void
    {
        $source = (string) file_get_contents(
            dirname(__DIR__, 2) . '/admin/partials/page/function/single_keyword_add_link.php'
        );

        $this->assertSame(0, preg_match('/wp_kses_post\s*\(/', $source), 'kses strips block delimiters and data-* attributes');
        $this->assertStringContainsString("add_filter('the_content', array(__CLASS__, 'tag_link'), 12)", $source);
        $this->assertStringContainsString('if (is_feed())', $source);
    }

    protected function tearDown(): void
    {
        $this->tearDownTags();
        parent::tearDown();
    }
}
