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
            // 优先级 12：在 do_blocks(9) 与 do_shortcode(11) 之后处理已渲染 HTML。
            // 过早介入再叠加 wp_kses_post 会剥掉区块标记（WP 6.3-6.5）与
            // data-* 属性（互操作性区块），导致动态区块前台渲染异常。
            add_filter('the_content', array(__CLASS__, 'tag_link'), 12);
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
            // 订阅源保持内容原样，不注入内链
            if (is_feed()) {
                return $content;
            }
            //连接数量
            $match_num_from = 1; //一篇文章中同一个关键字少于多少不锚文本（这个直接填1就好了）
            $match_num_to = 3; //一篇文章中同一个关键字最多出现多少次锚文本（建议不超过1次）
            $posttags = get_the_tags();
            if (!$posttags) {
                return $content;
            }
            usort($posttags, array(__CLASS__, 'tag_sort'));

            // 暂存代码与预格式块，避免其中的关键词被链接；
            // 使用私有区字符包裹，保证占位符不会被当作普通关键词匹配
            $vault = array();
            $content = preg_replace_callback(
                '/<(code|pre)\b[^>]*>.*?<\/\1>/is',
                static function ($matches) use (&$vault) {
                    $vault[] = $matches[0];
                    return "\u{E000}npcink-kw-vault-" . (count($vault) - 1) . "\u{E001}";
                },
                (string) $content
            );

            foreach ($posttags as $tag) {
                $link = get_tag_link($tag->term_id);
                $keyword = wp_strip_all_tags((string) $tag->name);
                if ('' === $keyword) {
                    continue;
                }
                //连接代码
                $cleankeyword = stripslashes($keyword);
                /* translators: %s: Tag name used in the generated link title. */
                $title = sprintf(__('查看所有文章关于 %s', 'npcink-site-toolbox'), $cleankeyword);
                $url = '<strong><a href="' . esc_url($link) . '" title="' . esc_attr($title) . '"';
                $url .= ' target="_blank" rel="noopener"';
                $url .= '>' . esc_html($cleankeyword) . '</a></strong>';
                $limit = wp_rand($match_num_from, $match_num_to);
                $case = '';
                $cleankeyword = preg_quote($cleankeyword, '~');
                // 使用 ~ 分隔符：模式中的 </a> 含 / 字符，用 / 作分隔符会使正则非法
                $regEx = '~(?!((<.*?)|(<a.*?)))(' . $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))~is' . $case;
                $content = preg_replace_callback($regEx, static function () use ($url) {
                    return $url;
                }, $content, $limit);
            }

            foreach ($vault as $index => $segment) {
                $content = str_replace("\u{E000}npcink-kw-vault-" . $index . "\u{E001}", $segment, (string) $content);
            }
            return $content;
        }
    }
}
