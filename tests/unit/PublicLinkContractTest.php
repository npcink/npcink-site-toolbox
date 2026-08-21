<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class PublicLinkContractTest extends TestCase
{
    private const CURRENT_REPOSITORY = 'https://github.com/npcink/npcink-site-toolbox';
    private const RETIRED_REPOSITORY = 'github.com/muze-page/npcink-site-toolbox';

    public function test_current_public_surfaces_use_the_current_repository_identity(): void
    {
        foreach ($this->publicSurfaceFiles() as $relative_path) {
            $source = $this->source($relative_path);
            $this->assertStringNotContainsString(self::RETIRED_REPOSITORY, $source, $relative_path);
        }

        $this->assertStringContainsString(self::CURRENT_REPOSITORY, $this->source('README.md'));
        $this->assertStringContainsString(self::CURRENT_REPOSITORY, $this->source('readme.txt'));
        $this->assertStringContainsString(self::CURRENT_REPOSITORY, $this->source('docs-site/.vitepress/config.ts'));
        $this->assertStringContainsString(self::CURRENT_REPOSITORY, $this->source('vite/admin/src/components/about/index.tsx'));
    }

    public function test_unready_documentation_domain_is_not_advertised(): void
    {
        foreach ($this->publicSurfaceFiles() as $relative_path) {
            $this->assertStringNotContainsString('docs.npc.ink', $this->source($relative_path), $relative_path);
        }
    }

    public function test_user_facing_static_links_do_not_use_plain_http(): void
    {
        $about_sources = array(
            'vite/admin/src/components/about/index.tsx',
            'vite/admin/src/components/about/collapse.tsx',
        );

        foreach ($about_sources as $relative_path) {
            $source = $this->source($relative_path);
            $this->assertStringNotContainsString('href="http://', $source, $relative_path);
        }
    }

    public function test_markdown_relative_links_resolve_to_existing_targets(): void
    {
        foreach ($this->markdownFiles() as $relative_path) {
            $source = $this->source($relative_path);
            preg_match_all('/\[[^\]]*\]\(([^)]+)\)/', $source, $matches);

            foreach ($matches[1] as $raw_target) {
                $target = trim(explode(' ', trim($raw_target), 2)[0], '<>');
                if ($target === '' || preg_match('~^(?:https?://|mailto:|\#)~', $target)) {
                    continue;
                }

                $path = preg_split('/[#?]/', $target, 2)[0];
                if ($path === '') {
                    continue;
                }

                if (strpos($path, '/') === 0) {
                    $resolved = $this->root() . '/docs-site/' . ltrim($path, '/');
                } else {
                    $resolved = dirname($this->root() . '/' . $relative_path) . '/' . $path;
                }

                $this->assertTrue(
                    file_exists($resolved) || file_exists($resolved . '.md') || file_exists($resolved . '/index.md'),
                    $relative_path . ' -> ' . $target
                );
            }
        }
    }

    public function test_link_maintenance_rules_are_documented(): void
    {
        $rules = $this->source('docs/链接维护规范-2026-08.md');
        $agents = $this->source('AGENTS.md');

        foreach (array('插件内置帮助页', '第三方链接', '占位', 'composer links:check') as $required) {
            $this->assertStringContainsString($required, $rules);
        }
        $this->assertStringContainsString('built-in help page', $agents);
    }

    /** @return array<int,string> */
    private function publicSurfaceFiles(): array
    {
        $files = array(
            'README.md',
            'readme.txt',
            'npcink-site-toolbox.php',
            'docs-site/.vitepress/config.ts',
            'vite/admin/src/components/about/index.tsx',
            'vite/admin/src/components/about/collapse.tsx',
            'vite/admin/src/components/about/table.tsx',
            'vite/admin/src/components/page/comment.tsx',
        );

        foreach ($this->filesBelow('docs-site', array('md')) as $relative_path) {
            $files[] = $relative_path;
        }

        return array_values(array_unique($files));
    }

    /** @return array<int,string> */
    private function markdownFiles(): array
    {
        return array_merge(array('README.md'), $this->filesBelow('docs-site', array('md')));
    }

    /** @param array<int,string> $extensions
     *  @return array<int,string>
     */
    private function filesBelow(string $directory, array $extensions): array
    {
        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root() . '/' . $directory, FilesystemIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            $pathname = str_replace('\\', '/', $file->getPathname());
            if (strpos($pathname, '/node_modules/') !== false || strpos($pathname, '/.vitepress/dist/') !== false) {
                continue;
            }
            if (!$file->isFile() || !in_array($file->getExtension(), $extensions, true)) {
                continue;
            }
            $files[] = substr($file->getPathname(), strlen($this->root()) + 1);
        }
        sort($files);
        return $files;
    }

    private function source(string $relative_path): string
    {
        $source = file_get_contents($this->root() . '/' . $relative_path);
        $this->assertIsString($source, $relative_path);
        return $source;
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
