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

            if (is_search()) {
                global $wp_query;
                //拿到输入的值
                $ytkah_search_key = $malice_keu_content;
                if ($ytkah_search_key) {
                    $ytkah_search_key = str_replace("\n", "|", $ytkah_search_key);
                    $BanKey = explode('|', $ytkah_search_key);
                    $S_Key = $wp_query->query_vars;
                    foreach ($BanKey as $Key) {
                        if (stristr($S_Key['s'], $Key) != false) {
                            $message = '搜索内容包含敏感词，请换个关键词搜索';
                            $message = $message . Npcink_Toolbox_Admin::back_button();
                            $allowed_html = array(
                                'br'     => array(),
                                'a'      => array(
                                    'href'    => true,
                                    'onclick' => true,
                                    'class'   => true,
                                ),
                                'button' => array('class' => true),
                                'style'  => array(),
                            );
                            wp_die(wp_kses($message, $allowed_html));
                        }
                    }
                }
            }
        }
    }
}
