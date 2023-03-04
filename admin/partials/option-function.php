<?php
/**
 * 功能选项
 */
if (!class_exists('Magick_Mixtrue_Fuction')) {
    class Magick_Mixtrue_Fuction
    {
        //加载
        public static function run()
        {
            add_action('init', array(__CLASS__, 'load_run'));

        }
        //准备
        public static function load_run()
        {
            //屏蔽恶意关键词搜索
            if (carbon_get_theme_option('cmma_ban_search_keywords')) {
                add_action('template_redirect', array(__CLASS__, 'ytkah_search_ban'));
            }

        }

        //屏蔽恶意关键词搜索
        public static function ytkah_search_ban()
        {

            if (is_search()) {
                global $wp_query;
                //拿到输入的值
                $ytkah_search_key = carbon_get_theme_option('cmma_ban_search_keywords_content');
                if ($ytkah_search_key) {
                    $ytkah_search_key = str_replace("\r\n", "|", $ytkah_search_key);
                    $BanKey = explode('|', $ytkah_search_key);
                    $S_Key = $wp_query->query_vars;
                    foreach ($BanKey as $Key) {
                        if (stristr($S_Key['s'], $Key) != false) {
                            wp_die('好像搜索了什么不宜展示的东西呢');
                        }
                    }
                }
            }
        }

    } //end class
} //end if
