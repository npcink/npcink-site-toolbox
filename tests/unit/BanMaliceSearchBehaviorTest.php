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
if (!function_exists('wp_kses')) {
    function wp_kses($content, $allowed_html)
    {
        return $content;
    }
}
if (!function_exists('is_search')) {
    function is_search()
    {
        return !empty($GLOBALS['_test_is_search']);
    }
}
if (!function_exists('wp_get_referer')) {
    function wp_get_referer()
    {
        return 'https://example.com/';
    }
}
if (!function_exists('wp_validate_redirect')) {
    function wp_validate_redirect($location, $fallback = '')
    {
        return $fallback !== '' ? $fallback : $location;
    }
}
if (!function_exists('home_url')) {
    function home_url($path = '')
    {
        return 'https://example.com/' . ltrim((string) $path, '/');
    }
}
if (!class_exists('Npcink_Toolbox_Admin')) {
    class Npcink_Toolbox_Admin
    {
        public static function back_button($text = null)
        {
            return '<p><a href="https://example.com/">返回</a></p>';
        }
    }
}

final class BanMaliceSearchDieException extends RuntimeException
{
}

if (!function_exists('wp_die')) {
    function wp_die($message = '')
    {
        $GLOBALS['_test_ban_malice_die_calls'][] = is_string($message) ? $message : '';
        throw new BanMaliceSearchDieException('wp_die simulated');
    }
}

require_once dirname(__DIR__, 2) . '/includes/interface-npcink-toolbox-module.php';
require_once dirname(__DIR__, 2) . '/admin/partials/function/auxiliary/ban_malice_search.php';

final class BanMaliceSearchBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['_test_is_search'] = false;
        $GLOBALS['_test_ban_malice_die_calls'] = array();
        $GLOBALS['wp_query'] = new stdClass();
        $GLOBALS['wp_query']->query_vars = array('s' => '');
    }

    private function search(string $term): void
    {
        $GLOBALS['_test_is_search'] = true;
        $GLOBALS['wp_query']->query_vars = array('s' => $term);
    }

    public function test_blank_keyword_lines_do_not_block_normal_search(): void
    {
        $this->search('天气预报');

        Npcink_Toolbox_Ban_Malice_Search::ban_malice_search("敏感词\n\n赌博\n\n\n正常词");
        $this->assertSame(array(), $GLOBALS['_test_ban_malice_die_calls']);
    }

    public function test_only_blank_lines_never_block_any_search(): void
    {
        $this->search('任何内容都应该放行');

        Npcink_Toolbox_Ban_Malice_Search::ban_malice_search("\n\n \n\t\n");
        $this->assertSame(array(), $GLOBALS['_test_ban_malice_die_calls']);
    }

    public function test_real_keyword_still_blocks(): void
    {
        $this->search('在线赌博网站');

        try {
            Npcink_Toolbox_Ban_Malice_Search::ban_malice_search("敏感词\n\n赌博");
            $this->fail('Expected wp_die for a blocked keyword.');
        } catch (BanMaliceSearchDieException $e) {
            $this->assertCount(1, $GLOBALS['_test_ban_malice_die_calls']);
            $this->assertStringContainsString('敏感词', $GLOBALS['_test_ban_malice_die_calls'][0]);
        }
    }

    public function test_keyword_at_position_zero_still_blocks(): void
    {
        $this->search('赌博');

        try {
            Npcink_Toolbox_Ban_Malice_Search::ban_malice_search("赌博");
            $this->fail('Expected wp_die when the keyword starts the search term.');
        } catch (BanMaliceSearchDieException $e) {
            $this->assertCount(1, $GLOBALS['_test_ban_malice_die_calls']);
        }
    }

    public function test_non_search_requests_are_ignored(): void
    {
        $GLOBALS['_test_is_search'] = false;
        $GLOBALS['wp_query']->query_vars = array('s' => '赌博');

        Npcink_Toolbox_Ban_Malice_Search::ban_malice_search("赌博");
        $this->assertSame(array(), $GLOBALS['_test_ban_malice_die_calls']);
    }
}
