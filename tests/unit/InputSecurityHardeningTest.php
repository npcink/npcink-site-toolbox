<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private $errors = array();
        private $error_data = array();

        public function __construct($code = '', $message = '', $data = array())
        {
            if ('' !== $code) {
                $this->errors[$code] = array($message);
                $this->error_data[$code] = $data;
            }
        }

        public function get_error_codes()
        {
            return array_keys($this->errors);
        }

        public function get_error_code()
        {
            $codes = $this->get_error_codes();
            return isset($codes[0]) ? $codes[0] : '';
        }

        public function get_error_message($code = '')
        {
            $code = '' !== $code ? $code : $this->get_error_code();
            return isset($this->errors[$code][0]) ? $this->errors[$code][0] : '';
        }

        public function get_error_data($code = '')
        {
            $code = '' !== $code ? $code : $this->get_error_code();
            return isset($this->error_data[$code]) ? $this->error_data[$code] : null;
        }
    }
}

if (!function_exists('wp_unslash')) {
    function wp_unslash($value)
    {
        return stripslashes_deep($value);
    }
}

if (!function_exists('sanitize_key')) {
    function sanitize_key($key)
    {
        return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key));
    }
}

if (!function_exists('absint')) {
    function absint($value)
    {
        return abs((int) $value);
    }
}

final class InputSecurityHardeningTest extends TestCase
{
    private $server;
    private $post;
    private $get;

    protected function setUp(): void
    {
        parent::setUp();
        $this->server = $_SERVER;
        $this->post = $_POST;
        $this->get = $_GET;
        $GLOBALS['_test_option_store'] = array();
        $GLOBALS['_test_transient_store'] = array();
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->server;
        $_POST = $this->post;
        $_GET = $this->get;
        parent::tearDown();
    }

    public function test_real_ip_ignores_untrusted_forwarded_headers_and_rejects_invalid_values(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        $_SERVER['HTTP_X_FORWARDED_FOR'] = '198.51.100.20';
        $_SERVER['HTTP_CLIENT_IP'] = '192.0.2.30';

        $this->assertSame('203.0.113.10', Npcink_Toolbox_Helpers::get_real_ip());

        $_SERVER['REMOTE_ADDR'] = 'not-an-ip';
        $this->assertSame('', Npcink_Toolbox_Helpers::get_real_ip());

        $_SERVER['REMOTE_ADDR'] = array('203.0.113.10');
        $this->assertSame('', Npcink_Toolbox_Helpers::get_real_ip());
    }

    public function test_category_seo_values_require_core_nonce_and_are_sanitized(): void
    {
        $_POST = array(
            'action'    => 'add-tag',
            'cat-title' => '<b>Unverified</b>',
            'cat-words' => 'one,two',
        );

        Npcink_Toolbox_Seo_Category_Add_Meat::taxonomy_metadate(7);
        $this->assertArrayNotHasKey('npcink_site_toolbox_category_title_7', $GLOBALS['_test_option_store']);
        $this->assertArrayNotHasKey('npcink_site_toolbox_category_keywords_7', $GLOBALS['_test_option_store']);

        $_POST['_wpnonce_add-tag'] = 'valid-core-nonce';
        $_POST['cat-title'] = '<b>Category Title</b>';
        $_POST['cat-words'] = 'one\\,two';

        Npcink_Toolbox_Seo_Category_Add_Meat::taxonomy_metadate(7);
        $this->assertSame('Category Title', $GLOBALS['_test_option_store']['npcink_site_toolbox_category_title_7']);
        $this->assertSame('one,two', $GLOBALS['_test_option_store']['npcink_site_toolbox_category_keywords_7']);

        $_POST = array(
            'action'    => 'editedtag',
            '_wpnonce'  => 'valid-core-nonce',
            'cat-title' => 'Edited Title',
            'cat-words' => 'alpha,beta',
        );

        Npcink_Toolbox_Seo_Category_Add_Meat::taxonomy_metadate(8);
        $this->assertSame('Edited Title', $GLOBALS['_test_option_store']['npcink_site_toolbox_category_title_8']);
        $this->assertSame('alpha,beta', $GLOBALS['_test_option_store']['npcink_site_toolbox_category_keywords_8']);
    }

    public function test_public_rate_limit_nonce_comes_from_the_rest_request(): void
    {
        $_SERVER['REMOTE_ADDR'] = '203.0.113.10';
        $_SERVER['HTTP_USER_AGENT'] = 'Test Agent';

        $request = new class {
            public function get_header($name)
            {
                return 'x-npcink-site-toolbox-nonce' === $name ? 'valid-rest-nonce' : '';
            }

            public function get_param($name)
            {
                return null;
            }
        };

        $callback = Npcink_Toolbox_Rate_Limiter::permission_callback_with_nonce(
            'search-log',
            'npcink_site_toolbox_public_api',
            array('max_requests' => 30, 'time_window' => 60)
        );

        $this->assertTrue($callback($request));

        $param_request = new class {
            public function get_header($name)
            {
                return '';
            }

            public function get_param($name)
            {
                return 'nonce' === $name ? 'valid-param-nonce' : null;
            }
        };
        $this->assertTrue($callback($param_request));

        $missing_request_result = $callback();
        $this->assertInstanceOf(WP_Error::class, $missing_request_result);
        $this->assertSame('invalid_nonce', $missing_request_result->get_error_code());

        $source = file_get_contents(dirname(__DIR__, 2) . '/includes/class-npcink-toolbox-rate-limiter.php');
        $this->assertIsString($source);
        $this->assertStringNotContainsString('$_REQUEST', $source);
        $this->assertStringNotContainsString("HTTP_X_MABOX_NONCE", $source);
        $this->assertStringContainsString("get_header('x-npcink-site-toolbox-nonce')", $source);
        $this->assertStringContainsString("get_param('nonce')", $source);
    }

    public function test_nonce_suppressions_are_limited_to_read_only_get_requests(): void
    {
        $files = array(
            'admin/class-npcink-toolbox-admin.php',
            'admin/partials/optimize/site/search_link_simplify.php',
        );
        $combined = '';

        foreach ($files as $file) {
            $source = file_get_contents(dirname(__DIR__, 2) . '/' . $file);
            $this->assertIsString($source);
            $this->assertStringNotContainsString('phpcs:disable', $source, $file);
            $combined .= $source;
        }

        // 三处均为只读 GET 搜索的合法豁免：搜索词读取、重定向与参数透传循环
        $this->assertSame(
            3,
            substr_count($combined, 'phpcs:ignore WordPress.Security.NonceVerification.Recommended')
        );
        $this->assertSame(3, substr_count($combined, 'no state is changed.'));
    }

    public function test_category_seo_uses_wordpress_core_form_nonce_actions(): void
    {
        $source = file_get_contents(
            dirname(__DIR__, 2) . '/admin/partials/function/seo/seo_category_add_meat.php'
        );
        $this->assertIsString($source);
        $this->assertStringContainsString("wp_verify_nonce(\$nonce, 'add-tag')", $source);
        $this->assertStringContainsString("wp_verify_nonce(\$nonce, 'update-tag_' . absint(\$term_id))", $source);
        $this->assertStringContainsString("current_user_can('manage_categories')", $source);
    }
}
