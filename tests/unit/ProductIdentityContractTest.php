<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class ProductIdentityContractTest extends TestCase
{
    private const DISPLAY_NAME = 'Npcink Site Toolbox';
    private const SLUG = 'npcink-site-toolbox';
    private const VERSION = '3.3.2';

    public function test_main_plugin_file_defines_the_public_identity(): void
    {
        $root = $this->root();
        $main = $this->source('npcink-site-toolbox.php');

        $this->assertFileDoesNotExist($root . '/magick-tool-box.php');
        $this->assertMatchesRegularExpression('/^[ \t]*\*[ \t]*Plugin Name:[ \t]*' . preg_quote(self::DISPLAY_NAME, '/') . '$/m', $main);
        $this->assertMatchesRegularExpression('/^[ \t]*\*[ \t]*Version:[ \t]*' . preg_quote(self::VERSION, '/') . '$/m', $main);
        $this->assertMatchesRegularExpression('/^[ \t]*\*[ \t]*Text Domain:[ \t]*' . preg_quote(self::SLUG, '/') . '$/m', $main);
        $this->assertStringContainsString("define('NPCINK_SITE_TOOLBOX_NAME', '" . self::SLUG . "')", $main);
        $this->assertStringContainsString("define('NPCINK_SITE_TOOLBOX_VERSION', '" . self::VERSION . "')", $main);
        $this->assertStringContainsString('(new Npcink_Site_Toolbox())->run();', $main);
        $this->assertStringNotContainsString('function npcink_site_toolbox_run()', $main);
        $this->assertStringContainsString('plugins.php?page=' . self::SLUG, $main);
    }

    public function test_public_protocols_use_the_product_slug(): void
    {
        $registry = $this->source('includes/class-npcink-toolbox-rest-route-registry.php');
        $admin = $this->source('admin/class-npcink-toolbox-admin.php');
        $rate_limiter = $this->source('includes/class-npcink-toolbox-rate-limiter.php');

        $this->assertStringContainsString("private static \$namespace = '" . self::SLUG . "/v1'", $registry);
        $this->assertStringContainsString("'" . self::SLUG . "'", $admin);
        $this->assertStringContainsString("'Npcink 站点工具箱'", $admin);
        $this->assertStringContainsString("rest_url('" . self::SLUG . "/v1')", $admin);
        $this->assertStringContainsString("'npcink_site_toolbox_public_api'", $admin);
        $this->assertStringContainsString("get_header('x-" . self::SLUG . "-nonce')", $rate_limiter);
    }

    public function test_build_and_package_metadata_use_one_slug(): void
    {
        $composer = json_decode($this->source('composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('npcink/site-toolbox', $composer['name']);
        $this->assertSame(
            'phpstan analyse --configuration phpstan.neon --memory-limit=2G --no-progress',
            $composer['scripts']['phpstan']
        );

        $build = $this->source('bin/build-release-zip.sh');
        $verify = $this->source('bin/verify-release-zip.sh');
        $workflow = $this->source('.github/workflows/ci.yml');

        $this->assertStringContainsString('PLUGIN_SLUG="' . self::SLUG . '"', $build);
        $this->assertStringContainsString(self::SLUG . '.zip', $build);
        $this->assertStringContainsString('PLUGIN_SLUG="' . self::SLUG . '"', $verify);
        $this->assertStringContainsString('"' . self::SLUG . '.php"', $verify);
        $this->assertStringContainsString('name: ' . self::SLUG, $workflow);
        $this->assertStringContainsString(self::SLUG . '.zip', $workflow);
        $this->assertStringContainsString('run: composer phpstan', $workflow);
        $this->assertStringNotContainsString('vendor/bin/phpstan analyse', $workflow);
        $this->assertStringContainsString('run: pnpm audit --prod', $workflow);
    }

    public function test_release_metadata_uses_one_current_version(): void
    {
        $readme = $this->source('readme.txt');
        $vite = json_decode($this->source('vite/package.json'), true, 512, JSON_THROW_ON_ERROR);
        $docs = json_decode($this->source('docs-site/package.json'), true, 512, JSON_THROW_ON_ERROR);

        $this->assertMatchesRegularExpression('/^Stable tag: ' . preg_quote(self::VERSION, '/') . '$/m', $readme);
        $this->assertSame(self::VERSION, $vite['version']);
        $this->assertSame(self::VERSION, $docs['version']);

        foreach (array('blocks/site-stats/block.json', 'blocks/github-project/block.json') as $path) {
            $metadata = json_decode($this->source($path), true, 512, JSON_THROW_ON_ERROR);
            $this->assertSame(self::VERSION, $metadata['version'], $path);
        }
    }

    public function test_internal_php_identity_uses_one_current_prefix(): void
    {
        $root = $this->root();
        $retired_paths = array(
            'admin/class-magick-mixture-admin.php',
            'includes/class-magick-mixture.php',
            'includes/class-magick-config-manager.php',
            'includes/class-mabox-config-schema.php',
            'includes/interface-mabox-module.php',
            'public/class-magick-mixture-public.php',
        );
        $current_paths = array(
            'admin/class-npcink-toolbox-admin.php',
            'includes/class-npcink-site-toolbox.php',
            'includes/class-npcink-toolbox-config-manager.php',
            'includes/class-npcink-toolbox-config-schema.php',
            'includes/interface-npcink-toolbox-module.php',
            'public/class-npcink-toolbox-public.php',
        );

        foreach ($retired_paths as $relative_path) {
            $this->assertFileDoesNotExist($root . '/' . $relative_path, $relative_path);
        }
        foreach ($current_paths as $relative_path) {
            $this->assertFileExists($root . '/' . $relative_path, $relative_path);
        }

        foreach ($this->runtimePhpSources() as $relative_path => $source) {
            $this->assertSame(
                0,
                preg_match('/(?:\\bMaBox_|\\bMagick_(?:Mixture|Mixtrue)\\b|\\bMAGICK_(?:MIXTURE|TOOLBOX)_|\\bMABOX_)/', $source),
                $relative_path
            );
        }
    }

    public function test_plugin_owned_storage_uses_one_current_prefix(): void
    {
        $this->assertSame('npcink_site_toolbox_active_modules', NPCINK_SITE_TOOLBOX_ACTIVE_MODULES);
        $this->assertSame(
            array(
                'optimize'    => 'npcink_site_toolbox_optimize',
                'page'        => 'npcink_site_toolbox_page',
                'function'    => 'npcink_site_toolbox_function',
                'domestic'    => 'npcink_site_toolbox_domestic',
                'performance' => 'npcink_site_toolbox_performance',
            ),
            Npcink_Toolbox_Config_Manager::get_module_map()
        );

        $runtime = implode("\n", $this->runtimePhpSources());
        foreach (
            array(
                'Magick_ToolBox_',
                'mabox_audit_log',
                'mabox_search_log',
                'mabox_spam_comment_log',
                'mabox_rate_limit_',
                'mabox_comment_rate_',
                'mabox_login_attempt_',
                'mabox_login_lock_',
                'mabox_wx_jsapi_ticket',
                'mabox_environment_check',
                'mabox_search_limit_',
                '_mabox_block_reason',
                'magick_plugin_config',
                'sandbox_theme_display_options',
            ) as $retired_storage
        ) {
            $this->assertStringNotContainsString($retired_storage, $runtime, $retired_storage);
        }

        $favorites = $this->source('vite/admin/src/tool/favorites.ts');
        $risk = $this->source('vite/admin/src/tool/riskyFeature.tsx');
        $this->assertStringContainsString('npcink_site_toolbox_favorites', $favorites);
        $this->assertStringContainsString('npcink_site_toolbox_risky_dismissed', $risk);
        $this->assertStringNotContainsString('mabox_favorites', $favorites);
        $this->assertStringNotContainsString('mabox_risky_dismissed', $risk);
    }

    /**
     * @dataProvider currentRuntimeSourceProvider
     */
    public function test_current_runtime_sources_do_not_expose_retired_public_identity(string $relative_path): void
    {
        $source = $this->source($relative_path);

        foreach (array('magick-toolbox', '/mabox/v1', 'MaBox_config', 'x-mabox-nonce', 'mabox_public_api') as $retired) {
            $this->assertStringNotContainsString($retired, $source, $relative_path);
        }
    }

    /**
     * @return array<string,array{string}>
     */
    public function currentRuntimeSourceProvider(): array
    {
        return array(
            'main plugin file' => array('npcink-site-toolbox.php'),
            'admin controller' => array('admin/class-npcink-toolbox-admin.php'),
            'privacy surface' => array('admin/partials/privacy/index.php'),
            'REST registry' => array('includes/class-npcink-toolbox-rest-route-registry.php'),
            'rate limiter' => array('includes/class-npcink-toolbox-rate-limiter.php'),
            'admin REST client' => array('vite/admin/src/axios/public.ts'),
            'admin data context' => array('vite/admin/src/tool/dataContext.tsx'),
        );
    }

    private function source(string $relative_path): string
    {
        $source = file_get_contents($this->root() . '/' . $relative_path);
        $this->assertIsString($source, $relative_path);

        return $source;
    }

    /**
     * @return array<string,string>
     */
    private function runtimePhpSources(): array
    {
        $sources = array(
            'npcink-site-toolbox.php' => $this->source('npcink-site-toolbox.php'),
            'uninstall.php' => $this->source('uninstall.php'),
        );

        foreach (array('admin', 'includes', 'public') as $directory) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator(
                    $this->root() . '/' . $directory,
                    FilesystemIterator::SKIP_DOTS
                )
            );
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') {
                    continue;
                }
                $relative_path = substr($file->getPathname(), strlen($this->root()) + 1);
                $sources[$relative_path] = $this->source($relative_path);
            }
        }

        ksort($sources);
        return $sources;
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
