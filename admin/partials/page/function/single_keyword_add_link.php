<?php

/**
 * 效果：文章中出现的标签自动添加链接
 * 来源：https://www.npc.ink/15286.html
 */
if (!class_exists('MaBox_Single_Keyword_Add_Link')) {
    class MaBox_Single_Keyword_Add_Link
    {
        //加载
        public static function run()
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
            //连接数量
            $match_num_from = 1; //一篇文章中同一个关键字少于多少不锚文本（这个直接填1就好了）
            $match_num_to = 3; //一篇文章中同一个关键字最多出现多少次锚文本（建议不超过1次）
            $posttags = get_the_tags();
            if ($posttags) {
                usort($posttags, array(__CLASS__, "tag_sort"));
                foreach ($posttags as $tag) {
                    $link = get_tag_link($tag->term_id);
                    $keyword = $tag->name;
                    //连接代码
                    $cleankeyword = stripslashes($keyword);
                    $url = "<strong><a href=\"$link\" title=\"" . str_replace('%s', addcslashes($cleankeyword, '$'), __('查看所有文章关于 %s')) . "\"";
                    $url .= 'target="_blank"';
                    $url .= ">" . addcslashes($cleankeyword, '$') . "</a></strong>";
                    $limit = rand($match_num_from, $match_num_to);
                    //不连接的代码
                    $ex_word = '';
                    $case = '';
                    $content = preg_replace('|(<a[^>]+>)(.*)(' . $ex_word . ')(.*)(</a[^>]*>)|U' . $case, '$1$2%&&&&&%$4$5', $content);
                    $content = preg_replace('|(<img)(.*?)(' . $ex_word . ')(.*?)(>)|U' . $case, '$1$2%&&&&&%$4$5', $content);
                    $cleankeyword = preg_quote($cleankeyword, '\'');
                    $regEx = '\'(?!((<.*?)|(<a.*?)))(' . $cleankeyword . ')(?!(([^<>]*?)>)|([^>]*?</a>))\'s' . $case;
                    $content = preg_replace($regEx, $url, $content, $limit);
                    $content = str_replace('%&&&&&%', stripslashes($ex_word), $content);
                }
            }
            return $content;
        }
    }
}
