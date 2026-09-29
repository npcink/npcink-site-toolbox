<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__DIR__, 2) . '/');
}
if (!defined('NPCINK_SITE_TOOLBOX_VERSION')) {
    define('NPCINK_SITE_TOOLBOX_VERSION', '3.3.2');
}

if (!function_exists('__')) {
    function __($text, $domain = 'default')
    {
        return $text;
    }
}
if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return $value;
    }
}
if (!function_exists('wp_is_mobile')) {
    function wp_is_mobile()
    {
        return !empty($GLOBALS['_test_wp_is_mobile']);
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return (string) $url;
    }
}
if (!function_exists('wp_register_style') || !function_exists('wp_enqueue_style')) {
    function wp_register_style($handle, $src, $deps = array(), $version = false, $media = 'all')
    {
        return true;
    }
    function wp_enqueue_style($handle)
    {
        return true;
    }
}
if (!function_exists('wp_add_inline_style')) {
    function wp_add_inline_style($handle, $data)
    {
        $GLOBALS['_test_inline_styles'][$handle][] = $data;
        return true;
    }
}
if (!function_exists('wp_register_script') || !function_exists('wp_enqueue_script')) {
    function wp_register_script($handle, $src, $deps = array(), $version = false, $footer = false)
    {
        return true;
    }
    function wp_enqueue_script($handle)
    {
        return true;
    }
}
if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script($handle, $data, $position = 'after')
    {
        $GLOBALS['_test_inline_scripts'][$handle][] = $data;
        return true;
    }
}

require_once dirname(__DIR__, 2) . '/includes/interface-npcink-toolbox-module.php';
require_once dirname(__DIR__, 2) . '/admin/partials/domestic/wechat/index.php';

final class WechatGuideOverlayBehaviorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['_test_wp_is_mobile'] = false;
        $GLOBALS['_test_inline_styles'] = array();
        $GLOBALS['_test_inline_scripts'] = array();
        $_SERVER['HTTP_USER_AGENT'] = '';
    }

    protected function tearDown(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        parent::tearDown();
    }

    private static function method(string $name): ReflectionMethod
    {
        $method = new ReflectionMethod('Npcink_Toolbox_Domestic_Wechat', $name);
        $method->setAccessible(true);
        return $method;
    }

    public function test_wechat_webview_matches(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (iPhone; CPU iPhone OS 16_0) AppleWebKit/605.1.15 MicroMessenger/8.0.38';
        $this->assertTrue(self::method('is_wechat_qq')->invoke(null));
    }

    public function test_mobile_qq_webview_matches(): void
    {
        $GLOBALS['_test_wp_is_mobile'] = true;
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 13) Mobile Safari/537.36 MQQBrowser/6.2 QQ/8.9.13';
        $this->assertTrue(self::method('is_wechat_qq')->invoke(null));
    }

    public function test_desktop_qq_browser_is_not_matched(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 QQBrowser/11.0';
        $this->assertFalse(self::method('is_wechat_qq')->invoke(null));
    }

    public function test_mobile_qq_browser_without_webview_marker_is_not_matched(): void
    {
        $GLOBALS['_test_wp_is_mobile'] = true;
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Linux; Android 13) Mobile Safari/537.36 MQQBrowser/6.2';
        $this->assertFalse(self::method('is_wechat_qq')->invoke(null));
    }

    public function test_overlay_always_ships_a_dismiss_path(): void
    {
        $GLOBALS['_test_wp_is_mobile'] = true;
        $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger/8.0.38';

        Npcink_Toolbox_Domestic_Wechat::run(array('guide_overlay_enabled' => true, 'guide_mode' => 'guide'));
        Npcink_Toolbox_Domestic_Wechat::guide_overlay();

        $styles = implode("\n", $GLOBALS['_test_inline_styles']['mabox-wechat-guide-style'] ?? array());
        $scripts = implode("\n", $GLOBALS['_test_inline_scripts']['mabox-wechat-guide-script'] ?? array());

        $this->assertStringContainsString('.mabox-wechat-guide .dismiss', $styles);
        $this->assertStringContainsString('class="dismiss"', $scripts);
        $this->assertStringContainsString('继续浏览', $scripts);
        $this->assertStringContainsString("dismissBtn.addEventListener('click'", $scripts);
        $this->assertStringContainsString("overlay.remove()", $scripts);
        $this->assertStringContainsString("document.body.style.overflow=''", $scripts);
    }

    public function test_redirect_mode_still_locks_scroll_until_dismissed(): void
    {
        $GLOBALS['_test_wp_is_mobile'] = true;
        $_SERVER['HTTP_USER_AGENT'] = 'MicroMessenger/8.0.38';

        Npcink_Toolbox_Domestic_Wechat::run(array('guide_overlay_enabled' => true, 'guide_mode' => 'redirect'));
        Npcink_Toolbox_Domestic_Wechat::guide_overlay();

        $scripts = implode("\n", $GLOBALS['_test_inline_scripts']['mabox-wechat-guide-script'] ?? array());
        $this->assertStringContainsString("document.body.style.overflow='hidden'", $scripts);
    }
}
