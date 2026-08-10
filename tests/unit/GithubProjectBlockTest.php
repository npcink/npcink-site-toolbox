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
            if ($code !== '') {
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
            $code = $code !== '' ? $code : $this->get_error_code();
            return isset($this->errors[$code][0]) ? $this->errors[$code][0] : '';
        }

        public function get_error_data($code = '')
        {
            $code = $code !== '' ? $code : $this->get_error_code();
            return isset($this->error_data[$code]) ? $this->error_data[$code] : null;
        }
    }
}

if (!defined('NPCINK_SITE_TOOLBOX_VERSION')) {
    define('NPCINK_SITE_TOOLBOX_VERSION', '3.2.0');
}

if (!function_exists('wp_parse_url')) {
    function wp_parse_url($url, $component = -1)
    {
        return parse_url($url, $component);
    }
}
if (!function_exists('wp_safe_remote_get')) {
    function wp_safe_remote_get($url, $args = array())
    {
        $GLOBALS['_test_github_remote_calls'][] = array('url' => $url, 'args' => $args);
        return $GLOBALS['_test_github_remote_response'];
    }
}
if (!function_exists('wp_remote_retrieve_response_code')) {
    function wp_remote_retrieve_response_code($response)
    {
        return isset($response['response']['code']) ? (int) $response['response']['code'] : 0;
    }
}
if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response)
    {
        return isset($response['body']) ? (string) $response['body'] : '';
    }
}
if (!function_exists('is_wp_error')) {
    function is_wp_error($value)
    {
        return is_object($value) && is_a($value, 'WP_Error');
    }
}
if (!function_exists('absint')) {
    function absint($value)
    {
        return abs((int) $value);
    }
}
if (!function_exists('esc_html')) {
    function esc_html($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_html__')) {
    function esc_html__($text, $domain = 'default')
    {
        unset($domain);
        return esc_html($text);
    }
}
if (!function_exists('esc_attr')) {
    function esc_attr($text)
    {
        return htmlspecialchars((string) $text, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('esc_url')) {
    function esc_url($url)
    {
        return htmlspecialchars((string) $url, ENT_QUOTES, 'UTF-8');
    }
}
if (!function_exists('number_format_i18n')) {
    function number_format_i18n($number, $decimals = 0)
    {
        return number_format((float) $number, $decimals);
    }
}
if (!function_exists('get_block_wrapper_attributes')) {
    function get_block_wrapper_attributes($extra_attributes = array())
    {
        $extra_class = isset($extra_attributes['class']) ? (string) $extra_attributes['class'] : '';
        $block_class = strpos($extra_class, 'npcink-github-project') !== false
            ? 'wp-block-npcink-github-project'
            : 'wp-block-npcink-site-stats';

        return 'class="' . esc_attr(trim($block_class . ' ' . $extra_class)) . '"';
    }
}

require_once dirname(__DIR__, 2) . '/includes/class-npcink-toolbox-github-project.php';

final class GithubProjectBlockTest extends TestCase
{
    protected function setUp(): void
    {
        global $_test_transient_store;

        $_test_transient_store = array();
        $GLOBALS['_test_github_remote_calls'] = array();
        $GLOBALS['_test_github_remote_response'] = array(
            'response' => array('code' => 500),
            'body' => '',
        );
    }

    public function test_block_uses_metadata_registration_and_build_free_editor_source(): void
    {
        $metadata = json_decode($this->source('blocks/github-project/block.json'), true, 512, JSON_THROW_ON_ERROR);
        $asset = $this->source('blocks/github-project/index.asset.php');
        $editor = $this->source('blocks/github-project/index.js');
        $core = $this->source('includes/class-npcink-site-toolbox.php');
        $autoload = $this->source('includes/autoload.php');

        $this->assertSame('npcink/github-project', $metadata['name']);
        $this->assertSame(3, $metadata['apiVersion']);
        $this->assertSame('npcink-site-toolbox', $metadata['category']);
        $this->assertSame(array('repositoryUrl', 'customDescription'), array_keys($metadata['attributes']));
        $this->assertSame('', $metadata['attributes']['customDescription']['default']);
        $this->assertStringContainsString("'wp-server-side-render'", $asset);
        $this->assertStringContainsString("blocks.registerBlockType( 'npcink/github-project'", $editor);
        $this->assertStringContainsString('ServerSideRender', $editor);
        $this->assertStringContainsString("add_action('init', array('Npcink_Toolbox_Github_Project', 'register_block'))", $core);
        $this->assertStringContainsString("'Npcink_Toolbox_Github_Project' => 'includes/class-npcink-toolbox-github-project.php'", $autoload);
        $this->assertFileDoesNotExist($this->root() . '/vite/blocks');
    }

    public function test_repository_url_parser_accepts_repository_urls_only(): void
    {
        $method = new ReflectionMethod('Npcink_Toolbox_Github_Project', 'parse_repository_url');
        $method->setAccessible(true);

        $this->assertSame(
            array(
                'owner' => 'muze-page',
                'repo' => 'npcink-site-toolbox',
                'url' => 'https://github.com/muze-page/npcink-site-toolbox',
            ),
            $method->invoke(null, 'https://github.com/muze-page/npcink-site-toolbox.git')
        );

        foreach (array(
            'http://github.com/muze-page/npcink-site-toolbox',
            'https://example.com/muze-page/npcink-site-toolbox',
            'https://github.com/muze-page/npcink-site-toolbox/issues',
            'https://github.com/muze-page/npcink-site-toolbox?tab=readme',
            'https://github.com/-invalid/repository',
        ) as $invalid_url) {
            $this->assertNull($method->invoke(null, $invalid_url), $invalid_url);
        }
    }

    public function test_successful_repository_response_is_cached_and_escaped(): void
    {
        $GLOBALS['_test_github_remote_response'] = array(
            'response' => array('code' => 200),
            'body' => json_encode(array(
                'description' => '<script>alert("x")</script>实用项目 & 工具',
                'language' => 'PHP',
                'stargazers_count' => 1234,
                'forks_count' => 56,
                'archived' => true,
            )),
        );

        $attributes = array('repositoryUrl' => 'https://github.com/muze-page/npcink-site-toolbox');
        $first = Npcink_Toolbox_Github_Project::render_block($attributes);
        $second = Npcink_Toolbox_Github_Project::render_block($attributes);

        $this->assertSame($first, $second);
        $this->assertCount(1, $GLOBALS['_test_github_remote_calls']);
        $request = $GLOBALS['_test_github_remote_calls'][0];
        $this->assertSame('https://api.github.com/repos/muze-page/npcink-site-toolbox', $request['url']);
        $this->assertSame('application/vnd.github+json', $request['args']['headers']['Accept']);
        $this->assertSame('2022-11-28', $request['args']['headers']['X-GitHub-Api-Version']);
        $this->assertStringStartsWith('Npcink-Site-Toolbox/', $request['args']['headers']['User-Agent']);
        $this->assertSame(4, $request['args']['timeout']);
        $this->assertSame(2, $request['args']['redirection']);
        $this->assertSame(102400, $request['args']['limit_response_size']);
        $this->assertStringContainsString('npcink-github-project', $first);
        $this->assertStringContainsString('muze-page/npcink-site-toolbox', $first);
        $this->assertStringContainsString('实用项目 &amp; 工具', $first);
        $this->assertStringNotContainsString('<script>', $first);
        $this->assertStringContainsString('PHP', $first);
        $this->assertStringContainsString('1,234', $first);
        $this->assertStringContainsString('56', $first);
        $this->assertStringContainsString('已归档', $first);
    }

    public function test_custom_description_overrides_github_description_and_is_escaped(): void
    {
        $GLOBALS['_test_github_remote_response'] = array(
            'response' => array('code' => 200),
            'body' => json_encode(array(
                'description' => 'GitHub API description',
                'language' => 'PHP',
                'stargazers_count' => 12,
                'forks_count' => 3,
                'archived' => false,
            )),
        );

        $output = Npcink_Toolbox_Github_Project::render_block(array(
            'repositoryUrl' => 'https://github.com/muze-page/npcink-site-toolbox',
            'customDescription' => '面向站长的 <strong>中文摘要</strong> & 项目说明',
        ));

        $this->assertCount(1, $GLOBALS['_test_github_remote_calls']);
        $this->assertStringContainsString('面向站长的 中文摘要 &amp; 项目说明', $output);
        $this->assertStringNotContainsString('<strong>', $output);
        $this->assertStringNotContainsString('GitHub API description', $output);
        $this->assertStringContainsString('<dd>12</dd>', $output);
    }

    public function test_custom_description_survives_github_api_failure(): void
    {
        $GLOBALS['_test_github_remote_response'] = array(
            'response' => array('code' => 403),
            'body' => '{"message":"rate limit"}',
        );

        $output = Npcink_Toolbox_Github_Project::render_block(array(
            'repositoryUrl' => 'https://github.com/muze-page/npcink-site-toolbox',
            'customDescription' => '即使接口暂不可用，也应显示这段站内摘要。',
        ));

        $this->assertStringContainsString('即使接口暂不可用，也应显示这段站内摘要。', $output);
        $this->assertStringContainsString('暂时无法读取项目数据', $output);
        $this->assertStringContainsString('href="https://github.com/muze-page/npcink-site-toolbox"', $output);
    }

    public function test_failed_repository_response_is_negative_cached_with_direct_link_fallback(): void
    {
        $GLOBALS['_test_github_remote_response'] = array(
            'response' => array('code' => 403),
            'body' => '{"message":"rate limit"}',
        );
        $attributes = array('repositoryUrl' => 'https://github.com/muze-page/npcink-site-toolbox');

        $first = Npcink_Toolbox_Github_Project::render_block($attributes);
        $second = Npcink_Toolbox_Github_Project::render_block($attributes);

        $this->assertSame($first, $second);
        $this->assertCount(1, $GLOBALS['_test_github_remote_calls']);
        $this->assertStringContainsString('暂时无法读取项目数据', $first);
        $this->assertStringContainsString('href="https://github.com/muze-page/npcink-site-toolbox"', $first);
    }

    public function test_structurally_invalid_success_response_uses_negative_cache_and_fallback(): void
    {
        global $_test_transient_store;

        $GLOBALS['_test_github_remote_response'] = array(
            'response' => array('code' => 200),
            'body' => '{}',
        );
        $attributes = array('repositoryUrl' => 'https://github.com/muze-page/npcink-site-toolbox');

        $first = Npcink_Toolbox_Github_Project::render_block($attributes);
        $second = Npcink_Toolbox_Github_Project::render_block($attributes);

        $this->assertSame($first, $second);
        $this->assertCount(1, $GLOBALS['_test_github_remote_calls']);
        $this->assertStringContainsString('暂时无法读取项目数据', $first);
        $this->assertStringContainsString('href="https://github.com/muze-page/npcink-site-toolbox"', $first);
        $this->assertSame(
            array('status' => 'error'),
            $_test_transient_store['npcink_site_toolbox_github_2ec6f3a633bd1e653106b2475c95f63d']
        );
    }

    /**
     * @dataProvider remote_failure_provider
     * @param mixed $response Simulated WordPress HTTP API response.
     */
    public function test_remote_failure_matrix_uses_one_negative_cached_request($response): void
    {
        $GLOBALS['_test_github_remote_response'] = $response;
        $attributes = array('repositoryUrl' => 'https://github.com/muze-page/npcink-site-toolbox');

        $first = Npcink_Toolbox_Github_Project::render_block($attributes);
        $second = Npcink_Toolbox_Github_Project::render_block($attributes);

        $this->assertSame($first, $second);
        $this->assertCount(1, $GLOBALS['_test_github_remote_calls']);
        $this->assertStringContainsString('暂时无法读取项目数据', $first);
        $this->assertStringContainsString('href="https://github.com/muze-page/npcink-site-toolbox"', $first);
    }

    /**
     * @return array<string,array{0:mixed}>
     */
    public function remote_failure_provider(): array
    {
        return array(
            'not found' => array(array('response' => array('code' => 404), 'body' => '{}')),
            'rate limited' => array(array('response' => array('code' => 429), 'body' => '{}')),
            'network error' => array(new WP_Error()),
            'malformed json' => array(array('response' => array('code' => 200), 'body' => '{bad')),
        );
    }

    public function test_cache_ttls_match_the_documented_success_and_failure_windows(): void
    {
        $reflection = new ReflectionClass('Npcink_Toolbox_Github_Project');

        $this->assertSame(43200, $reflection->getConstant('CACHE_TTL'));
        $this->assertSame(1800, $reflection->getConstant('ERROR_CACHE_TTL'));
    }

    public function test_invalid_repository_url_renders_nothing_without_a_remote_request(): void
    {
        $output = Npcink_Toolbox_Github_Project::render_block(array(
            'repositoryUrl' => 'https://example.com/owner/repository',
        ));

        $this->assertSame('', $output);
        $this->assertSame(array(), $GLOBALS['_test_github_remote_calls']);
    }

    public function test_release_contract_and_external_service_disclosure_include_the_block(): void
    {
        $build = $this->source('bin/build-release-zip.sh');
        $verify = $this->source('bin/verify-release-zip.sh');
        $readme = $this->source('readme.txt');

        foreach (array('block.json', 'index.js', 'index.asset.php', 'style.css') as $file) {
            $path = 'blocks/github-project/' . $file;
            $this->assertStringContainsString($path, $build);
            $this->assertStringContainsString($path, $verify);
        }
        $this->assertStringContainsString('includes/class-npcink-toolbox-github-project.php', $verify);
        $this->assertStringContainsString('= GitHub project block =', $readme);
        $this->assertStringContainsString('Service and endpoint documentation', $readme);
        $this->assertStringNotContainsString('https://api.github.com/repos/{owner}/{repository}', $readme);
        $this->assertStringContainsString('GitHub Terms of Service', $readme);
        $this->assertStringContainsString('GitHub General Privacy Statement', $readme);
    }

    private function source(string $relative_path): string
    {
        $source = file_get_contents($this->root() . '/' . $relative_path);
        $this->assertIsString($source);

        return $source;
    }

    private function root(): string
    {
        return dirname(__DIR__, 2);
    }
}
