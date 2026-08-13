<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PlatformCompatibilityContractTest extends TestCase
{
    public function test_declared_platform_floor_is_consistent_across_public_metadata(): void
    {
        $main = $this->source('npcink-site-toolbox.php');
        $readme = $this->source('readme.txt');
        $composer = json_decode($this->source('composer.json'), true, 512, JSON_THROW_ON_ERROR);
        $this->assertMatchesRegularExpression('/^ \* Requires at least: 6\.3$/m', $main);
        $this->assertMatchesRegularExpression('/^ \* Requires PHP:\s+7\.4$/m', $main);
        $this->assertMatchesRegularExpression('/^Requires at least: 6\.3$/m', $readme);
        $this->assertMatchesRegularExpression('/^Requires PHP: 7\.4$/m', $readme);
        $this->assertSame('>=7.4', $composer['require']['php']);
        $this->assertSame('7.4', $composer['config']['platform']['php']);
    }

    public function test_ci_php_matrix_covers_supported_runtime_floor_and_current_versions(): void
    {
        $workflow = $this->source('.github/workflows/ci.yml');
        foreach (array('7.4', '8.0', '8.1', '8.2', '8.3') as $version) {
            $this->assertStringContainsString("'{$version}'", $workflow, $version);
        }
        $this->assertStringContainsString('PHP Syntax Check', $workflow);
    }

    public function test_runtime_php_sources_do_not_use_php_8_only_syntax(): void
    {
        foreach ($this->runtimeSources() as $relativePath => $source) {
            foreach (token_get_all($source) as $token) {
                if (!is_array($token)) continue;
                foreach (array_filter(array(
                    defined('T_READONLY') ? T_READONLY : null,
                    defined('T_MATCH') ? T_MATCH : null,
                    defined('T_FN') ? T_FN : null,
                ), static function ($value): bool { return $value !== null; }) as $forbidden) {
                    $this->assertNotSame($forbidden, $token[0], $relativePath . ' uses PHP 8-only syntax');
                }
            }
        }
    }

    /** @return array<string,string> */
    private function runtimeSources(): array
    {
        $sources = array('npcink-site-toolbox.php' => $this->source('npcink-site-toolbox.php'), 'uninstall.php' => $this->source('uninstall.php'));
        foreach (array('admin', 'includes', 'public') as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->root() . '/' . $directory, FilesystemIterator::SKIP_DOTS));
            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getExtension() !== 'php') continue;
                $relative = substr($file->getPathname(), strlen($this->root()) + 1);
                $sources[$relative] = $this->source($relative);
            }
        }
        return $sources;
    }

    private function source(string $relativePath): string
    {
        $source = file_get_contents($this->root() . '/' . $relativePath);
        $this->assertIsString($source, $relativePath);
        return $source;
    }

    private function root(): string { return dirname(__DIR__, 2); }
}
