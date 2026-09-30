<?php
defined('ABSPATH') || exit;

/**
 * 效果：屏蔽恶意关键词搜索词
 * 历史来源文章已下线，实现事实以当前代码和测试为准。
 */
if (!class_exists('Npcink_Toolbox_Ban_Malice_Search')) {
    class Npcink_Toolbox_Ban_Malice_Search implements Npcink_Toolbox_Module_Interface
    {

        /**
         * @param array $config 辅助功能配置。
         */
        public static function run($config = array())
        {
            $keyword_content = isset($config['malice_keu_content']) && is_string($config['malice_keu_content'])
                ? $config['malice_keu_content']
                : '';

            add_action('template_redirect', function () use ($keyword_content) {
                self::ban_malice_search($keyword_content);
            });
        }

        //屏蔽恶意关键词搜索
        public static function ban_malice_search($keyword_arr)
        {
            $malice_keu_content = $keyword_arr;

            if (!is_search()) {
                return;
            }
            global $wp_query;
            $search_term = isset($wp_query->query_vars['s']) && is_string($wp_query->query_vars['s'])
                ? $wp_query->query_vars['s']
                : '';
            if ('' === $search_term || '' === $malice_keu_content) {
                return;
            }

            $keyword_list = str_replace("\n", '|', $malice_keu_content);
            // 关键词列表中的空行会产生空关键词；PHP 8 下空串匹配任何搜索词，
            // 会把全站搜索全部拦截，因此必须跳过空白条目
            foreach (explode('|', $keyword_list) as $Key) {
                $Key = trim( (string) $Key);
                if ('' === $Key) {
                    continue;
                }
                if (false !== stripos($search_term, $Key)) {
                    $message      = __('搜索内容包含敏感词，请换个关键词搜索', 'npcink-site-toolbox');
                    $message      = $message . Npcink_Toolbox_Admin::back_button();
                    $allowed_html = array(
                        'p' => array(),
                        'a' => array(
                            'href'  => true,
                            'class' => true,
                        ),
                    );
                    wp_die(wp_kses($message, $allowed_html));
                }
            }
        }
    }
}
