<?php

defined('ABSPATH') || exit;

/**
 * Dynamic GitHub project block backed by GitHub's public repository API.
 */
final class Npcink_Toolbox_Github_Project
{
    private const API_VERSION     = '2022-11-28';
    private const CACHE_TTL       = 43200;
    private const ERROR_CACHE_TTL = 1800;

    /**
     * Register the dynamic block from its canonical block.json metadata.
     */
    public static function register_block()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        register_block_type(
            dirname(__DIR__) . '/blocks/github-project',
            array('render_callback' => array(__CLASS__, 'render_block'))
        );
    }

    /**
     * Render a GitHub project card.
     *
     * @param array<string,mixed> $attributes Block attributes.
     * @return string
     */
    public static function render_block($attributes)
    {
        $repository_url = isset($attributes['repositoryUrl']) && is_string($attributes['repositoryUrl'])
            ? $attributes['repositoryUrl']
            : '';
        $repository     = self::parse_repository_url($repository_url);

        if ($repository === null) {
            return '';
        }

        $custom_description = isset($attributes['customDescription']) && is_string($attributes['customDescription'])
            ? sanitize_text_field($attributes['customDescription'])
            : '';
        $data               = self::get_repository_data($repository);
        $project_name       = $repository['owner'] . '/' . $repository['repo'];
        $description        = $custom_description !== ''
            ? $custom_description
            : (is_array($data) ? $data['description'] : '');
        $archive_badge      = is_array($data) && $data['archived']
            ? '<span class="npcink-github-project__badge">' . esc_html__('已归档', 'npcink-site-toolbox') . '</span>'
            : '';
        $description_markup = $description !== ''
            ? '<p class="npcink-github-project__description">' . esc_html($description) . '</p>'
            : '';
        $metadata           = is_array($data)
            ? self::render_metadata($data)
            : '<p class="npcink-github-project__status">'
                . esc_html__('暂时无法读取项目数据，可直接前往 GitHub 查看。', 'npcink-site-toolbox')
                . '</p>';
        $link_label         = sprintf(
            /* translators: %s: GitHub repository owner and name. */
            __('在 GitHub 查看 %s', 'npcink-site-toolbox'),
            $project_name
        );

        return sprintf(
            '<article %1$s><div class="npcink-github-project__header"><div><span class="npcink-github-project__eyebrow">GitHub</span><p class="npcink-github-project__name">%2$s</p></div>%3$s</div>%4$s%5$s<a class="npcink-github-project__link" href="%6$s" aria-label="%7$s">%8$s</a></article>',
            get_block_wrapper_attributes(array('class' => 'npcink-github-project')),
            esc_html($project_name),
            $archive_badge,
            $description_markup,
            $metadata,
            esc_url($repository['url']),
            esc_attr($link_label),
            esc_html__('查看 GitHub 项目', 'npcink-site-toolbox')
        );
    }

    /**
     * Render the repository metadata returned by GitHub.
     *
     * @param array{description:string,language:string,stars:int,forks:int,archived:bool} $data Repository data.
     * @return string
     */
    private static function render_metadata($data)
    {
        $items = '';

        if ($data['language'] !== '') {
            $items .= sprintf(
                '<div><dt>%1$s</dt><dd>%2$s</dd></div>',
                esc_html__('主要语言', 'npcink-site-toolbox'),
                esc_html($data['language'])
            );
        }

        $items .= sprintf(
            '<div><dt>Stars</dt><dd>%1$s</dd></div><div><dt>Forks</dt><dd>%2$s</dd></div>',
            esc_html(number_format_i18n($data['stars'])),
            esc_html(number_format_i18n($data['forks']))
        );

        return '<dl class="npcink-github-project__metadata">' . $items . '</dl>';
    }

    /**
     * Read one repository from cache or GitHub's public API.
     *
     * @param array{owner:string,repo:string,url:string} $repository Validated repository identity.
     * @return array{description:string,language:string,stars:int,forks:int,archived:bool}|null
     */
    private static function get_repository_data($repository)
    {
        $cache_key = 'npcink_site_toolbox_github_' . md5(strtolower($repository['owner'] . '/' . $repository['repo']));
        $cached    = get_transient($cache_key);

        if (is_array($cached) && isset($cached['status'])) {
            if ($cached['status'] === 'success' && isset($cached['data']) && is_array($cached['data'])) {
                return $cached['data'];
            }
            if ($cached['status'] === 'error') {
                return null;
            }
        }

        $api_url  = sprintf(
            'https://api.github.com/repos/%1$s/%2$s',
            rawurlencode($repository['owner']),
            rawurlencode($repository['repo'])
        );
        $response = wp_safe_remote_get($api_url, array(
            'timeout'             => 4,
            'redirection'         => 2,
            'limit_response_size' => 102400,
            'headers'             => array(
                'Accept'               => 'application/vnd.github+json',
                'User-Agent'           => 'Npcink-Site-Toolbox/' . NPCINK_SITE_TOOLBOX_VERSION,
                'X-GitHub-Api-Version' => self::API_VERSION,
            ),
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            self::cache_failure($cache_key);
            return null;
        }

        $payload = json_decode(wp_remote_retrieve_body($response), true);
        if (!is_array($payload) || !self::is_valid_repository_payload($payload)) {
            self::cache_failure($cache_key);
            return null;
        }

        $data = array(
            'description' => isset($payload['description']) && is_string($payload['description'])
                ? sanitize_text_field($payload['description'])
                : '',
            'language'    => isset($payload['language']) && is_string($payload['language'])
                ? sanitize_text_field($payload['language'])
                : '',
            'stars'       => isset($payload['stargazers_count']) ? absint($payload['stargazers_count']) : 0,
            'forks'       => isset($payload['forks_count']) ? absint($payload['forks_count']) : 0,
            'archived'    => !empty($payload['archived']),
        );

        set_transient($cache_key, array('status' => 'success', 'data' => $data), self::CACHE_TTL);

        return $data;
    }

    /**
     * Reject successful HTTP responses that do not match GitHub's repository shape.
     *
     * @param array<string,mixed> $payload Decoded GitHub response.
     * @return bool
     */
    private static function is_valid_repository_payload($payload)
    {
        if (
            !array_key_exists('description', $payload)
            || ($payload['description'] !== null && !is_string($payload['description']))
            || !array_key_exists('language', $payload)
            || ($payload['language'] !== null && !is_string($payload['language']))
            || !isset($payload['stargazers_count'])
            || !is_int($payload['stargazers_count'])
            || !isset($payload['forks_count'])
            || !is_int($payload['forks_count'])
            || !array_key_exists('archived', $payload)
            || !is_bool($payload['archived'])
        ) {
            return false;
        }

        return true;
    }

    /**
     * Avoid retrying an unavailable or rate-limited repository on every page view.
     *
     * @param string $cache_key Transient key.
     */
    private static function cache_failure($cache_key)
    {
        set_transient($cache_key, array('status' => 'error'), self::ERROR_CACHE_TTL);
    }

    /**
     * Accept one canonical public github.com repository URL and reject extra paths.
     *
     * @param string $url Candidate repository URL.
     * @return array{owner:string,repo:string,url:string}|null
     */
    private static function parse_repository_url($url)
    {
        $parts = wp_parse_url(trim($url));
        if (!is_array($parts)) {
            return null;
        }

        $scheme = isset($parts['scheme']) && is_string($parts['scheme']) ? strtolower($parts['scheme']) : '';
        $host   = isset($parts['host']) && is_string($parts['host']) ? strtolower($parts['host']) : '';
        if ($scheme !== 'https' || !in_array($host, array('github.com', 'www.github.com'), true)) {
            return null;
        }
        if (isset($parts['user']) || isset($parts['pass']) || isset($parts['port']) || isset($parts['query']) || isset($parts['fragment'])) {
            return null;
        }

        $segments = isset($parts['path']) && is_string($parts['path'])
            ? array_values(array_filter(explode('/', trim($parts['path'], '/')), 'strlen'))
            : array();
        if (count($segments) !== 2) {
            return null;
        }

        $owner         = $segments[0];
        $repo          = preg_replace('/\.git$/i', '', $segments[1]);
        $owner_pattern = '/^[A-Za-z0-9](?:[A-Za-z0-9-]{0,37}[A-Za-z0-9])?$/';
        $repo_pattern  = '/^[A-Za-z0-9._-]{1,100}$/';
        if (!is_string($repo) || !preg_match($owner_pattern, $owner) || !preg_match($repo_pattern, $repo)) {
            return null;
        }
        if ($repo === '.' || $repo === '..') {
            return null;
        }

        return array(
            'owner' => $owner,
            'repo'  => $repo,
            'url'   => 'https://github.com/' . rawurlencode($owner) . '/' . rawurlencode($repo),
        );
    }
}
