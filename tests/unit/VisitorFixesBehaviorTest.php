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
if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1)
    {
        return true;
    }
}
if (!function_exists('is_admin')) {
    function is_admin()
    {
        return !empty($GLOBALS['_test_is_admin']);
    }
}
if (!function_exists('is_user_logged_in')) {
    function is_user_logged_in()
    {
        return !empty($GLOBALS['_test_mabox_logged_in']);
    }
}
if (!function_exists('wp_is_post_revision')) {
    function wp_is_post_revision($post)
    {
        return false;
    }
}
if (!function_exists('wp_is_post_autosave')) {
    function wp_is_post_autosave($post)
    {
        return false;
    }
}
if (!function_exists('post_type_supports')) {
    function post_type_supports($type, $feature)
    {
        return true;
    }
}
if (!function_exists('get_post_type')) {
    function get_post_type($post)
    {
        return 'post';
    }
}
if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail($post_id = null)
    {
        return !empty($GLOBALS['_test_has_thumbnail']);
    }
}
if (!function_exists('get_children')) {
    function get_children($args)
    {
        return isset($GLOBALS['_test_children']) ? $GLOBALS['_test_children'] : array();
    }
}
if (!function_exists('set_post_thumbnail')) {
    function set_post_thumbnail($post_id, $thumbnail_id)
    {
        $GLOBALS['_test_set_thumbnail_calls'][] = array($post_id, $thumbnail_id);
        return true;
    }
}
if (!function_exists('is_feed')) {
    function is_feed()
    {
        return false;
    }
}
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}

require_once dirname(__DIR__, 2) . '/includes/interface-npcink-toolbox-module.php';
require_once dirname(__DIR__, 2) . '/includes/class-npcink-toolbox-helpers.php';
require_once dirname(__DIR__, 2) . '/includes/class-npcink-toolbox-config-manager.php';
require_once dirname(__DIR__, 2) . '/admin/partials/page/jurisdiction/hide_category.php';
require_once dirname(__DIR__, 2) . '/admin/partials/page/function/first_picture.php';

final class VisitorFixesBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['_test_is_admin'] = false;
        $GLOBALS['_test_mabox_logged_in'] = false;
        $GLOBALS['_test_set_thumbnail_calls'] = array();
        $GLOBALS['_test_children'] = array();
        $GLOBALS['_test_has_thumbnail'] = false;
        $GLOBALS['_test_option_store'] = array();
        unset($_SERVER['REMOTE_ADDR'], $_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    private static function fake_query(array $flags)
    {
        return new VisitorFixesFakeQuery($flags);
    }

    public function test_restricted_categories_are_excluded_from_listings_for_guests(): void
    {
        Npcink_Toolbox_Page_Hide_Category::run(array('category_id' => array('3', '5'), 'tip_content' => 'tip'));
        $query = self::fake_query(array('is_home' => true, 'is_main_query' => true));

        Npcink_Toolbox_Page_Hide_Category::exclude_from_listings($query);

        $this->assertSame(array(3, 5), $query->get('category__not_in'));
    }

    public function test_listing_exclusion_keeps_logged_in_and_single_views(): void
    {
        Npcink_Toolbox_Page_Hide_Category::run(array('category_id' => array('3'), 'tip_content' => 'tip'));

        $GLOBALS['_test_mabox_logged_in'] = true;
        $logged_in_query = self::fake_query(array('is_home' => true, 'is_main_query' => true));
        Npcink_Toolbox_Page_Hide_Category::exclude_from_listings($logged_in_query);
        $this->assertNull($logged_in_query->get('category__not_in'));

        $GLOBALS['_test_mabox_logged_in'] = false;
        $single_query = self::fake_query(array('is_home' => false, 'is_main_query' => true));
        Npcink_Toolbox_Page_Hide_Category::exclude_from_listings($single_query);
        $this->assertNull($single_query->get('category__not_in'));
    }

    public function test_rate_limit_ip_resolves_forwarded_chain_behind_trusted_proxy(): void
    {
        $GLOBALS['_test_option_store']['npcink_site_toolbox_domestic'] = array(
            'login_security' => array('trusted_proxies' => "10.0.0.2\n10.0.0.3"),
        );
        $_SERVER['REMOTE_ADDR'] = '10.0.0.2';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.9, 10.0.0.3';

        $this->assertSame('203.0.113.9', Npcink_Toolbox_Helpers::get_rate_limit_ip());
    }

    public function test_rate_limit_ip_ignores_forwarded_header_without_trusted_proxy(): void
    {
        $_SERVER['REMOTE_ADDR'] = '198.51.100.7';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '203.0.113.9';

        $this->assertSame('198.51.100.7', Npcink_Toolbox_Helpers::get_rate_limit_ip());
    }

    public function test_first_picture_is_set_on_save_not_on_render(): void
    {
        Npcink_Toolbox_Single_First_Picture::run(array());
        $post = new stdClass();
        $post->ID = 42;
        $attachment = new stdClass();
        $GLOBALS['_test_children'] = array(77 => $attachment);

        Npcink_Toolbox_Single_First_Picture::set_featured_image_on_save(42, $post);

        $this->assertSame(array(array(42, 77)), $GLOBALS['_test_set_thumbnail_calls']);

        // 已有特色图时不再覆盖
        $GLOBALS['_test_set_thumbnail_calls'] = array();
        $GLOBALS['_test_has_thumbnail'] = true;
        Npcink_Toolbox_Single_First_Picture::set_featured_image_on_save(42, $post);
        $this->assertSame(array(), $GLOBALS['_test_set_thumbnail_calls']);
    }
}

final class VisitorFixesFakeQuery
{
    private $vars = array();
    private $flags;

    public function __construct(array $flags)
    {
        $this->flags = $flags;
    }

    public function __get($name)
    {
        return isset($this->flags[$name]) ? $this->flags[$name] : false;
    }

    public function __call($name, $args)
    {
        return isset($this->flags[$name]) ? $this->flags[$name] : false;
    }

    public function is_main_query()
    {
        return !empty($this->flags['is_main_query']);
    }

    public function set($key, $value)
    {
        $this->vars[$key] = $value;
    }

    public function get($key)
    {
        return isset($this->vars[$key]) ? $this->vars[$key] : null;
    }
}
