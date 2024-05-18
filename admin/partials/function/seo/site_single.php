<?php
//简单SEO - 文章SEO
/**
 * title：文章标题
 * description：文章描述，拿不到就拿文章开头120字
 * keywords：文章标签
 */
if (!class_exists('Npcink_Seo_Single')) {
    class Npcink_Seo_Single
    {

        public static function run()
        {
            add_action('wp_head', array(__CLASS__, 'single_seo'), 1);
        }


        public static function single_seo()
        {
            //是文章
            if (is_singular()) {
                //文章ID
                //拿到文章的描述，关键词
                $description_data = get_the_excerpt();
               
                $description = mb_substr($description_data, 0, 55, 'utf-8'); //只取前40个字

                //拿到文章的关键词
                $tags = get_the_tags();
                $keywords = '';
                if ($tags) {
                    foreach ($tags as $tag) {
                        $keywords .= $tag->name . ', ';
                    }
                    $keywords = rtrim($keywords, ', '); // 去除最后一个逗号和空格
                }

                //echo $description . $keywords;

                echo '<meta name="keywords" content="' . $keywords . '" />';
                echo "\n";
                echo '<meta name="description" content="' . $description . '" />';
                echo "\n";
            }
        }
    }
}
