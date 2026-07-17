<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class AssetVersionContractTest extends TestCase
{
    public function test_inline_asset_handles_are_registered_before_inline_data_is_added(): void
    {
        $sources = array(
            $this->source('admin/partials/domestic/compliance/index.php'),
            $this->source('admin/partials/domestic/wechat/index.php'),
        );

        $handles = array(
            'mabox-cookie-style',
            'mabox-cookie-script',
            'mabox-wechat-jssdk',
            'mabox-wechat-guide-style',
            'mabox-wechat-guide-script',
        );

        foreach ($handles as $handle) {
            $source = strpos($handle, 'cookie') !== false ? $sources[0] : $sources[1];
            $register_position = strpos($source, "wp_register_" . (strpos($handle, 'style') !== false ? 'style' : 'script') . "('{$handle}'");
            $inline_position = strpos($source, "wp_add_inline_" . (strpos($handle, 'style') !== false ? 'style' : 'script') . "('{$handle}'");

            $this->assertNotFalse($register_position, $handle);
            $this->assertNotFalse($inline_position, $handle);
            $this->assertLessThan($inline_position, $register_position, $handle);
        }
    }

    public function test_plugin_owned_assets_have_explicit_versions(): void
    {
        $this->assertStringContainsString(
            "wp_register_style('mabox-cookie-style', false, array(), MAGICK_MIXTURE_VERSION)",
            $this->source('admin/partials/domestic/compliance/index.php')
        );
        $this->assertStringContainsString(
            "wp_register_script('mabox-cookie-script', false, array(), MAGICK_MIXTURE_VERSION, true)",
            $this->source('admin/partials/domestic/compliance/index.php')
        );
        $this->assertStringContainsString(
            "wp_register_style('mabox-wechat-guide-style', false, array(), MAGICK_MIXTURE_VERSION)",
            $this->source('admin/partials/domestic/wechat/index.php')
        );
        $this->assertStringContainsString(
            "wp_register_script('mabox-wechat-jssdk', 'https://res.wx.qq.com/open/js/jweixin-1.6.0.js', array(), '1.6.0', true)",
            $this->source('admin/partials/domestic/wechat/index.php')
        );
        $this->assertStringContainsString(
            "wp_register_script('mabox-wechat-guide-script', false, array(), MAGICK_MIXTURE_VERSION, true)",
            $this->source('admin/partials/domestic/wechat/index.php')
        );
        $this->assertStringContainsString(
            "array(), MAGICK_MIXTURE_VERSION );",
            $this->source('admin/partials/optimize/admin/thumbnail_switcher/easy-thumbnail-switcher.php')
        );
        $this->assertStringContainsString(
            "array(), MAGICK_MIXTURE_VERSION)",
            $this->source('admin/partials/optimize/admin/add_time_screen.php')
        );
    }

    private function source(string $relative_path): string
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/' . $relative_path);
        $this->assertIsString($source, $relative_path);

        return $source;
    }
}
