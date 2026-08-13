<?php

use PHPUnit\Framework\TestCase;

final class InternationalizationContractTest extends TestCase
{
    public function test_admin_runtime_and_pot_share_the_plugin_text_domain(): void
    {
        $root = dirname(__DIR__, 2);
        $admin = (string) file_get_contents($root . '/admin/class-npcink-toolbox-admin.php');
        $helper = (string) file_get_contents($root . '/vite/admin/src/tool/i18n.ts');
        $composer = json_decode((string) file_get_contents($root . '/composer.json'), true);

        $this->assertStringContainsString("array('wp-i18n')", $admin);
        $this->assertStringContainsString("wp_set_script_translations(\$name, 'npcink-site-toolbox'", $admin);
        $this->assertStringContainsString('window.wp?.i18n?.__(text, "npcink-site-toolbox")', $helper);
        $this->assertSame('bash bin/make-pot.sh', $composer['scripts']['i18n:pot'] ?? null);
    }

    public function test_pot_contains_php_and_admin_javascript_messages(): void
    {
        $pot = (string) file_get_contents(dirname(__DIR__, 2) . '/languages/npcink-site-toolbox.pot');

        foreach (array(
            '保存成功',
            'SEO 检查助手',
            '数据库清理与优化',
            '媒体库健康体检',
        ) as $message) {
            $this->assertStringContainsString('msgid "' . $message . '"', $pot, $message);
        }
    }
}
