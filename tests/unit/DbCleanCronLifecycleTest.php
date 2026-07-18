<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_get_scheduled_event')) {
    function wp_get_scheduled_event($hook, $args = array(), $timestamp = null)
    {
        return $GLOBALS['_test_cron_events'][$hook] ?? false;
    }
}

if (!function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook($hook, $args = array(), $wp_error = false)
    {
        $removed = isset($GLOBALS['_test_cron_events'][$hook]) ? 1 : 0;
        unset($GLOBALS['_test_cron_events'][$hook]);
        $GLOBALS['_test_cron_clear_calls'][] = $hook;
        return $removed;
    }
}

if (!function_exists('wp_schedule_event')) {
    function wp_schedule_event($timestamp, $recurrence, $hook, $args = array(), $wp_error = false)
    {
        $event = (object) array(
            'hook' => $hook,
            'timestamp' => $timestamp,
            'schedule' => $recurrence,
        );
        $GLOBALS['_test_cron_events'][$hook] = $event;
        $GLOBALS['_test_cron_schedule_calls'][] = $event;
        return true;
    }
}

class DbCleanCronLifecycleTest extends TestCase
{
    private const CRON_HOOK = 'npcink_site_toolbox_auto_db_clean';

    protected function setUp(): void
    {
        global $_test_option_store;

        $_test_option_store = array();
        $GLOBALS['_test_cron_events'] = array();
        $GLOBALS['_test_cron_clear_calls'] = array();
        $GLOBALS['_test_cron_schedule_calls'] = array();
    }

    public function test_enabled_auto_cleanup_is_scheduled(): void
    {
        Npcink_Toolbox_Performance_Db_Clean::run(array(
            'enabled' => true,
            'auto_clean' => true,
            'auto_clean_schedule' => 'weekly',
        ));

        $this->assertSame('weekly', $GLOBALS['_test_cron_events'][self::CRON_HOOK]->schedule);
        $this->assertCount(1, $GLOBALS['_test_cron_schedule_calls']);
    }

    public function test_schedule_change_clears_old_event_before_rescheduling(): void
    {
        $GLOBALS['_test_cron_events'][self::CRON_HOOK] = (object) array(
            'hook' => self::CRON_HOOK,
            'timestamp' => time(),
            'schedule' => 'weekly',
        );

        Npcink_Toolbox_Performance_Db_Clean::run(array(
            'enabled' => true,
            'auto_clean' => true,
            'auto_clean_schedule' => 'monthly',
        ));

        $this->assertSame(array(self::CRON_HOOK), $GLOBALS['_test_cron_clear_calls']);
        $this->assertSame('monthly', $GLOBALS['_test_cron_events'][self::CRON_HOOK]->schedule);
        $this->assertCount(1, $GLOBALS['_test_cron_schedule_calls']);
    }

    public function test_disabling_module_via_option_update_clears_schedule(): void
    {
        $GLOBALS['_test_cron_events'][self::CRON_HOOK] = (object) array(
            'hook' => self::CRON_HOOK,
            'timestamp' => time(),
            'schedule' => 'weekly',
        );

        Npcink_Toolbox_Performance_Db_Clean::handle_performance_option_update(
            array(),
            array('db_clean' => array('enabled' => false, 'auto_clean' => false))
        );

        $this->assertArrayNotHasKey(self::CRON_HOOK, $GLOBALS['_test_cron_events']);
        $this->assertSame(array(self::CRON_HOOK), $GLOBALS['_test_cron_clear_calls']);
    }

    public function test_cron_callback_uses_latest_persisted_schedule(): void
    {
        global $_test_option_store;

        $GLOBALS['_test_cron_events'][self::CRON_HOOK] = (object) array(
            'hook' => self::CRON_HOOK,
            'timestamp' => time(),
            'schedule' => 'weekly',
        );
        $_test_option_store[NPCINK_SITE_TOOLBOX_OPTION_PERFORMANCE] = array(
            'db_clean' => array(
                'enabled' => true,
                'auto_clean' => true,
                'auto_clean_schedule' => 'monthly',
            ),
        );

        Npcink_Toolbox_Performance_Db_Clean::run_scheduled_cleanup();

        $this->assertSame('monthly', $GLOBALS['_test_cron_events'][self::CRON_HOOK]->schedule);
    }

    public function test_cron_callback_fails_closed_when_configuration_is_missing(): void
    {
        $GLOBALS['_test_cron_events'][self::CRON_HOOK] = (object) array(
            'hook' => self::CRON_HOOK,
            'timestamp' => time(),
            'schedule' => 'weekly',
        );

        Npcink_Toolbox_Performance_Db_Clean::run_scheduled_cleanup();

        $this->assertArrayNotHasKey(self::CRON_HOOK, $GLOBALS['_test_cron_events']);
    }

    public function test_plugin_lifecycle_registers_runtime_and_teardown_hooks(): void
    {
        $plugin_source = file_get_contents(dirname(__DIR__, 2) . '/npcink-site-toolbox.php');
        $uninstall_source = file_get_contents(dirname(__DIR__, 2) . '/uninstall.php');

        $this->assertIsString($plugin_source);
        $this->assertIsString($uninstall_source);
        $this->assertStringContainsString(
            "add_action('npcink_site_toolbox_auto_db_clean', array('Npcink_Toolbox_Performance_Db_Clean', 'run_scheduled_cleanup'));",
            $plugin_source
        );
        $this->assertStringContainsString(
            "array('Npcink_Toolbox_Performance_Db_Clean', 'handle_performance_option_update')",
            $plugin_source
        );
        $this->assertStringContainsString(
            "register_deactivation_hook(__FILE__, array('Npcink_Toolbox_Performance_Db_Clean', 'clear_schedule'));",
            $plugin_source
        );
        $this->assertStringContainsString(
            "wp_clear_scheduled_hook('npcink_site_toolbox_auto_db_clean');",
            $uninstall_source
        );
    }
}
