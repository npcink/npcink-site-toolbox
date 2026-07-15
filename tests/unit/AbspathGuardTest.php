<?php

use PHPUnit\Framework\TestCase;

/**
 * 直接访问保护测试
 *
 * 验证所有 PHP 文件都包含 defined('ABSPATH') || exit;
 */
class AbspathGuardTest extends TestCase {

    /**
     * 获取所有需要检查的 PHP 文件
     */
    private function get_php_files(): array {
        $root = dirname(__DIR__, 2);
        $files = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        // ai/ 保存 reference-only 快照，并由发布包规则整体排除，不属于插件运行源码。
        $exclude_dirs = array('vendor', 'node_modules', '.git', '.opencode', '.sisyphus', '.vscode', '.github', 'tests', 'vite', 'admin/partials', 'ai');

        foreach ($iterator as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $path = $file->getPathname();
            $relative = str_replace($root . '/', '', $path);

            // 排除不需要检查的目录
            $skip = false;
            foreach ($exclude_dirs as $dir) {
                if (strpos($relative, $dir . '/') === 0 || strpos($relative, $dir) === 0) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            // 排除标准 WordPress index.php（仅包含 Silence is golden）
            if (basename($relative) === 'index.php') {
                $content = file_get_contents($path);
                if (strpos($content, "Silence is golden") !== false || strpos($content, "沉默是金") !== false) {
                    continue;
                }
            }

            $files[] = $relative;
        }

        return $files;
    }

    /**
     * 测试所有 PHP 文件都有直接访问保护
     */
    public function test_all_php_files_have_direct_access_guard(): void {
        $root = dirname(__DIR__, 2);
        $files = $this->get_php_files();
        $missing = array();

        foreach ($files as $relative) {
            $content = file_get_contents($root . '/' . $relative);
            $has_guard = strpos($content, "defined('ABSPATH')") !== false
                      || strpos($content, "defined('WPINC')") !== false;
            if (!$has_guard) {
                $missing[] = $relative;
            }
        }

        $this->assertEmpty(
            $missing,
            '以下 PHP 文件缺少直接访问保护（ABSPATH 或 WPINC 检查）：' . PHP_EOL . implode(PHP_EOL, $missing)
        );
    }

    /**
     * 测试核心入口文件有双重保护
     */
    public function test_main_plugin_file_has_dual_protection(): void {
        $main_file = dirname(__DIR__, 2) . '/magick-tool-box.php';
        $content = file_get_contents($main_file);

        $this->assertStringContainsString("defined('WPINC')", $content, '主文件应该有 WPINC 检查');
        $this->assertStringContainsString("defined('ABSPATH')", $content, '主文件应该有 ABSPATH 检查');
    }

    public function test_main_plugin_file_loads_autoloader_before_core_class(): void {
        $main_file = dirname(__DIR__, 2) . '/magick-tool-box.php';
        $content = file_get_contents($main_file);

        $autoload_pos = strpos($content, "includes/autoload.php");
        $core_pos = strpos($content, "includes/class-magick-mixture.php");

        $this->assertNotFalse($autoload_pos, '主文件应该加载 includes/autoload.php');
        $this->assertNotFalse($core_pos, '主文件应该加载核心插件类');
        $this->assertLessThan($core_pos, $autoload_pos, '自动加载器应该先于核心插件类加载');
    }
}
