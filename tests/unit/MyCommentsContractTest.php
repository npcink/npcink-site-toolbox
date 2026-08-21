<?php

defined('ABSPATH') || exit;

use PHPUnit\Framework\TestCase;

final class MyCommentsContractTest extends TestCase
{
    private function routes()
    {
        global $_test_option_store;
        $_test_option_store[NPCINK_SITE_TOOLBOX_OPTION_PAGE] = array(
            'comment' => array(
                'self_service_enabled' => true,
                'self_service_admin_page_enabled' => true,
            ),
        );
        Npcink_Toolbox_Rest_Route_Registry::clear();
        Npcink_Toolbox_My_Comments::register_routes();
        return Npcink_Toolbox_Rest_Route_Registry::get_registered();
    }

    public function test_feature_is_disabled_by_default_and_registers_no_routes(): void
    {
        global $_test_option_store;
        unset($_test_option_store[NPCINK_SITE_TOOLBOX_OPTION_PAGE]);
        Npcink_Toolbox_Rest_Route_Registry::clear();
        Npcink_Toolbox_My_Comments::register_routes();

        $this->assertFalse(Npcink_Toolbox_My_Comments::is_enabled());
        $this->assertSame(array(), Npcink_Toolbox_Rest_Route_Registry::get_registered());
    }

    public function test_feature_accepts_enabled_module_config(): void
    {
        $this->assertTrue(Npcink_Toolbox_My_Comments::is_enabled(array('self_service_enabled' => true)));
        $this->assertFalse(Npcink_Toolbox_My_Comments::is_enabled(array('self_service_enabled' => false)));
    }

    public function test_admin_page_can_be_disabled_without_disabling_rest(): void
    {
        $config = array(
            'self_service_enabled' => true,
            'self_service_admin_page_enabled' => false,
        );

        $this->assertTrue(Npcink_Toolbox_My_Comments::is_enabled($config));
        $this->assertFalse(Npcink_Toolbox_My_Comments::is_admin_page_enabled($config));
    }

    public function test_routes_are_registered_with_permissions(): void
    {
        $routes = $this->routes();
        $this->assertSame(array(
            '/me/comments',
            '/me/comments/(?P<id>\\d+)',
            '/me/comments/batch-delete',
        ), array_column($routes, 'path'));
        $this->assertEmpty(Npcink_Toolbox_Rest_Route_Registry::validate_all_have_permission());
    }

    public function test_batch_delete_is_limited_to_twenty_unique_positive_ids(): void
    {
        $this->assertTrue(Npcink_Toolbox_My_Comments::validate_comment_ids(range(1, 20)));
        $this->assertFalse(Npcink_Toolbox_My_Comments::validate_comment_ids(range(1, 21)));
        $this->assertFalse(Npcink_Toolbox_My_Comments::validate_comment_ids(array(0, 1)));
        $this->assertSame(array(2, 3), Npcink_Toolbox_My_Comments::sanitize_comment_ids(array('2', 2, 3, 0)));
    }

    public function test_comment_content_is_trimmed_and_sanitized(): void
    {
        $this->assertSame('允许<strong>强调</strong>坏', Npcink_Toolbox_My_Comments::sanitize_comment_content(' 允许<strong>强调</strong><script>坏</script> '));
    }

    public function test_source_enforces_ownership_trash_and_reply_guards(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/includes/class-npcink-toolbox-my-comments.php');
        $this->assertStringContainsString('(int) $comment->user_id !== get_current_user_id()', $source);
        $this->assertStringContainsString('wp_trash_comment($comment_id)', $source);
        $this->assertStringContainsString("'comment_approved' => 0", $source);
        $this->assertStringContainsString('has_effective_replies', $source);
        $this->assertStringContainsString("'parent__in' => \$parent_ids", $source);
        $this->assertStringNotContainsString('wp_delete_comment(', $source);
    }

    public function test_admin_page_uses_dedicated_assets_and_application_password_guidance(): void
    {
        $source = file_get_contents(dirname(__DIR__, 2) . '/includes/class-npcink-toolbox-my-comments.php');
        $this->assertStringContainsString("profile.php#application-passwords-section", $source);
        $this->assertStringContainsString("admin/js/my-comments.js", $source);
        $this->assertStringContainsString("admin/css/my-comments.css", $source);
    }

    public function test_settings_link_uses_local_admin_help_page(): void
    {
        $admin_source = file_get_contents(dirname(__DIR__, 2) . '/admin/class-npcink-toolbox-admin.php');
        $frontend_source = file_get_contents(dirname(__DIR__, 2) . '/vite/admin/src/components/page/comment.tsx');
        $help_source = file_get_contents(dirname(__DIR__, 2) . '/admin/partials/page/comment/rest_help.php');

        $this->assertStringContainsString('npcink-site-toolbox-comment-rest-help', $admin_source);
        $this->assertStringContainsString("'manage_options'", $admin_source);
        $this->assertStringContainsString('commentRestHelpUrl', $admin_source);
        $this->assertStringContainsString('commentRestHelpUrl', $frontend_source);
        $this->assertStringNotContainsString('docs.npc.ink/features/page-comment/user-comment-rest-api', $frontend_source);
        $this->assertStringContainsString('WordPress 应用程序密码', $help_source);
        $this->assertStringContainsString('batch-delete', $help_source);
    }
}
