<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class WordPressOrgGovernanceDocumentationTest extends TestCase
{
    public function test_canonical_release_governance_documents_are_linked_and_complete(): void
    {
        $root = dirname(__DIR__, 2);
        $standard_path = $root . '/docs/operations/WordPress.org发布审核与防回归规范.md';
        $adr_path = $root . '/docs/decisions/0005-exact-artifact-wordpress-org-release-gate.md';

        $this->assertFileExists($standard_path);
        $this->assertFileExists($adr_path);

        $standard = (string) file_get_contents($standard_path);
        $adr = (string) file_get_contents($adr_path);
        $readme = (string) file_get_contents($root . '/README.md');
        $agents = (string) file_get_contents($root . '/AGENTS.md');
        $build_guide = (string) file_get_contents($root . '/docs/operations/构建与发布指南.md');
        $retrospective = (string) file_get_contents($root . '/docs/operations/WordPress.org自动预审整改复盘-2026-08.md');

        foreach (array(
            '精确发布包',
            'composer release:wordpress-org-check',
            'WP_DEBUG',
            '官方最新版 Plugin Check',
            '未审阅 warning',
            'ZIP SHA-256',
            '公开源码与 tag 顺序',
            '一次反馈形成永久门禁',
            '验证工具本身也要被验证',
        ) as $required) {
            $this->assertStringContainsString($required, $standard, $required);
        }

        $this->assertStringContainsString('ADR-0005', $adr);
        $this->assertStringContainsString('已接受', $adr);
        $this->assertStringContainsString('不得回滚“精确 ZIP 是验收对象”这一原则', $adr);
        $this->assertStringContainsString('不得传入 `--ignore-warnings`', $standard);
        $this->assertStringContainsString('不得传入 `--ignore-warnings`', $adr);

        foreach (array($readme, $agents, $build_guide, $retrospective) as $source) {
            $this->assertStringContainsString('WordPress.org发布审核与防回归规范.md', $source);
        }
    }
}
