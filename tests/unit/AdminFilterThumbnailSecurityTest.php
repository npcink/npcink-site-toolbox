<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return stripslashes_deep($value);
    }
}

final class AdminFilterThumbnailSecurityTest extends TestCase
{
    public function test_date_filters_are_unslashed_sanitized_and_documented_as_read_only(): void
    {
        $source = $this->source('add_time_screen.php');

        $this->assertStringContainsString(
            "\$from_value = wp_unslash(\$_GET['mishaDateFrom'] ?? '')",
            $source
        );
        $this->assertStringContainsString(
            "\$to_value = wp_unslash(\$_GET['mishaDateTo'] ?? '')",
            $source
        );
        $this->assertStringContainsString(
            "is_string(\$from_value) ? sanitize_text_field(\$from_value) : ''",
            $source
        );
        $this->assertStringContainsString(
            "is_string(\$to_value) ? sanitize_text_field(\$to_value) : ''",
            $source
        );
        $this->assertSame(
            2,
            substr_count($source, 'WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized')
        );
        $this->assertSame(1, substr_count($source, "\$_GET['mishaDateFrom']"));
        $this->assertSame(1, substr_count($source, "\$_GET['mishaDateTo']"));
    }

    public function test_date_filter_arrays_fail_closed_without_reaching_text_sanitization(): void
    {
        $original_get = $_GET;
        $_GET = array(
            'mishaDateFrom' => array('2026-07-01'),
            'mishaDateTo' => array('2026-07-31'),
        );

        try {
            $method = new ReflectionMethod(Npcink_Toolbox_Admin_Add_Time_Screen::class, 'requested_dates');
            $method->setAccessible(true);

            $this->assertSame(
                array('from' => '', 'to' => ''),
                $method->invoke(null)
            );
        } finally {
            $_GET = $original_get;
        }
    }

    public function test_author_filter_reads_the_submitted_author_key_as_an_integer(): void
    {
        $source = $this->source('single_add_user_screen.php');

        $this->assertStringContainsString(
            "\$author   = wp_unslash(\$_GET['author'] ?? '')",
            $source
        );
        $this->assertStringContainsString(
            'is_string($author) ? absint($author) : 0',
            $source
        );
        $this->assertStringNotContainsString("\$_GET['user']", $source);
        $this->assertStringContainsString("'name'            => 'author',", $source);
    }

    public function test_thumbnail_mutations_require_sanitized_input_and_object_capability(): void
    {
        $source = $this->source('thumbnail_switcher/easy-thumbnail-switcher.php');

        $this->assertSame(
            2,
            substr_count($source, "\$nonce_value = wp_unslash( \$_POST['nonce'] )")
        );
        $this->assertSame(
            2,
            substr_count($source, "! is_string( \$nonce_value )")
        );
        $this->assertSame(
            2,
            substr_count($source, "\$post_id = wp_unslash( \$_POST['post_id'] )")
        );
        $this->assertStringContainsString("\$thumbnail_id = wp_unslash( \$_POST['thumb_id'] )", $source);
        $this->assertStringContainsString("! is_string( \$post_id )", $source);
        $this->assertStringContainsString("! is_string( \$thumbnail_id )", $source);
        $this->assertSame(2, substr_count($source, 'absint( $post_id )'));
        $this->assertSame(1, substr_count($source, 'absint( $thumbnail_id )'));
        $this->assertSame(2, substr_count($source, "current_user_can( 'edit_post', \$id )"));

        $this->assertGuardPrecedesMutation($source, "public function update()", 'set_post_thumbnail( $id, $thumb_id )');
        $this->assertGuardPrecedesMutation($source, "public function remove()", 'delete_post_thumbnail( $id )');
    }

    public function test_thumbnail_helpers_use_project_prefixes_without_promotional_shortlinks(): void
    {
        $switcher = $this->source('thumbnail_switcher/easy-thumbnail-switcher.php');
        $autoload = file_get_contents(dirname(__DIR__, 2) . '/includes/autoload.php');
        $this->assertIsString($autoload);

        $this->assertStringContainsString('class Npcink_Toolbox_Easy_Thumbnail_Switcher', $switcher);
        $this->assertStringContainsString('new Npcink_Toolbox_Easy_Thumbnail_Switcher();', $switcher);
        $this->assertFileDoesNotExist(
            dirname(__DIR__, 2) . '/admin/partials/optimize/admin/thumbnail_switcher/class-ts-admin-notice.php'
        );
        $this->assertStringNotContainsString('Npcink_Toolbox_Admin_Notice', $autoload);
        $this->assertStringNotContainsString('Npcink_Toolbox_Easy_Thumbnail_Switcher', $autoload);

        $combined = $switcher . $autoload;
        $this->assertStringNotContainsString('TS_Easy_Thumbnail_Switcher', $combined);
        $this->assertStringNotContainsString('TS_Admin_Notice', $combined);
        $this->assertStringNotContainsString('$ts_admin_notices', $combined);
        $this->assertStringNotContainsString('$ts_admin_notices_js', $combined);
        $this->assertStringNotContainsString('goo.gl', $switcher);
        $this->assertStringNotContainsString('super-blog-pack', $switcher);
        $this->assertStringNotContainsString('$href', $switcher);
    }

    private function assertGuardPrecedesMutation(string $source, string $method, string $mutation): void
    {
        $method_position = strpos($source, $method);
        $this->assertNotFalse($method_position);

        $guard_position = strpos($source, "current_user_can( 'edit_post', \$id )", $method_position);
        $mutation_position = strpos($source, $mutation, $method_position);

        $this->assertNotFalse($guard_position);
        $this->assertNotFalse($mutation_position);
        $this->assertLessThan($mutation_position, $guard_position);
    }

    private function source(string $relative_path): string
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/admin/partials/optimize/admin/' . $relative_path
        );
        $this->assertIsString($source);

        return $source;
    }
}
