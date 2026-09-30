<?php

defined('ABSPATH') || exit;

/**
 * Shared site-statistics provider and dynamic block renderer.
 */
final class Npcink_Toolbox_Site_Stats
{
    /** @var array<string,array{label:string,value:int}>|null */
    private static $cached_stats;

    /**
     * Register the dynamic block from its canonical block.json metadata.
     */
    public static function register_block()
    {
        if (!function_exists('register_block_type')) {
            return;
        }

        register_block_type(
            dirname(__DIR__) . '/blocks/site-stats',
            array('render_callback' => array(__CLASS__, 'render_block'))
        );
    }

    /**
     * Return one request-scoped snapshot of the site counts.
     *
     * @return array<string,array{label:string,value:int}>
     */
    public static function get_stats()
    {
        if (is_array(self::$cached_stats)) {
            return self::$cached_stats;
        }

        $post_counts    = wp_count_posts('post');
        $comment_counts = wp_count_comments();
        $user_counts    = count_users();
        $category_count = wp_count_terms('category');

        self::$cached_stats = array(
            'posts'      => array(
                'label' => __('文章', 'npcink-site-toolbox'),
                'value' => isset($post_counts->publish) ? (int) $post_counts->publish : 0,
            ),
            'comments'   => array(
                'label' => __('评论', 'npcink-site-toolbox'),
                'value' => isset($comment_counts->approved) ? (int) $comment_counts->approved : 0,
            ),
            'categories' => array(
                'label' => __('分类', 'npcink-site-toolbox'),
                'value' => is_wp_error($category_count) ? 0 : (int) $category_count,
            ),
            'users'      => array(
                'label' => __('用户', 'npcink-site-toolbox'),
                'value' => isset($user_counts['total_users']) ? (int) $user_counts['total_users'] : 0,
            ),
        );

        return self::$cached_stats;
    }

    /**
     * Render the statistic definition list used by the block and legacy widget.
     *
     * @param array<string,bool> $visibility Stat visibility keyed by stat name.
     * @param string             $class_name Additional list class.
     * @return string
     */
    public static function render_items($visibility = array(), $class_name = 'npcink-site-stats__items')
    {
        $stats = self::get_stats();
        $items = '';

        foreach ($stats as $key => $stat) {
            if (isset($visibility[ $key ]) && !$visibility[ $key ]) {
                continue;
            }

            $items .= sprintf(
                '<div class="npcink-site-stats__item"><dt class="npcink-site-stats__label">%1$s</dt><dd class="npcink-site-stats__value">%2$s</dd></div>',
                esc_html($stat['label']),
                esc_html(number_format_i18n($stat['value']))
            );
        }

        if ($items === '') {
            return '';
        }

        return '<dl class="' . esc_attr($class_name) . '">' . $items . '</dl>';
    }

    /**
     * Render one dynamic site-statistics block.
     *
     * @param array<string,mixed> $attributes Block attributes.
     * @return string
     */
    public static function render_block($attributes)
    {
        $title      = isset($attributes['title']) && is_string($attributes['title'])
            ? $attributes['title']
            : __('站点数据', 'npcink-site-toolbox');
        $visibility = array(
            'posts'      => !isset($attributes['showPosts']) || (bool) $attributes['showPosts'],
            'comments'   => !isset($attributes['showComments']) || (bool) $attributes['showComments'],
            'categories' => !isset($attributes['showCategories']) || (bool) $attributes['showCategories'],
            'users'      => !isset($attributes['showUsers']) || (bool) $attributes['showUsers'],
        );
        $items      = self::render_items($visibility);

        $heading = trim($title) === ''
            ? ''
            : '<h2 class="npcink-site-stats__title">' . esc_html($title) . '</h2>';
        $body    = $items !== ''
            ? $items
            : '<p class="npcink-site-stats__empty">' . esc_html__('请至少选择一个统计项目。', 'npcink-site-toolbox') . '</p>';

        return sprintf(
            '<section %1$s>%2$s%3$s</section>',
            get_block_wrapper_attributes(array('class' => 'npcink-site-stats')),
            $heading,
            $body
        );
    }
}
