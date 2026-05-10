<?php
//简单SEO - 文章SEO
/**
 * title：文章标题
 * description：文章描述，拿不到就拿文章开头120字
 * keywords：文章标签
 */
if (!class_exists('MaBox_Seo_Single')) {
    class MaBox_Seo_Single
    {

        public static function run()
        {
            add_action('wp_head', array(__CLASS__, 'seo_single'), 1);
        }


        public static function seo_single()
        {
            //是文章
            if (is_singular()) {
                if (get_query_var('paged') < 2) {
                     //拿到文章的关键词
                     $tags = get_the_tags();
                     $keywords = '';
                     if ($tags) {
                         foreach ($tags as $tag) {
                             $keywords .= $tag->name . ', ';
                         }
                         $keywords = rtrim($keywords, ', '); // 去除最后一个逗号和空格
                     }
                     if ($keywords !== '' && $keywords !== false) {
                        echo '<meta name="keywords" content="' . $keywords . '" />';
                        echo "\n";
                    }

                    //拿到文章的描述
                    $description_data = get_the_excerpt();
                    if ($description_data !== '' &&  $description_data !== false) {
                        $description = mb_substr($description_data, 0, 55, 'utf-8'); //只取前40个字
                        echo '<meta name="description" content="' . $description . '" />';
                        echo "\n";
                    }
                   

                    //echo $description . $keywords;

                  
                }
            }
        }
    }
}
