<?php

defined('ABSPATH') || exit;

/**
 * 效果：文章中出现的标签自动添加链接
 * 来源：https://www.npc.ink/15286.html
 */
if (!class_exists('Npcink_Toolbox_Single_Keyword_Add_Link')) {
    class Npcink_Toolbox_Single_Keyword_Add_Link implements Npcink_Toolbox_Module_Interface
    {
        //加载
        public static function run($config = array())
        {
            add_filter('the_content', array(__CLASS__, 'tag_link'), 1);
        }

        //按长度排序
        public static function tag_sort($a, $b)
        {
            if ($a->name == $b->name) {
                return 0;
            }

            return (strlen($a->name) > strlen($b->name)) ? -1 : 1;
        }
        //改变标签关键字
        public static function tag_link($content)
        {
            $content = wp_kses_post((string) $content);
            //连接数量
            $match_num_from = 1; //一篇文章中同一个关键字少于多少不锚文本（这个直接填1就好了）
            $match_num_to = 3; //一篇文章中同一个关键字最多出现多少次锚文本（建议不超过1次）
            $posttags = get_the_tags();
            if ($posttags) {
                usort($posttags, array(__CLASS__, "tag_sort"));
                foreach ($posttags as $tag) {
                    $link = get_tag_link($tag->term_id);
                    $keyword = wp_strip_all_tags((string) $tag->name);
                    //连接代码
                    $cleankeyword = stripslashes($keyword);
                    /* translators: %s: Tag name used in the generated link title. */
                    $title = sprintf(__('查看所有文章关于 %s', 'npcink-site-toolbox'), $cleankeyword);
                    $url = '<strong><a href="' . esc_url($link) . '" title="' . esc_attr($title) . '"';
                    $url .= 'target="_blank"';
                    $url .= '>' . esc_html($cleankeyword) . '</a></strong>';
                    $limit = wp_rand($match_num_from, $match_num_to);
                    //不连接的代码
                    $ex_word = '';
                    $case = '';
                    $content = preg_replace('|(<a[^>]+>)(.*)(' . $ex_word . ')(.*)(</a[^>]*>)|U' . $case, '$1$2%&&&&&%$4$5', $content);
                    $content = preg_replace('|(<img)(.*?)(' . $ex_word . ')(.*?)(>)|U' . $case, '$1$2%&&&&&%$4$5', $content);
                    $cleankeyword = preg_quote($cleankeyword, '/');
                    $regEx = '/(?!((<.*?)|(<a.*?)))(' . $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))/is' . $case;
                    $content = preg_replace_callback($regEx, static function () use ($url) {
                        return $url;
                    }, $content, $limit);
                    $content = str_replace('%&&&&&%', stripslashes($ex_word), $content);
                }
            }
            return wp_kses_post($content);
        }
    }
}
