<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('add_action')) {
    function add_action($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        $GLOBALS['_test_mabox_actions'][] = array(
            'hook' => $hook_name,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        );
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook_name, $callback, $priority = 10, $accepted_args = 1)
    {
        $GLOBALS['_test_mabox_filters'][] = array(
            'hook' => $hook_name,
            'callback' => $callback,
            'priority' => $priority,
            'accepted_args' => $accepted_args,
        );
        return true;
    }
}

if (!function_exists('remove_action')) {
    function remove_action($hook_name, $callback, $priority = 10)
    {
        $GLOBALS['_test_mabox_removed_actions'][] = array($hook_name, $callback, $priority);
        $GLOBALS['_test_mabox_actions'] = array_values(array_filter(
            $GLOBALS['_test_mabox_actions'],
            function ($hook) use ($hook_name, $callback, $priority) {
                return $hook['hook'] !== $hook_name
                    || $hook['callback'] !== $callback
                    || $hook['priority'] !== $priority;
            }
        ));
        return true;
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter($hook_name, $callback, $priority = 10)
    {
        $GLOBALS['_test_mabox_removed_filters'][] = array($hook_name, $callback, $priority);
        $GLOBALS['_test_mabox_filters'] = array_values(array_filter(
            $GLOBALS['_test_mabox_filters'],
            function ($hook) use ($hook_name, $callback, $priority) {
                return $hook['hook'] !== $hook_name
                    || $hook['callback'] !== $callback
                    || $hook['priority'] !== $priority;
            }
        ));
        return true;
    }
}

if (!function_exists('get_taxonomy')) {
    function get_taxonomy($taxonomy)
    {
        return $taxonomy === 'category' ? $GLOBALS['_test_category_taxonomy'] : false;
    }
}

class MaBox_Test_Category_Rewrite
{
    public $extra_permastructs = array(
        'category' => array('struct' => 'category/%category%'),
    );
    public $pagination_base = 'page';
    public $flush_count = 0;

    public function flush_rules()
    {
        ++$this->flush_count;
    }
}

class MaBox_Test_Category_Taxonomy
{
    public $restore_count = 0;

    public function add_rewrite_rules()
    {
        global $wp_rewrite;
        ++$this->restore_count;
        $wp_rewrite->extra_permastructs['category']['struct'] = 'category/%category%';
    }
}

require_once dirname(__DIR__, 2) . '/includes/interface-mabox-module.php';
require_once dirname(__DIR__, 2) . '/admin/partials/optimize/site/category_link_simplify.php';

class RewriteLifecycleTest extends TestCase
{
    protected function setUp(): void
    {
        global $wp_rewrite;

        $GLOBALS['_test_option_store'] = array();
        $GLOBALS['_test_mabox_actions'] = array();
        $GLOBALS['_test_mabox_filters'] = array();
        $GLOBALS['_test_mabox_removed_actions'] = array();
        $GLOBALS['_test_mabox_removed_filters'] = array();
        $GLOBALS['_test_category_taxonomy'] = new MaBox_Test_Category_Taxonomy();
        $GLOBALS['wp_version'] = '7.0.1';
        $wp_rewrite = new MaBox_Test_Category_Rewrite();
    }

    public function test_main_file_registers_lifecycle_hooks_at_top_level(): void
    {
        $main = file_get_contents(dirname(__DIR__, 2) . '/magick-tool-box.php');
        $module = file_get_contents(dirname(__DIR__, 2) . '/admin/partials/optimize/site/category_link_simplify.php');

        $this->assertStringContainsString(
            "register_activation_hook(__FILE__, array('MaBox_Category_Link_Simplify', 'activate'))",
            $main
        );
        $this->assertStringContainsString(
            "register_deactivation_hook(__FILE__, array('MaBox_Category_Link_Simplify', 'deactivate'))",
            $main
        );
        $this->assertStringContainsString("'update_option_' . MAGICK_MIXTURE_OPTION_OPTIMIZE", $main);
        $this->assertStringNotContainsString('register_activation_hook', $module);
        $this->assertStringNotContainsString('register_deactivation_hook', $module);
    }

    public function test_activation_applies_and_flushes_only_when_enabled(): void
    {
        global $wp_rewrite;

        $GLOBALS['_test_option_store'][MAGICK_MIXTURE_OPTION_OPTIMIZE] = array(
            'site' => array('category_link_simplify' => false),
        );
        MaBox_Category_Link_Simplify::activate();

        $this->assertSame('category/%category%', $wp_rewrite->extra_permastructs['category']['struct']);
        $this->assertSame(0, $wp_rewrite->flush_count);

        $GLOBALS['_test_option_store'][MAGICK_MIXTURE_OPTION_OPTIMIZE]['site']['category_link_simplify'] = true;
        MaBox_Category_Link_Simplify::activate();

        $this->assertSame('%category%', $wp_rewrite->extra_permastructs['category']['struct']);
        $this->assertSame(1, $wp_rewrite->flush_count);
        $this->assertHookRegistered(
            $GLOBALS['_test_mabox_filters'],
            'category_rewrite_rules',
            array('MaBox_Category_Link_Simplify', 'no_category_base_rewrite_rules')
        );
    }

    public function test_deactivation_removes_exact_callbacks_restores_core_and_flushes(): void
    {
        global $wp_rewrite;

        MaBox_Category_Link_Simplify::run();
        $wp_rewrite->extra_permastructs['category']['struct'] = '%category%';

        MaBox_Category_Link_Simplify::deactivate();

        $this->assertSame('category/%category%', $wp_rewrite->extra_permastructs['category']['struct']);
        $this->assertSame(1, $GLOBALS['_test_category_taxonomy']->restore_count);
        $this->assertSame(1, $wp_rewrite->flush_count);
        $this->assertSame(array(), $GLOBALS['_test_mabox_actions']);
        $this->assertSame(array(), $GLOBALS['_test_mabox_filters']);
        $this->assertContains(
            array(
                'category_rewrite_rules',
                array('MaBox_Category_Link_Simplify', 'no_category_base_rewrite_rules'),
                10,
            ),
            $GLOBALS['_test_mabox_removed_filters']
        );
    }

    public function test_option_update_enables_on_false_to_true_transition(): void
    {
        global $wp_rewrite;

        MaBox_Category_Link_Simplify::handle_optimize_option_update(
            array('site' => array('category_link_simplify' => false)),
            array('site' => array('category_link_simplify' => true))
        );

        $this->assertSame('%category%', $wp_rewrite->extra_permastructs['category']['struct']);
        $this->assertSame(1, $wp_rewrite->flush_count);
        $this->assertHookRegistered(
            $GLOBALS['_test_mabox_filters'],
            'category_rewrite_rules',
            array('MaBox_Category_Link_Simplify', 'no_category_base_rewrite_rules')
        );
    }

    public function test_option_update_disables_on_true_to_false_transition(): void
    {
        global $wp_rewrite;

        MaBox_Category_Link_Simplify::run();
        $wp_rewrite->extra_permastructs['category']['struct'] = '%category%';

        MaBox_Category_Link_Simplify::handle_optimize_option_update(
            array('site' => array('category_link_simplify' => true)),
            array('site' => array('category_link_simplify' => false))
        );

        $this->assertSame('category/%category%', $wp_rewrite->extra_permastructs['category']['struct']);
        $this->assertSame(1, $GLOBALS['_test_category_taxonomy']->restore_count);
        $this->assertSame(1, $wp_rewrite->flush_count);
        $this->assertSame(array(), $GLOBALS['_test_mabox_actions']);
        $this->assertSame(array(), $GLOBALS['_test_mabox_filters']);
    }

    public function test_option_update_does_not_flush_without_an_actual_boolean_transition(): void
    {
        global $wp_rewrite;

        MaBox_Category_Link_Simplify::handle_optimize_option_update(
            array('site' => array('category_link_simplify' => false, 'other' => 'old')),
            array('site' => array('category_link_simplify' => false, 'other' => 'new'))
        );
        MaBox_Category_Link_Simplify::handle_optimize_option_update(
            array('site' => array('category_link_simplify' => true, 'other' => 'old')),
            array('site' => array('category_link_simplify' => true, 'other' => 'new'))
        );
        MaBox_Category_Link_Simplify::handle_optimize_option_update(
            array('site' => array('category_link_simplify' => 'true')),
            array('site' => array('category_link_simplify' => 1))
        );
        MaBox_Category_Link_Simplify::handle_optimize_option_update(
            array('site' => array('category_link_simplify' => 'true')),
            array('site' => array('category_link_simplify' => true))
        );
        MaBox_Category_Link_Simplify::handle_optimize_option_update(
            array('site' => array('category_link_simplify' => false)),
            array('site' => array('category_link_simplify' => 1))
        );

        $this->assertSame(0, $wp_rewrite->flush_count);
        $this->assertSame(0, $GLOBALS['_test_category_taxonomy']->restore_count);
        $this->assertSame(array(), $GLOBALS['_test_mabox_actions']);
        $this->assertSame(array(), $GLOBALS['_test_mabox_filters']);
    }

    private function assertHookRegistered($hooks, $hook_name, $callback): void
    {
        foreach ($hooks as $hook) {
            if ($hook['hook'] === $hook_name && $hook['callback'] === $callback) {
                $this->addToAssertionCount(1);
                return;
            }
        }

        $this->fail('Expected hook was not registered: ' . $hook_name);
    }
}
