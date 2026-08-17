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
        $main = (string) file_get_contents($root . '/npcink-site-toolbox.php');
        $plugin = (string) file_get_contents($root . '/includes/class-npcink-site-toolbox.php');

        $this->assertStringContainsString('Domain Path:       /languages', $main);
        $this->assertStringContainsString("load_textdomain('npcink-site-toolbox', \$mofile)", $plugin);
        $this->assertStringContainsString("'/languages/npcink-site-toolbox-' . \$locale . '.mo'", $plugin);
        $this->assertStringContainsString("array('wp-i18n')", $admin);
        $this->assertStringContainsString("wp_set_script_translations(\$name, 'npcink-site-toolbox'", $admin);
        $this->assertStringContainsString('window.wp?.i18n?.__(text, "npcink-site-toolbox")', $helper);
        $this->assertSame('bash bin/make-pot.sh', $composer['scripts']['i18n:pot'] ?? null);
        $this->assertSame('bash bin/build-language-pack.sh', $composer['scripts']['i18n:build'] ?? null);
    }

    public function test_english_language_pack_is_complete_and_targets_runtime_scripts(): void
    {
        $root = dirname(__DIR__, 2);
        $po = (string) file_get_contents($root . '/languages/npcink-site-toolbox-en_US.po');
        $adminJson = json_decode((string) file_get_contents(
            $root . '/languages/npcink-site-toolbox-en_US-be96897d1813598cc6ffe96654a4f062.json'
        ), true);
        $countdownJson = json_decode((string) file_get_contents(
            $root . '/languages/npcink-site-toolbox-en_US-d4372d764458b4d5899ad1740400c0a9.json'
        ), true);
        $app = (string) file_get_contents($root . '/vite/admin/src/App.tsx');
        $admin = (string) file_get_contents($root . '/admin/class-npcink-toolbox-admin.php');

        $this->assertFileExists($root . '/languages/npcink-site-toolbox-en_US.mo');
        $this->assertStringContainsString('"Language: en_US\\n"', $po);
        $this->assertSame('Save', $adminJson['locale_data']['messages']['保存'][0] ?? null);
        $this->assertSame('Countdown complete', $countdownJson['locale_data']['messages']['倒计时结束'][0] ?? null);
        $this->assertStringContainsString("'locale' => determine_locale()", $admin);
        $this->assertStringContainsString('import enUS from "antd/locale/en_US"', $app);
        $this->assertStringContainsString('locale.toLowerCase().startsWith("en") ? enUS : zhCN', $app);
    }

    public function test_pot_contains_php_and_admin_javascript_messages(): void
    {
        $pot = (string) file_get_contents(dirname(__DIR__, 2) . '/languages/npcink-site-toolbox.pot');

        foreach (array(
            '保存成功',
            'SEO 检查助手',
            '数据库清理与优化',
            '媒体库健康体检',
            '升级维护中',
            '倒计时结束后即可正常访问',
            '倒计时结束',
            '请在倒计时结束后再回来，我们正在准备新的内容。',
            '隐藏顶部工具条',
            '评论过于频繁，请 %d 秒后再试。',
            '数据库表优化完成',
            'Npcink Site Toolbox - 站点统计',
            '最后编辑于：',
            '登录可见',
            'AI 诊断与分析',
            '备案与合规',
        ) as $message) {
            $this->assertStringContainsString('msgid "' . $message . '"', $pot, $message);
        }

        foreach (array(
            'Gravatar 头像替换',
            'Google 字体替换',
            '中国访问适配',
        ) as $retired_message) {
            $this->assertStringNotContainsString('msgid "' . $retired_message . '"', $pot, $retired_message);
        }

        $this->assertStringNotContainsString('const App: React.FC', $pot);
        $this->assertStringNotContainsString('useState<MediaWebpAssessment', $pot);
    }

    public function test_public_maintenance_countdown_loads_the_translation_runtime(): void
    {
        $root = dirname(__DIR__, 2);
        $template = (string) file_get_contents($root . '/admin/partials/page/function/maintenance/red.php');
        $script = (string) file_get_contents($root . '/admin/partials/page/function/maintenance/countdown/main.js');
        $potBuilder = (string) file_get_contents($root . '/bin/make-pot.sh');

        $this->assertStringContainsString("array('wp-i18n')", $template);
        $this->assertStringContainsString("wp_set_script_translations(\n            'mabox-maintenance-countdown-script'", $template);
        $this->assertStringContainsString('__("倒计时结束", "npcink-site-toolbox")', $script);
        $this->assertStringContainsString('maintenance/countdown/main.js', $potBuilder);
        $this->assertStringContainsString('export-i18n-metadata.php', $potBuilder);
    }

    public function test_remaining_backend_i18n_surfaces_are_registered_in_the_inventory(): void
    {
        $root = dirname(__DIR__, 2);
        $inventory = (string) file_get_contents($root . '/docs/后台与PHP国际化实施清单-2026-08.md');

        foreach (array(
            'admin/partials/page/function/maintenance/index.php',
            'admin/partials/page/function/maintenance_tips.php',
            'admin/partials/page/function/maintenance/countdown/main.js',
            'admin/partials/performance/db_clean/index.php',
            'admin/partials/performance/media_health/webp_batch.php',
            'admin/partials/performance/seo_checker/index.php',
            'admin/modules/registry.php',
            'vite/admin/src/components/feature-search.tsx',
        ) as $surface) {
            $this->assertStringContainsString($surface, $inventory, $surface);
        }

        $this->assertStringContainsString('用户在设置中填写的标题、正文、维护提示', $inventory);
        $this->assertStringContainsString('REST error code、设置路径、模块 ID', $inventory);
        $this->assertStringContainsString('每批完成条件', $inventory);
    }
}
