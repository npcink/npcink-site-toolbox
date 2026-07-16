<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class ModuleRegistryWpWidgetStub {
}

class ModuleRegistryConsistency_Test extends TestCase {

    private static $plugin_dir;

    public static function setUpBeforeClass(): void {
        self::$plugin_dir = dirname(__DIR__, 2);
    }

    public function test_registry_module_files_exist(): void {
        $registry = MaBox_Module_Loader::get_registry();
        $partials_dir = self::$plugin_dir . '/admin/partials/';

        foreach ($registry as $module_id => $meta) {
            $file = $partials_dir . $meta['file'];
            $this->assertFileExists(
                $file,
                "Module '$module_id' file does not exist at: {$meta['file']}"
            );
        }
    }

    public function test_every_registered_module_implements_the_runtime_contract(): void {
        if (!class_exists('WP_Widget')) {
            class_alias('ModuleRegistryWpWidgetStub', 'WP_Widget');
        }

        $registry = MaBox_Module_Loader::get_registry();
        $partials_dir = self::$plugin_dir . '/admin/partials/';

        foreach ($registry as $module_id => $meta) {
            require_once $partials_dir . $meta['file'];

            $this->assertTrue(
                class_exists($meta['class']),
                "Module '$module_id' class '{$meta['class']}' should exist"
            );

            $class = new ReflectionClass($meta['class']);
            $this->assertTrue(
                $class->implementsInterface('MaBox_Module_Interface'),
                "Module '$module_id' should implement MaBox_Module_Interface"
            );
            $this->assertFalse(
                $class->hasMethod('runs'),
                "Module '$module_id' should not expose the retired runs() entrypoint"
            );
            $this->assertTrue(
                $class->hasMethod('run'),
                "Module '$module_id' should expose run()"
            );

            $method = $class->getMethod('run');
            $this->assertTrue($method->isPublic(), "Module '$module_id' run() should be public");
            $this->assertTrue($method->isStatic(), "Module '$module_id' run() should be static");

            $parameters = $method->getParameters();
            $this->assertCount(1, $parameters, "Module '$module_id' run() should accept one config argument");
            $this->assertSame('config', $parameters[0]->getName(), "Module '$module_id' should name its argument config");
            $this->assertTrue($parameters[0]->isOptional(), "Module '$module_id' config should be optional");
            $this->assertTrue($parameters[0]->isDefaultValueAvailable(), "Module '$module_id' config should have a default");
            $this->assertSame(array(), $parameters[0]->getDefaultValue(), "Module '$module_id' config should default to an empty array");
        }
    }

    public function test_parameterized_modules_receive_their_configuration_subtree(): void {
        $registry = MaBox_Module_Loader::get_registry();
        $expected_paths = array(
            'page.maintenance_tips'       => 'page.function',
            'page.hide_category'          => 'page.jurisdiction',
            'page.hide_tag'               => 'page.jurisdiction',
            'page.hide_page'              => 'page.jurisdiction',
            'auxiliary.ban_malice_search' => 'function.auxiliary',
            'auxiliary.baidu_tonji'       => 'function.auxiliary',
            'auxiliary.google_tonji'      => 'function.auxiliary',
            'auxiliary.biying_tonji'      => 'function.auxiliary',
            'domestic.login_security'     => 'domestic.login_security',
        );

        foreach ($expected_paths as $module_id => $config_path) {
            $this->assertSame(
                $config_path,
                isset($registry[$module_id]['config_path']) ? $registry[$module_id]['config_path'] : null,
                "Module '$module_id' should receive '$config_path'"
            );
        }
    }

    public function test_domestic_login_security_has_exact_activation_and_schema_contracts(): void {
        $registry = MaBox_Module_Loader::get_registry();
        $schema = MaBox_Config_Schema::get_schema();
        $meta = $registry['domestic.login_security'];
        $login_schema = $schema['domestic']['login_security'];

        $activation_paths = array(
            'domestic.login_security.attempt_limit_enabled',
            'domestic.login_security.anonymous_author_guard_enabled',
        );
        $this->assertSame($activation_paths[0], $meta['option_key']);
        $this->assertSame($activation_paths, $meta['activation_paths']);
        $this->assertSame(
            array(
                'attempt_limit_enabled',
                'attempt_limit_count',
                'attempt_window_minutes',
                'lock_duration_minutes',
                'trusted_proxies',
                'anonymous_author_guard_enabled',
            ),
            array_keys($login_schema)
        );

        foreach ($activation_paths as $path) {
            $field = substr($path, strlen('domestic.login_security.'));
            $this->assertSame('boolean', $login_schema[$field]['type']);
        }

        $this->assertSame(5, $login_schema['attempt_limit_count']['default']);
        $this->assertSame(2, $login_schema['attempt_limit_count']['min']);
        $this->assertSame(20, $login_schema['attempt_limit_count']['max']);
        $this->assertTrue($login_schema['attempt_limit_count']['integer']);
        $this->assertSame(15, $login_schema['attempt_window_minutes']['default']);
        $this->assertSame(1, $login_schema['attempt_window_minutes']['min']);
        $this->assertSame(1440, $login_schema['attempt_window_minutes']['max']);
        $this->assertTrue($login_schema['attempt_window_minutes']['integer']);
        $this->assertSame(30, $login_schema['lock_duration_minutes']['default']);
        $this->assertSame(1, $login_schema['lock_duration_minutes']['min']);
        $this->assertSame(1440, $login_schema['lock_duration_minutes']['max']);
        $this->assertTrue($login_schema['lock_duration_minutes']['integer']);
        $this->assertSame('', $login_schema['trusted_proxies']['default']);
        $this->assertSame('ip_list', $login_schema['trusted_proxies']['format']);
    }

    public function test_loader_has_no_legacy_runs_fallback(): void {
        $loader = file_get_contents(self::$plugin_dir . '/admin/modules/loader.php');

        $this->assertStringNotContainsString("method_exists(\$class, 'runs')", $loader);
        $this->assertStringNotContainsString('run() or runs()', $loader);
    }

    public function test_autoloader_maps_the_module_interface(): void {
        $autoload = file_get_contents(self::$plugin_dir . '/includes/autoload.php');

        $this->assertStringContainsString(
            "'MaBox_Module_Interface' => 'includes/interface-mabox-module.php'",
            $autoload
        );
    }

    public function test_no_escape_module_file_exists(): void {
        $file = self::$plugin_dir . '/admin/partials/optimize/site/no_escape.php';
        $this->assertFileExists($file);
    }

    public function test_no_escape_class_exists(): void {
        require_once self::$plugin_dir . '/admin/partials/optimize/site/no_escape.php';
        $this->assertTrue(class_exists('MaBox_No_Escape'));
    }

    public function test_no_escape_implements_interface(): void {
        $this->assertTrue(
            is_subclass_of('MaBox_No_Escape', 'MaBox_Module_Interface'),
            'MaBox_No_Escape should implement MaBox_Module_Interface'
        );
    }

    public function test_no_escape_has_run_method(): void {
        $this->assertTrue(method_exists('MaBox_No_Escape', 'run'));
    }

    public function test_h5_main_removed_from_registry(): void {
        $registry = MaBox_Module_Loader::get_registry();
        $this->assertArrayNotHasKey('h5.main', $registry);
    }

    public function test_function_b2_removed_from_registry(): void {
        $registry = MaBox_Module_Loader::get_registry();
        $this->assertArrayNotHasKey('function.b2', $registry);
    }

    public function test_h5_main_removed_from_tiers(): void {
        $tiers = MaBox_Module_Loader::get_tiers();
        foreach ($tiers as $tier => $modules) {
            $this->assertNotContains('h5.main', $modules, "h5.main should not be in tier '$tier'");
        }
    }

    public function test_function_b2_removed_from_tiers(): void {
        $tiers = MaBox_Module_Loader::get_tiers();
        foreach ($tiers as $tier => $modules) {
            $this->assertNotContains('function.b2', $modules, "function.b2 should not be in tier '$tier'");
        }
    }

    public function test_ai_review_runtime_removed_from_backend_contracts(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $map = MaBox_Config_Manager::get_module_map();
        $registry = MaBox_Module_Loader::get_registry();
        $autoload = file_get_contents(self::$plugin_dir . '/includes/autoload.php');

        $this->assertArrayNotHasKey('ai_review', $schema);
        $this->assertArrayNotHasKey('ai_review', $map);
        $this->assertArrayNotHasKey('ai_review.main', $registry);
        $this->assertStringNotContainsString('MaBox_Ai_', $autoload);
        $this->assertDirectoryDoesNotExist(self::$plugin_dir . '/admin/partials/ai_review');
    }

    public function test_retired_credential_integrations_are_removed_from_backend_contracts(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $registry = MaBox_Module_Loader::get_registry();
        $tiers = MaBox_Module_Loader::get_tiers();
        $autoload = file_get_contents(self::$plugin_dir . '/includes/autoload.php');

        $this->assertArrayNotHasKey('baidu_push', $schema['domestic']);
        $this->assertArrayNotHasKey('anti_crawler', $schema['page']['function']);
        $this->assertArrayNotHasKey('domestic.baidu_push', $registry);
        $this->assertArrayNotHasKey('page.anti_crawler', $registry);
        foreach ($tiers as $modules) {
            $this->assertNotContains('domestic.baidu_push', $modules);
            $this->assertNotContains('page.anti_crawler', $modules);
        }
        $this->assertStringNotContainsString('MaBox_Domestic_Baidu_Push', $autoload);
        $this->assertStringNotContainsString('MaBox_Page_Anti_Crawler', $autoload);
        $this->assertDirectoryDoesNotExist(self::$plugin_dir . '/admin/partials/domestic/baidu_push');
        $this->assertDirectoryDoesNotExist(self::$plugin_dir . '/admin/partials/page/function/anti_crawler');
    }

    public function test_h5_php_file_deleted(): void {
        $file = self::$plugin_dir . '/admin/partials/h5.php';
        $this->assertFileDoesNotExist($file);
    }

    public function test_b2_directory_deleted(): void {
        $dir = self::$plugin_dir . '/admin/partials/function/b2';
        $this->assertDirectoryDoesNotExist($dir);
    }

    public function test_maintenance_deleted_templates_absent(): void {
        $maintenance_dir = self::$plugin_dir . '/admin/partials/page/function/maintenance/';
        $this->assertDirectoryDoesNotExist($maintenance_dir . 'purple');
        $this->assertDirectoryDoesNotExist($maintenance_dir . 'lighting');
        $this->assertDirectoryDoesNotExist($maintenance_dir . 'masking');
        $this->assertDirectoryDoesNotExist($maintenance_dir . 'rotate');
    }

    public function test_maintenance_kept_templates_present(): void {
        $maintenance_dir = self::$plugin_dir . '/admin/partials/page/function/maintenance/';
        $this->assertDirectoryExists($maintenance_dir . 'default');
        $this->assertFileExists($maintenance_dir . 'red.php');
    }

    public function test_schema_has_no_h5_branch(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $this->assertArrayNotHasKey('h5', $schema);
    }

    public function test_schema_has_no_b2_branch(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $this->assertIsArray($schema['function']);
        $this->assertArrayNotHasKey('b2', $schema['function']);
    }

    public function test_config_manager_has_no_h5_mapping(): void {
        $map = MaBox_Config_Manager::get_module_map();
        $this->assertArrayNotHasKey('h5', $map);
    }

    public function test_no_escape_no_global_the_title_filter(): void {
        $file = self::$plugin_dir . '/admin/partials/optimize/site/no_escape.php';
        $content = file_get_contents($file);
        $this->assertStringNotContainsString("add_filter('the_title'", $content);
        $this->assertStringContainsString("add_filter('document_title_parts'", $content);
    }

    public function test_census_single_no_b2_div_id(): void {
        $file = self::$plugin_dir . '/admin/partials/function/auxiliary/census-single.php';
        $content = file_get_contents($file);
        $this->assertStringNotContainsString('MaBox_b2_shop_count', $content);
    }

    public function test_count_frontend_source_and_dashboard_page_asset_contract_exist(): void {
        $this->assertFileExists(self::$plugin_dir . '/vite/count/src/main.tsx');
        $this->assertFileExists(self::$plugin_dir . '/vite/count/vite.config.ts');

        $loader = file_get_contents(self::$plugin_dir . '/admin/partials/function/auxiliary/census-single.php');
        // WordPress maps an index.php submenu to dashboard_page_{$menu_slug}, not index_page_{$menu_slug}.
        $this->assertStringContainsString("'dashboard_page_magick-census-single'", $loader);
        $this->assertStringNotContainsString("'index_page_magick-census-single'", $loader);
        $this->assertStringContainsString("'vite/count/dist/index.css'", $loader);
        $this->assertStringContainsString("'vite/count/dist/index.js'", $loader);
        $this->assertStringContainsString('filemtime($build_css_path)', $loader);
        $this->assertStringContainsString('filemtime($build_js_path)', $loader);
        $this->assertStringContainsString("MAGICK_MIXTURE_NAME . '_census_css'", $loader);
        $this->assertSame(2, substr_count($loader, "MAGICK_MIXTURE_NAME . '_census_js'"));
        $this->assertStringContainsString("wp_localize_script(MAGICK_MIXTURE_NAME . '_census_js', 'dataLocal'", $loader);
        $this->assertStringContainsString("'countData' => self::deliver_data()", $loader);
        $this->assertStringContainsString('id="mabox_census_count"', $loader);
    }

    public function test_retired_vite_public_project_is_absent(): void {
        $this->assertDirectoryDoesNotExist(self::$plugin_dir . '/vite/public');
    }

    public function test_current_frontend_docs_do_not_restore_vite_public(): void {
        $files = [
            'README.md',
            'docs/构建与发布指南.md',
            'docs-site/guide/development.md',
            'docs-site/guide/architecture.md',
        ];

        foreach ($files as $file) {
            $content = file_get_contents(self::$plugin_dir . '/' . $file);
            $this->assertStringNotContainsString('dev:public', $content, "$file must not document the retired dev target");
            $this->assertStringNotContainsString('build:public', $content, "$file must not document the retired build target");
        }
    }

    private static function removedP0Modules(): array {
        return [
            'page.click_effect', 'page.screen_hair', 'page.lantern',
            'page.pixel_chicken', 'page.completed_book', 'page.bottom_effect',
            'page.background_effect', 'template.main', 'template.static',
            'template.trends',
        ];
    }

    private static function removedP1Modules(): array {
        return [
            'page.ticket', 'page.diary', 'services.main', 'feedback.main',
            'function.wx_xcx_link', 'function.download_sql_table',
            'page.front_debug', 'page.article_rating', 'page.batch_replace',
        ];
    }

    private static function removedP2Modules(): array {
        return [
            'page.dynamic_title', 'page.go_top', 'page.color_tags',
            'page.top_ad', 'page.header_notice', 'page.link_source',
            'shortcode.main', 'shortcode.compose', 'shortcode.pendant',
        ];
    }

    private static function removedP3Modules(): array {
        return [
            'page.top_loading', 'page.add_scroll_bar', 'page.all_grey',
            'page.copy_pop_up', 'page.scrolling', 'page.font_switch',
            'page.comment_emoji', 'page.share', 'page.lang_jf',
            'login.custom_login_page',
        ];
    }

    private static function removedP4Modules(): array {
        return [
            'page.jump_middle_page', 'page.ban_open_weixing', 'page.ban_open_qq',
            'page.comment_modify_user_style', 'page.comment_baidu_moderation',
            'page.single_remove_link', 'page.ban_copy',
            'login.change_login_logo_link', 'login.remove_login_lang_select',
        ];
    }

    public function test_p0_modules_removed_from_registry(): void {
        $registry = MaBox_Module_Loader::get_registry();
        foreach (self::removedP0Modules() as $module_id) {
            $this->assertArrayNotHasKey($module_id, $registry, "$module_id should not be in registry");
        }
    }

    public function test_p1_modules_removed_from_registry(): void {
        $registry = MaBox_Module_Loader::get_registry();
        foreach (self::removedP1Modules() as $module_id) {
            $this->assertArrayNotHasKey($module_id, $registry, "$module_id should not be in registry");
        }
    }

    public function test_p2_modules_removed_from_registry(): void {
        $registry = MaBox_Module_Loader::get_registry();
        foreach (self::removedP2Modules() as $module_id) {
            $this->assertArrayNotHasKey($module_id, $registry, "$module_id should not be in registry");
        }
    }

    public function test_p0_modules_removed_from_tiers(): void {
        $tiers = MaBox_Module_Loader::get_tiers();
        foreach (self::removedP0Modules() as $module_id) {
            foreach ($tiers as $tier => $modules) {
                $this->assertNotContains($module_id, $modules, "$module_id should not be in tier '$tier'");
            }
        }
    }

    public function test_p1_modules_removed_from_tiers(): void {
        $tiers = MaBox_Module_Loader::get_tiers();
        foreach (self::removedP1Modules() as $module_id) {
            foreach ($tiers as $tier => $modules) {
                $this->assertNotContains($module_id, $modules, "$module_id should not be in tier '$tier'");
            }
        }
    }

    public function test_p2_modules_removed_from_tiers(): void {
        $tiers = MaBox_Module_Loader::get_tiers();
        foreach (self::removedP2Modules() as $module_id) {
            foreach ($tiers as $tier => $modules) {
                $this->assertNotContains($module_id, $modules, "$module_id should not be in tier '$tier'");
            }
        }
    }

    public function test_p3_modules_removed_from_registry(): void {
        $registry = MaBox_Module_Loader::get_registry();
        foreach (self::removedP3Modules() as $module_id) {
            $this->assertArrayNotHasKey($module_id, $registry, "$module_id should not be in registry");
        }
    }

    public function test_p3_modules_removed_from_tiers(): void {
        $tiers = MaBox_Module_Loader::get_tiers();
        foreach (self::removedP3Modules() as $module_id) {
            foreach ($tiers as $tier => $modules) {
                $this->assertNotContains($module_id, $modules, "$module_id should not be in tier '$tier'");
            }
        }
    }

    public function test_p4_modules_removed_from_registry(): void {
        $registry = MaBox_Module_Loader::get_registry();
        foreach (self::removedP4Modules() as $module_id) {
            $this->assertArrayNotHasKey($module_id, $registry, "$module_id should not be in registry");
        }
    }

    public function test_p4_modules_removed_from_tiers(): void {
        $tiers = MaBox_Module_Loader::get_tiers();
        foreach (self::removedP4Modules() as $module_id) {
            foreach ($tiers as $tier => $modules) {
                $this->assertNotContains($module_id, $modules, "$module_id should not be in tier '$tier'");
            }
        }
    }

    public function test_p3_module_files_deleted(): void {
        $partials = self::$plugin_dir . '/admin/partials/';
        $deleted_paths = [
            'page/exterior/top_loading',
            'page/exterior/copy_pop_up',
            'page/exterior/scrolling',
            'page/exterior/font_switch',
            'page/exterior/add_scroll_bar.php',
            'page/exterior/all_grey.php',
            'page/comment/comment_emoji.php',
            'page/comment/emoji',
            'page/function/share',
            'page/function/lang_jf',
            'login/beautify/custom_login_page.php',
            'login/beautify/style-login.css',
        ];
        foreach ($deleted_paths as $path) {
            $full = $partials . $path;
            $this->assertFileDoesNotExist($full, "$path should be deleted");
        }
    }

    public function test_p4_module_files_deleted(): void {
        $partials = self::$plugin_dir . '/admin/partials/';
        $deleted_paths = [
            'page/function/jump_middle_page.php',
            'page/function/go',
            'page/function/single_remove_link.php',
            'page/jurisdiction/ban_open_weixing.php',
            'page/jurisdiction/ban_open_qq.php',
            'page/jurisdiction/ban_copy.php',
            'page/jurisdiction/WxqqJump',
            'page/comment/comment_modify_user_style.php',
            'page/comment/baidu_moderation',
            'login/beautify/change_login_logo_link.php',
            'login/beautify/remove_login_lang_select.php',
        ];
        foreach ($deleted_paths as $path) {
            $full = $partials . $path;
            $this->assertFileDoesNotExist($full, "$path should be deleted");
        }
    }

    public function test_p0_module_files_deleted(): void {
        $partials = self::$plugin_dir . '/admin/partials/';
        $deleted_paths = [
            'page/exterior/screen_hair',
            'page/exterior/lantern',
            'page/exterior/pixel_chicken',
            'page/exterior/click_effect',
            'page/exterior/bottom_effect',
            'page/exterior/background_effect',
            'page/exterior/completed_book.php',
            'template',
        ];
        foreach ($deleted_paths as $path) {
            $full = $partials . $path;
            $this->assertFileDoesNotExist($full, "$path should be deleted");
        }
    }

    public function test_p1_module_files_deleted(): void {
        $partials = self::$plugin_dir . '/admin/partials/';
        $deleted_paths = [
            'page/ticket',
            'page/diary',
            'services',
            'feedback',
            'function/wx_xcx_link',
            'function/download-sql-table.php',
            'page/jurisdiction/front_debug.php',
            'page/function/article_rating.php',
            'page/function/article_rating.js',
            'page/function/batch_replace.php',
        ];
        foreach ($deleted_paths as $path) {
            $full = $partials . $path;
            $this->assertFileDoesNotExist($full, "$path should be deleted");
        }
    }

    public function test_p2_module_files_deleted(): void {
        $partials = self::$plugin_dir . '/admin/partials/';
        $deleted_paths = [
            'page/exterior/go_top',
            'page/exterior/dynamic_title.php',
            'page/function/top_ad.php',
            'page/function/header_notice.php',
            'page/function/link_source.php',
            'page/function/color_tags.php',
            'shortcode',
        ];
        foreach ($deleted_paths as $path) {
            $full = $partials . $path;
            $this->assertFileDoesNotExist($full, "$path should be deleted");
        }
    }

    public function test_schema_has_no_removed_branches(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $this->assertArrayNotHasKey('template', $schema);
        $this->assertArrayNotHasKey('services', $schema);
        $this->assertArrayNotHasKey('feedback', $schema);
        $this->assertArrayNotHasKey('wx_xcx', $schema['function']);
    }

    public function test_schema_page_feature_has_no_removed_fields(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $feature = $schema['page']['feature'];
        $removed_feature_fields = ['particle', 'screen_hair', 'lantern', 'lantern_left', 'lantern_right', 'pixel_chicken', 'past_books', 'bottom_effect', 'background_effect'];
        foreach ($removed_feature_fields as $field) {
            $this->assertArrayNotHasKey($field, $feature, "page.feature.$field should not exist in schema");
        }
    }

    public function test_schema_page_feature_has_no_p3_fields(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $feature = $schema['page']['feature'];
        $removed = ['top_loading', 'scrol', 'site_grey', 'copy_pop_up', 'page_scrolling', 'font_switch', 'fonts', 'font_position'];
        foreach ($removed as $field) {
            $this->assertArrayNotHasKey($field, $feature, "page.feature.$field should not exist in schema");
        }
    }

    public function test_schema_page_function_has_no_p3_fields(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $func = $schema['page']['function'];
        $removed = ['share', 'share_position', 'share_top', 'share_margins', 'share_text', 'share_email_email', 'share_email_title', 'share_email_content', 'share_img_home', 'share_img_page', 'share_img_about', 'switch_lang_jf'];
        foreach ($removed as $field) {
            $this->assertArrayNotHasKey($field, $func, "page.function.$field should not exist in schema");
        }
    }

    public function test_schema_page_comment_has_no_p3_fields(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $this->assertArrayNotHasKey('comment_emote', $schema['page']['comment'], 'page.comment.comment_emote should not exist in schema');
    }

    public function test_legacy_login_verification_removed_from_backend_contracts(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $map = MaBox_Config_Manager::get_module_map();
        $registry = MaBox_Module_Loader::get_registry();
        $tiers = MaBox_Module_Loader::get_tiers();
        $autoload = file_get_contents(self::$plugin_dir . '/includes/autoload.php');
        $plugin = file_get_contents(self::$plugin_dir . '/magick-tool-box.php');
        $uninstall = file_get_contents(self::$plugin_dir . '/uninstall.php');
        $phpstan = file_get_contents(self::$plugin_dir . '/phpstan.neon');

        $this->assertCount(56, $registry, 'The registry should contain 56 modules after removing the update blocker');
        $this->assertArrayNotHasKey('login.login_verify', $registry);
        foreach ($tiers as $modules) {
            $this->assertNotContains('login.login_verify', $modules);
        }
        $this->assertStringNotContainsString('MaBox_Login_Verify', $autoload);
        $this->assertFileDoesNotExist(self::$plugin_dir . '/admin/partials/login/security/login_verify.php');

        $this->assertArrayNotHasKey('login', $schema);
        $this->assertArrayNotHasKey('login', $map);
        $this->assertArrayNotHasKey('login', MaBox_Config_Schema::get_defaults());
        $this->assertFalse(defined('MAGICK_MIXTURE_OPTION_LOGIN'));

        $legacy_browser_settings = MaBox_Config_Schema::get_defaults();
        $legacy_browser_settings['login'] = array('security' => array('login_code' => 'math'));
        $legacy_validation = MaBox_Config_Schema::validate_browser_settings($legacy_browser_settings);
        $this->assertFalse($legacy_validation['valid']);
        $this->assertContains('settings.login 不是已知字段', $legacy_validation['errors']);

        $this->assertStringNotContainsString('MAGICK_MIXTURE_OPTION_LOGIN', $plugin);
        $this->assertStringNotContainsString('Magick_ToolBox_Option_Login', $uninstall);
        $this->assertStringNotContainsString('login/security/login_verify.php', $phpstan);
    }

    public function test_schema_page_function_has_no_removed_fields(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $func = $schema['page']['function'];
        $this->assertArrayNotHasKey('article_rating', $func);
        $this->assertArrayNotHasKey('batch_replace', $func);
        $this->assertArrayNotHasKey('batch_replace_pairs', $func);
        $this->assertArrayNotHasKey('ticket', $func);
        $this->assertArrayNotHasKey('diary', $func);
        $this->assertArrayNotHasKey('go_middle', $func);
        $this->assertArrayNotHasKey('remove_single_link', $func);
    }

    public function test_schema_page_jurisdiction_has_no_removed_fields(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $this->assertArrayNotHasKey('front_debug', $schema['page']['jurisdiction']);
        $this->assertArrayNotHasKey('ban_open_weixing', $schema['page']['jurisdiction']);
        $this->assertArrayNotHasKey('ban_open_qq', $schema['page']['jurisdiction']);
        $this->assertArrayNotHasKey('ban_copy', $schema['page']['jurisdiction']);
    }

    public function test_schema_page_comment_has_no_p4_fields(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $comment = $schema['page']['comment'];
        $removed = [
            'modify_comment_user',
            'baidu_moderation',
            'baidu_moderation_api_key',
            'baidu_moderation_secret_key',
            'baidu_moderation_action',
        ];
        foreach ($removed as $field) {
            $this->assertArrayNotHasKey($field, $comment, "page.comment.$field should not exist in schema");
        }
    }

    public function test_config_manager_has_no_removed_mappings(): void {
        $map = MaBox_Config_Manager::get_module_map();
        $this->assertArrayNotHasKey('template', $map);
        $this->assertArrayNotHasKey('services', $map);
        $this->assertArrayNotHasKey('feedback', $map);
    }

    public function test_readme_has_no_removed_feature_references(): void {
        $readme = file_get_contents(self::$plugin_dir . '/README.md');
        $removed = ['工单系统', '用户反馈', '增值服务', '点击特效', '背景特效'];
        foreach ($removed as $term) {
            $this->assertStringNotContainsString($term, $readme, "README.md should not reference removed feature '$term'");
        }
    }

    public function test_readme_txt_has_no_removed_feature_references(): void {
        $readme = file_get_contents(self::$plugin_dir . '/readme.txt');
        $removed = ['增值服务', '用户反馈'];
        foreach ($removed as $term) {
            $this->assertStringNotContainsString($term, $readme, "readme.txt should not reference removed feature '$term'");
        }
    }

    public function test_feature_list_has_no_removed_feature_references(): void {
        $file = self::$plugin_dir . '/功能清单.md';
        if (!file_exists($file)) {
            $this->markTestSkipped('功能清单.md not found');
        }
        $content = file_get_contents($file);
        $removed = ['点击特效', '背景特效', '页面模板', '小程序跳转'];
        foreach ($removed as $term) {
            $this->assertStringNotContainsString($term, $content, "功能清单.md should not reference removed feature '$term'");
        }
    }

    public function test_docs_site_config_has_no_page_templates_nav(): void {
        $config = file_get_contents(self::$plugin_dir . '/docs-site/.vitepress/config.ts');
        $this->assertStringNotContainsString('page-templates', $config);
        $this->assertStringNotContainsString('页面模板', $config);
    }

    public function test_docs_site_overview_has_no_page_templates(): void {
        $overview = file_get_contents(self::$plugin_dir . '/docs-site/features/overview.md');
        $this->assertStringNotContainsString('页面模板', $overview);
        $this->assertStringNotContainsString('page-templates', $overview);
    }

    public function test_docs_site_architecture_has_no_removed_dirs(): void {
        $arch = file_get_contents(self::$plugin_dir . '/docs-site/guide/architecture.md');
        $this->assertStringNotContainsString('feedback/', $arch);
        $this->assertStringNotContainsString('services/', $arch);
    }

    public function test_docs_site_config_recovery_has_no_removed_modules(): void {
        $recovery = file_get_contents(self::$plugin_dir . '/docs-site/guide/config-recovery.md');
        $this->assertStringNotContainsString('增值服务', $recovery);
        $this->assertStringNotContainsString('用户反馈', $recovery);
        $this->assertStringNotContainsString('services', $recovery);
        $this->assertStringNotContainsString('feedback', $recovery);
    }

    public function test_docs_site_page_templates_dir_deleted(): void {
        $dir = self::$plugin_dir . '/docs-site/features/page-templates';
        $this->assertDirectoryDoesNotExist($dir);
    }

    public function test_frontend_api_has_no_feedback_api(): void {
        $api = file_get_contents(self::$plugin_dir . '/vite/admin/src/api/index.ts');
        $this->assertStringNotContainsString('feedbackApi', $api);
        $this->assertStringNotContainsString('/feedback/', $api);
    }

    public function test_frontend_css_has_no_template_row_styles(): void {
        $css = file_get_contents(self::$plugin_dir . '/vite/admin/src/App.css');
        $this->assertStringNotContainsString('mabox-template-row', $css);
    }

    public function test_frontend_assets_template_dir_deleted(): void {
        $dir = self::$plugin_dir . '/vite/admin/src/assets/template';
        $this->assertDirectoryDoesNotExist($dir);
    }

    public function test_schema_has_no_shortcode_branch(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $this->assertArrayNotHasKey('shortcode', $schema);
    }

    public function test_schema_page_feature_has_no_p2_fields(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $feature = $schema['page']['feature'];
        $removed = ['title', 'go_top', 'page_back_top_cat_right'];
        foreach ($removed as $field) {
            $this->assertArrayNotHasKey($field, $feature, "page.feature.$field should not exist in schema");
        }
    }

    public function test_schema_page_function_has_no_p2_fields(): void {
        $schema = MaBox_Config_Schema::get_schema();
        $func = $schema['page']['function'];
        $removed = ['color_tag', 'top_ad', 'top_ad_content', 'top_ad_position', 'header_notice', 'header_notice_text', 'header_notice_color', 'header_notice_link', 'header_notice_dismissible', 'link_source', 'source_key'];
        foreach ($removed as $field) {
            $this->assertArrayNotHasKey($field, $func, "page.function.$field should not exist in schema");
        }
    }

    public function test_config_manager_has_no_shortcode_mapping(): void {
        $map = MaBox_Config_Manager::get_module_map();
        $this->assertArrayNotHasKey('shortcode', $map);
    }

    public function test_shortcode_directory_deleted(): void {
        $dir = self::$plugin_dir . '/admin/partials/shortcode';
        $this->assertDirectoryDoesNotExist($dir);
    }

    public function test_docs_site_has_no_p2_feature_pages(): void {
        $docs = self::$plugin_dir . '/docs-site/features/';
        $deleted = [
            'page-appearance/dynamic-title.md',
            'page-appearance/back-to-top.md',
            'page-function/top-ad-slot.md',
            'page-function/header-notice.md',
            'page-function/link-source.md',
            'page-function/colorful-tag-cloud.md',
        ];
        foreach ($deleted as $path) {
            $this->assertFileDoesNotExist($docs . $path, "$path should be deleted");
        }
        $this->assertDirectoryDoesNotExist($docs . 'shortcodes');
    }
}
