<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__, 2) . '/');
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return $value;
    }
}
if (!function_exists('is_search')) {
    function is_search()
    {
        return !empty($GLOBALS['_test_is_search']);
    }
}
if (!function_exists('home_url')) {
    function home_url($path = '')
    {
        return 'https://example.com' . $path;
    }
}
if (!function_exists('add_query_arg'))
{
    function add_query_arg($args, $url)
    {
        return $url . '?' . http_build_query($args);
    }
}

final class SearchLinkRedirectException extends RuntimeException
{
}

if (!function_exists('wp_safe_redirect')) {
    function wp_safe_redirect($location, $status = 302)
    {
        $GLOBALS['_test_search_redirect_target'] = $location;
        throw new SearchLinkRedirectException((string) $location);
    }
}

require_once dirname(__DIR__, 2) . '/includes/interface-npcink-toolbox-module.php';
require_once dirname(__DIR__, 2) . '/admin/partials/optimize/site/search_link_simplify.php';

final class SearchLinkSimplifyBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['_test_is_search'] = false;
        $GLOBALS['_test_search_redirect_target'] = null;
        $GLOBALS['_test_option_store'] = array();
        $_GET = array();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_test_is_search'], $GLOBALS['_test_search_redirect_target']);
        $_GET = array();
        parent::tearDown();
    }

    public function test_redirects_plain_search_on_pretty_permalinks(): void
    {
        $GLOBALS['_test_is_search'] = true;
        $GLOBALS['_test_option_store']['permalink_structure'] = '/%postname%/';
        $_GET['s'] = 'wordpress 教程';

        try {
            Npcink_Toolbox_Search_Link_Simplify::redirect_search();
            $this->fail('Expected redirect.');
        } catch (SearchLinkRedirectException $e) {
            $this->assertSame('https://example.com/search/wordpress%20%E6%95%99%E7%A8%8B', $e->getMessage());
        }
    }

    public function test_skips_redirect_without_pretty_permalinks(): void
    {
        $GLOBALS['_test_is_search'] = true;
        $GLOBALS['_test_option_store']['permalink_structure'] = '';
        $_GET['s'] = 'wordpress';

        Npcink_Toolbox_Search_Link_Simplify::redirect_search();
        $this->assertNull($GLOBALS['_test_search_redirect_target']);
    }

    public function test_passes_search_context_arguments_through(): void
    {
        $GLOBALS['_test_is_search'] = true;
        $GLOBALS['_test_option_store']['permalink_structure'] = '/%postname%/';
        $_GET['s'] = '主题';
        $_GET['post_type'] = 'book';
        $_GET['cat'] = '5';
        $_GET['utm_source'] = 'newsletter';

        try {
            Npcink_Toolbox_Search_Link_Simplify::redirect_search();
            $this->fail('Expected redirect.');
        } catch (SearchLinkRedirectException $e) {
            $target = $e->getMessage();
            $this->assertStringContainsString('/search/', $target);
            $this->assertStringContainsString('post_type=book', $target);
            $this->assertStringContainsString('cat=5', $target);
            // 白名单之外的参数不透传
            $this->assertStringNotContainsString('utm_source', $target);
        }
    }

    public function test_skips_redirect_for_empty_search_term(): void
    {
        $GLOBALS['_test_is_search'] = true;
        $GLOBALS['_test_option_store']['permalink_structure'] = '/%postname%/';
        $_GET['s'] = '';

        Npcink_Toolbox_Search_Link_Simplify::redirect_search();
        $this->assertNull($GLOBALS['_test_search_redirect_target']);
    }
}
