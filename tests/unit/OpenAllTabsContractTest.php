<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class OpenAllTabsContractTest extends TestCase
{
    public function test_either_switch_activates_the_module_and_both_disabled_do_not(): void
    {
        $this->assertSame('optimize.admin', Npcink_Toolbox_Module_Loader::get_module_meta('optimize.admin_open_all_edit_tabs')['config_path']);
        foreach (array(array(true, false), array(false, true), array(true, true), array(false, false)) as $switches) {
            $config = array('optimize' => array('admin' => array(
                'open_all_edit_tabs' => $switches[0],
                'open_all_view_tabs' => $switches[1],
            )));
            $active = Npcink_Toolbox_Module_Loader::get_active_modules($config);
            $this->assertSame($switches[0] || $switches[1], in_array('optimize.admin_open_all_edit_tabs', $active, true));
        }
        $this->assertFalse(Npcink_Toolbox_Config_Schema::get_defaults()['optimize']['admin']['open_all_view_tabs']);
    }

    /** @dataProvider listScreenCases */
    public function test_script_loading_and_localized_switches_follow_the_actual_screen_and_config($screen, $hook, $switches, $expected): void
    {
        // Run with isolated WordPress stubs to avoid other modules' test-global enqueue functions.
        $root = dirname(__DIR__, 2);
        $source = 'define("ABSPATH", ' . var_export($root . '/', true) . ');'
            . 'define("NPCINK_SITE_TOOLBOX_VERSION", "test");'
            . '$screen = ' . var_export($screen === null ? null : (object) $screen, true) . ';'
            . '$config = ' . var_export($switches, true) . ';'
            . '$hook = ' . var_export($hook, true) . ';'
            . 'require ' . var_export($root . '/includes/interface-npcink-toolbox-module.php', true) . ';'
            . 'require ' . var_export($root . '/admin/partials/optimize/admin/open_all_edit_tabs.php', true) . ';'
            . <<<'PHP'
function add_action($hook, $callback) {}
function get_current_screen() { return $GLOBALS['screen']; }
function plugins_url($file, $path) { return $file; }
function __($text, $domain) { return $text; }
function wp_enqueue_script($handle, $src, $deps, $version, $footer) {
    $GLOBALS['enqueued'][] = $handle;
}
function wp_localize_script($handle, $name, $data) { $GLOBALS['localized'] = $data; }
Npcink_Toolbox_Admin_Open_All_Edit_Tabs::run($config);
Npcink_Toolbox_Admin_Open_All_Edit_Tabs::enqueue_script($hook);
echo json_encode(array('enqueued' => $GLOBALS['enqueued'] ?? array(), 'localized' => $GLOBALS['localized'] ?? null));
PHP;
        $output = array();
        $status = 1;
        exec(escapeshellarg(PHP_BINARY) . ' -r ' . escapeshellarg($source) . ' 2>&1', $output, $status);
        $this->assertSame(0, $status, implode("\n", $output));
        $result = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($expected ? array('npcink-site-toolbox-open-all-edit-tabs') : array(), $result['enqueued']);
        if ($expected) {
            $this->assertSame(array('edit' => $switches['open_all_edit_tabs'], 'view' => $switches['open_all_view_tabs']), $result['localized']['actions']);
        } else {
            $this->assertNull($result['localized']);
        }
    }

    public function listScreenCases(): array
    {
        $edit = array('open_all_edit_tabs' => true, 'open_all_view_tabs' => false);
        $view = array('open_all_edit_tabs' => false, 'open_all_view_tabs' => true);
        $both = array('open_all_edit_tabs' => true, 'open_all_view_tabs' => true);
        $none = array('open_all_edit_tabs' => false, 'open_all_view_tabs' => false);
        return array(
            'posts' => array(array('base' => 'edit', 'post_type' => 'post'), 'edit.php', $edit, true),
            'pages view only' => array(array('base' => 'edit', 'post_type' => 'page'), 'edit.php', $view, true),
            'custom portfolio both' => array(array('base' => 'edit', 'post_type' => 'portfolio'), 'edit.php', $both, true),
            'custom portfolio view only' => array(array('base' => 'edit', 'post_type' => 'portfolio'), 'edit.php', $view, true),
            'switches disabled' => array(array('base' => 'edit', 'post_type' => 'post'), 'edit.php', $none, false),
            'media' => array(array('base' => 'upload', 'post_type' => 'attachment'), 'upload.php', $both, false),
            'wrong screen' => array(array('base' => 'edit-tags', 'post_type' => 'post'), 'edit.php', $both, false),
            'missing post type' => array(array('base' => 'edit', 'post_type' => ''), 'edit.php', $both, false),
            'missing screen' => array(null, 'edit.php', $both, false),
        );
    }
}
