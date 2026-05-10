<?php

/**
 * 效果：屏蔽恶意关键词搜索词
 * 来源：https://www.npc.ink/277953.html
 */
if (!class_exists('MaBox_Ban_Malice_Search')) {
    class MaBox_Ban_Malice_Search
    {

        /**
         * @param $keyword_arr 关键词数组
         */
        public static function run($keyword_arr)
        {
            // 使用 use 语法将 $keyword_arr 传递给回调函数
            add_action('template_redirect', function () use ($keyword_arr) {
                self::ban_malice_search($keyword_arr);
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
                            $message = $message . MaBox_Admin::back_button();
                            wp_die($message);
                        }
                    }
                }
            }
        }
    }
}
