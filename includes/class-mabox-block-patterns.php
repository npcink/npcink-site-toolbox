<?php

defined('ABSPATH') || exit;

/**
 * Register shared editor categories and reusable core-block patterns.
 */
final class MaBox_Block_Patterns
{
    /**
     * Add the plugin category to the block inserter without disturbing core order.
     *
     * @param array<int,array<string,mixed>> $categories Registered block categories.
     * @return array<int,array<string,mixed>>
     */
    public static function add_block_category($categories)
    {
        foreach ($categories as $category) {
            if (isset($category['slug']) && $category['slug'] === 'npcink-site-toolbox') {
                return $categories;
            }
        }

        $categories[] = array(
            'slug'  => 'npcink-site-toolbox',
            'title' => __('Npcink Site Toolbox', 'npcink-site-toolbox'),
        );

        return $categories;
    }

    /**
     * Register the plugin pattern category and patterns.
     */
    public static function register()
    {
        if (!function_exists('register_block_pattern')) {
            return;
        }

        if (function_exists('register_block_pattern_category')) {
            register_block_pattern_category(
                'npcink-site-toolbox',
                array('label' => __('Npcink Site Toolbox', 'npcink-site-toolbox'))
            );
        }

        foreach (self::definitions() as $slug => $definition) {
            $content = self::load_content($definition['file']);
            if ($content === '') {
                continue;
            }

            unset($definition['file']);
            $definition['content'] = $content;

            register_block_pattern('npcink-site-toolbox/' . $slug, $definition);
        }
    }

    /**
     * Return pattern definitions without loading their content prematurely.
     *
     * @return array<string,array<string,mixed>>
     */
    public static function definitions()
    {
        return array(
            'resource-download-card' => array(
                'title'       => __('资源下载卡片', 'npcink-site-toolbox'),
                'description' => __('突出展示资源名称、简短说明、文件信息和下载入口。', 'npcink-site-toolbox'),
                'categories'  => array('npcink-site-toolbox', 'call-to-action'),
                'keywords'    => array(
                    __('下载', 'npcink-site-toolbox'),
                    __('资源', 'npcink-site-toolbox'),
                    __('文件', 'npcink-site-toolbox'),
                ),
                'viewportWidth' => 720,
                'file'          => 'resource-download-card.php',
            ),
            'key-takeaway-card' => array(
                'title'       => __('文章结论卡片', 'npcink-site-toolbox'),
                'description' => __('用简洁的结论和要点列表收束文章。', 'npcink-site-toolbox'),
                'categories'  => array('npcink-site-toolbox', 'text'),
                'keywords'    => array(
                    __('结论', 'npcink-site-toolbox'),
                    __('要点', 'npcink-site-toolbox'),
                    __('总结', 'npcink-site-toolbox'),
                ),
                'viewportWidth' => 720,
                'file'          => 'key-takeaway-card.php',
            ),
            'source-copyright-note' => array(
                'title'       => __('来源与版权说明', 'npcink-site-toolbox'),
                'description' => __('在文章末尾清晰说明资料来源、授权方式和转载要求。', 'npcink-site-toolbox'),
                'categories'  => array('npcink-site-toolbox', 'text'),
                'keywords'    => array(
                    __('来源', 'npcink-site-toolbox'),
                    __('版权', 'npcink-site-toolbox'),
                    __('转载', 'npcink-site-toolbox'),
                ),
                'viewportWidth' => 720,
                'file'          => 'source-copyright-note.php',
            ),
        );
    }

    /**
     * Load one pattern file from the fixed plugin pattern directory.
     *
     * @param string $filename Pattern filename from a trusted definition.
     * @return string
     */
    private static function load_content($filename)
    {
        $path = dirname(__DIR__) . '/patterns/' . $filename;
        if (!is_readable($path)) {
            return '';
        }

        $content = include $path;

        return is_string($content) ? trim($content) : '';
    }
}
