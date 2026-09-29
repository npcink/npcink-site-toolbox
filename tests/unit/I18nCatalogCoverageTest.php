<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

/**
 * 语言目录完整性门禁。
 *
 * en_US 语言包曾出现“源码新增文案未进 po”的静默漂移（3.3.2 隐私披露、
 * 搜索索引标签等）。composer i18n:build 的未翻译检查只在本地执行，
 * 本测试把同类检查拉进 PHPUnit：所有可静态提取的中文文案都必须在
 * en_US.po 中有非空译文，且 po 内不允许存在未翻译条目。
 */
final class I18nCatalogCoverageTest extends TestCase
{
    public function test_english_catalog_has_no_untranslated_entries(): void
    {
        $catalog = self::parse_po(self::po_path());

        foreach ($catalog as $msgid => $msgstr) {
            $this->assertNotSame('', $msgstr, "en_US.po 存在未翻译条目：{$msgid}");
        }
        $this->assertNotEmpty($catalog);
    }

    public function test_every_extractable_source_string_is_translated(): void
    {
        $catalog = self::parse_po(self::po_path());
        $missing = array();

        foreach (array_keys(self::collect_source_messages()) as $message) {
            if (!isset($catalog[$message]) || $catalog[$message] === '') {
                $missing[$message] = true;
            }
        }

        $missing_list = implode("\n  - ", array_keys($missing));
        $this->assertSame(
            '',
            $missing_list,
            "以下源码文案未进入 en_US.po，请运行 composer i18n:pot 并补全翻译：\n  - {$missing_list}"
        );
    }

    private static function plugin_root(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function po_path(): string
    {
        return self::plugin_root() . '/languages/npcink-site-toolbox-en_US.po';
    }

    /**
     * 收集与发布 POT 同源的中文文案：
     * 1. bin/export-i18n-metadata.php 的输出（注册表、隐私披露、设置契约、React 源码）；
     * 2. PHP/JS 源码中的 __() 字面量（与 wp i18n make-pot 的扫描面一致）。
     *
     * @return array<string, true>
     */
    private static function collect_source_messages(): array
    {
        $root = self::plugin_root();
        $messages = array();

        $add = static function ($message) use (&$messages): void {
            if (
                is_string($message)
                && $message !== ''
                && preg_match('/[\x{4e00}-\x{9fff}]/u', $message) === 1
                && !preg_match('~https?://~i', $message)
            ) {
                $messages[$message] = true;
            }
        };

        // 1) 元数据导出流（与 make-pot 的 JS 抽取源保持同一实现）
        ob_start();
        include $root . '/bin/export-i18n-metadata.php';
        $metadata_stream = (string) ob_get_clean();
        foreach (preg_split('/\r\n|\n|\r/', $metadata_stream) ?: array() as $line) {
            if (preg_match('/^__\((\'(?:\\\\.|[^\'\\\\])*\'), "npcink-site-toolbox"\);$/', trim($line), $m)) {
                $decoded = json_decode($m[1]);
                if (is_string($decoded)) {
                    $add($decoded);
                }
            }
        }

        // 2) PHP/JS 源码字面量
        $scan_targets = array(
            $root . '/admin',
            $root . '/includes',
            $root . '/public',
            $root . '/blocks',
            $root . '/patterns',
            $root . '/npcink-site-toolbox.php',
            $root . '/uninstall.php',
        );

        $files = array();
        foreach ($scan_targets as $target) {
            if (!is_dir($target)) {
                if (is_file($target)) {
                    $files[] = $target;
                }
                continue;
            }
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($target, RecursiveDirectoryIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file_info) {
                if ($file_info->isFile() && preg_match('/\.(?:php|js)$/D', $file_info->getFilename()) === 1) {
                    $files[] = $file_info->getPathname();
                }
            }
        }

        $literal_pattern = '/__\(\s*(["\'])((?:\\\\.|(?!\1)[^\\\\])*)\1\s*,\s*([\'"])npcink-site-toolbox\3/s';
        foreach ($files as $file) {
            $source = (string) file_get_contents($file);
            if (preg_match_all($literal_pattern, $source, $matches, PREG_SET_ORDER)) {
                foreach ($matches as $match) {
                    $add(self::unescape($match[2], $match[1]));
                }
            }
        }

        return $messages;
    }

    private static function unescape(string $value, string $quote): string
    {
        if ($quote === "'") {
            return str_replace(array('\\\'', '\\\\'), array("'", '\\'), $value);
        }
        $decoded = json_decode('"' . str_replace('"', '\\"', $value) . '"');
        return is_string($decoded) ? $decoded : $value;
    }

    /**
     * @return array<string, string> msgid => msgstr（msgstr 为空的条目也会返回）
     */
    private static function parse_po(string $path): array
    {
        self::assertFileExists($path);
        $lines = preg_split('/\r\n|\n|\r/', (string) file_get_contents($path)) ?: array();

        $catalog = array();
        $current_id = null;
        $current_str = null;
        $mode = '';

        $flush = static function () use (&$catalog, &$current_id, &$current_str, &$mode): void {
            if ($current_id !== null && $current_id !== '') {
                $catalog[$current_id] = $current_str ?? '';
            } elseif ($current_id === '' && ($current_str ?? '') !== '') {
                // 头部条目，跳过
            }
            $current_id = null;
            $current_str = null;
            $mode = '';
        };

        foreach ($lines as $line) {
            if ($line === '') {
                $flush();
                continue;
            }
            if ($line[0] === '#') {
                continue;
            }
            if (preg_match('/^msgid ("(?:\\\\.|[^"\\\\])*")/', $line, $m)) {
                $flush();
                $current_id = self::decode_po_string($m[1]);
                $mode = 'id';
                continue;
            }
            if (preg_match('/^msgstr ("(?:\\\\.|[^"\\\\])*")/', $line, $m)) {
                $current_str = self::decode_po_string($m[1]);
                $mode = 'str';
                continue;
            }
            if ($current_id !== null && preg_match('/^("(?:\\\\.|[^"\\\\])*")/', $line, $m)) {
                $piece = self::decode_po_string($m[1]);
                if ($mode === 'id') {
                    $current_id .= $piece;
                } elseif ($mode === 'str') {
                    $current_str .= $piece;
                }
            }
        }
        $flush();

        return $catalog;
    }

    private static function decode_po_string(string $quoted): string
    {
        $decoded = json_decode($quoted);
        return is_string($decoded) ? $decoded : '';
    }
}
