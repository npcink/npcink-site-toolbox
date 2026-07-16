<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

class MetadataAggregationTest extends TestCase {

    public function test_metadata_class_exists(): void {
        $this->assertTrue(class_exists('MaBox_Module_Metadata'));
    }

    public function test_registry_is_the_single_source_with_stable_module_order(): void {
        MaBox_Module_Metadata::reset_cache();
        $registry = require dirname(__DIR__, 2) . '/admin/modules/registry.php';
        $metadata = MaBox_Module_Metadata::get_registry();

        $this->assertCount(56, $registry);
        $this->assertSame(self::expected_module_ids(), array_keys($registry));
        $this->assertSame($registry, $metadata);
    }

    public function test_four_sidecar_metadata_entries_are_folded_exactly_into_registry(): void {
        MaBox_Module_Metadata::reset_cache();
        $registry = MaBox_Module_Metadata::get_registry();

        foreach (self::expected_folded_metadata() as $module_id => $expected) {
            $this->assertArrayHasKey($module_id, $registry);
            $this->assertSame($expected, $registry[$module_id], "Folded metadata drifted for '{$module_id}'");
        }
    }

    public function test_no_sidecar_manifest_files_remain(): void {
        $partials = dirname(__DIR__, 2) . '/admin/partials';
        $manifests = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($partials, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file_info) {
            if ($file_info->isFile() && substr($file_info->getFilename(), -9) === '.meta.php') {
                $manifests[] = $file_info->getPathname();
            }
        }

        sort($manifests);
        $this->assertSame(array(), $manifests, 'Registry must be the only module metadata source');
    }

    public function test_metadata_loader_has_no_scan_or_merge_compatibility_path(): void {
        $source = file_get_contents(dirname(__DIR__, 2) . '/admin/modules/metadata.php');

        $this->assertStringContainsString("require plugin_dir_path(__FILE__) . 'registry.php'", $source);
        $this->assertStringNotContainsString('RecursiveDirectoryIterator', $source);
        $this->assertStringNotContainsString('scan_meta_files', $source);
        $this->assertStringNotContainsString('build_merged_registry', $source);
        $this->assertStringNotContainsString('array_merge', $source);
        $this->assertStringNotContainsString('.meta.php', $source);
    }

    public function test_ui_metadata_excludes_internal_keys_and_preserves_public_fields(): void {
        MaBox_Module_Metadata::reset_cache();
        $ui = MaBox_Module_Metadata::get_ui_metadata();

        $this->assertIsArray($ui);
        $this->assertCount(56, $ui);

        foreach ($ui as $module_id => $entry) {
            $this->assertArrayNotHasKey('_option_key', $entry, "UI metadata should not contain _option_key for '{$module_id}'");
            $this->assertArrayNotHasKey('class', $entry, "UI metadata should not contain class for '{$module_id}'");
            $this->assertArrayNotHasKey('file', $entry, "UI metadata should not contain file path for '{$module_id}'");
            $this->assertArrayHasKey('id', $entry);
            $this->assertArrayHasKey('category', $entry);
            $this->assertArrayHasKey('scope', $entry);
            $this->assertArrayHasKey('label', $entry);
            $this->assertArrayHasKey('risk', $entry);
        }

        $this->assertSame('隐藏顶部工具条', $ui['optimize.hide_top_toolbar']['label']);
        $this->assertSame('low', $ui['optimize.cdn_replace']['risk']['level']);
        $this->assertSame('none', $ui['domestic.login_security']['risk']['level']);
        $this->assertTrue($ui['optimize.widgets']['always_load']);
    }

    public function test_get_module_keeps_public_behavior(): void {
        MaBox_Module_Metadata::reset_cache();

        $this->assertNull(MaBox_Module_Metadata::get_module('nonexistent.module'));
        $this->assertSame(
            self::expected_folded_metadata()['optimize.hide_top_toolbar'],
            MaBox_Module_Metadata::get_module('optimize.hide_top_toolbar')
        );
    }

    public function test_required_keys_are_present_in_all_modules(): void {
        MaBox_Module_Metadata::reset_cache();
        $registry = MaBox_Module_Metadata::get_registry();
        $required = array('class', 'file', 'option_key', 'category', 'scope');

        foreach ($registry as $module_id => $meta) {
            foreach ($required as $key) {
                $this->assertArrayHasKey($key, $meta, "Module '{$module_id}' should have required key '{$key}'");
            }
        }
    }

    public function test_loader_registry_and_login_security_activation_contract_are_unchanged(): void {
        MaBox_Module_Metadata::reset_cache();
        $registry = require dirname(__DIR__, 2) . '/admin/modules/registry.php';

        $this->assertSame($registry, MaBox_Module_Metadata::get_registry());
        $this->assertSame($registry, MaBox_Module_Loader::get_registry());
        $this->assertSame(array(
            'domestic.login_security.attempt_limit_enabled',
            'domestic.login_security.anonymous_author_guard_enabled',
        ), $registry['domestic.login_security']['activation_paths']);

        $config = array(
            'domestic' => array(
                'login_security' => array(
                    'attempt_limit_enabled' => false,
                    'anonymous_author_guard_enabled' => true,
                ),
            ),
        );
        $this->assertContains('domestic.login_security', MaBox_Module_Loader::get_active_modules($config));

        $config['domestic']['login_security']['anonymous_author_guard_enabled'] = false;
        $this->assertNotContains('domestic.login_security', MaBox_Module_Loader::get_active_modules($config));
    }

    private static function expected_module_ids(): array {
        return array(
            'optimize.hide_top_toolbar',
            'optimize.no_escape',
            'optimize.remove_wp_version',
            'optimize.category_link_simplify',
            'optimize.search_link_simplify',
            'optimize.remove_sitemap_users',
            'optimize.user_list_show_nickname',
            'optimize.cdn_replace',
            'optimize.hide_email_ip',
            'optimize.widgets',
            'optimize.image_add_tag',
            'optimize.ban_auto_size',
            'optimize.svg_support',
            'optimize.image_rename',
            'optimize.admin_single_add_user_screen',
            'optimize.admin_add_time_screen',
            'optimize.admin_single_show_id',
            'optimize.admin_thumbnail_switcher',
            'page.reading_progress',
            'page.comment_interval',
            'page.limit_word_count',
            'page.ban_pure_english',
            'page.only_comment_once',
            'page.comment_sensitive_words',
            'page.first_picture',
            'page.single_keyword_add_link',
            'page.add_article_update_time',
            'page.unlisted_vague_img',
            'page.maintenance_tips',
            'page.default_thumbnail',
            'page.search_limit',
            'page.login_search',
            'page.hide_category',
            'page.hide_tag',
            'page.hide_page',
            'seo.seo_home',
            'seo.seo_single',
            'seo.seo_category_add_meat',
            'seo.seo_category',
            'seo.seo_tag',
            'auxiliary.census_single',
            'auxiliary.ban_malice_search',
            'auxiliary.baidu_tonji',
            'auxiliary.google_tonji',
            'auxiliary.biying_tonji',
            'function.config',
            'page.interface_category_data',
            'domestic.compliance',
            'domestic.wechat',
            'domestic.comment_security',
            'domestic.login_security',
            'performance.oss',
            'performance.seo_checker',
            'performance.media_health',
            'performance.search_enhance',
            'performance.db_clean',
        );
    }

    private static function expected_folded_metadata(): array {
        return array(
            'optimize.hide_top_toolbar' => array(
                'class'       => 'MaBox_Hide_Top_Toolbar',
                'file'        => 'optimize/site/hide_top_toolbar.php',
                'option_key'  => 'optimize.site.hide_top_toolbar',
                'category'    => 'optimize',
                'scope'       => 'both',
                'risk_tags'   => array('推荐', '仅后台'),
                'label'       => '隐藏顶部工具条',
                'group'       => '站点',
                'feature_id'  => 'optimize-site-hide_top_toolbar',
                'risk'        => array('level' => 'none'),
                'depends_on'  => array(),
                'preset_tags' => array('pure', 'blog'),
            ),
            'optimize.no_escape' => array(
                'class'       => 'MaBox_No_Escape',
                'file'        => 'optimize/site/no_escape.php',
                'option_key'  => 'optimize.site.no_escape',
                'category'    => 'optimize',
                'scope'       => 'frontend',
                'risk_tags'   => array('推荐'),
                'label'       => '禁止 Title 转义',
                'group'       => '站点',
                'feature_id'  => 'optimize-site-no_escape',
                'risk'        => array('level' => 'none'),
                'depends_on'  => array(),
                'preset_tags' => array('pure', 'blog'),
            ),
            'optimize.cdn_replace' => array(
                'class'       => 'MaBox_CDN_Replace',
                'file'        => 'optimize/site/cdn_replace.php',
                'option_key'  => 'optimize.site.cdn_replace',
                'category'    => 'optimize',
                'scope'       => 'frontend',
                'config_path' => 'optimize.site',
                'risk_tags'   => array('性能'),
                'label'       => '国内 CDN 替换',
                'group'       => '站点',
                'feature_id'  => 'optimize-site-cdn_replace',
                'risk'        => array('level' => 'low'),
                'depends_on'  => array(),
                'preset_tags' => array('performance'),
            ),
            'domestic.login_security' => array(
                'class'            => 'MaBox_Domestic_Login_Security',
                'file'             => 'domestic/login_security/index.php',
                'option_key'       => 'domestic.login_security.attempt_limit_enabled',
                'activation_paths' => array(
                    'domestic.login_security.attempt_limit_enabled',
                    'domestic.login_security.anonymous_author_guard_enabled',
                ),
                'category'         => 'domestic',
                'scope'            => 'both',
                'config_path'      => 'domestic.login_security',
                'risk_tags'        => array('推荐', '安全'),
                'label'            => '登录安全',
                'group'            => '登录安全',
                'feature_id'       => 'domestic-login_security',
                'depends_on'       => array(),
                'preset_tags'      => array('security'),
            ),
        );
    }
}
