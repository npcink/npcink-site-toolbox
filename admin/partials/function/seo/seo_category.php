<?php

/**
 * 效果：简单SEO - 分类和标签TDK 
 * 来源：https://www.npc.ink/4596.html
 */
if (!class_exists('MaBox_Seo_Category')) {
    class MaBox_Seo_Category
    {
        public static function run()
        {
            add_action('wp', array(__CLASS__, 'add_meta'));
        }

        public static function add_meta()
        {
            //判断是分类
            if (is_category()) {
                if (get_query_var('paged') < 2) {
                    add_action('wp_head', array(__CLASS__, 'seo_category'), 1);
                    //分类ID
                    $term_id = get_query_var('cat');
                    //分类标题
                    $title = get_option('cat-title-' . $term_id);
                    if ($title !== '' && $title !== false) {
                        remove_action('wp_head', '_wp_render_title_tag', 1); //移除默认标题
                    }
                }
            }
        }

        public static function seo_category()
        {
            //分类ID
            $term_id = get_query_var('cat');

            //分类标题
            $title = get_option('cat-title-' . $term_id);
            if ($title !== '' && $title !== false) {
                echo '<title>' . $title . '</title>';
                echo "\n";
            }

            //分类关键词
            $keywords = get_option('cat-words-' . $term_id);
            if ($keywords !== '' && $keywords !== false) {
                echo '<meta name="keywords" content="' . $keywords . '" />';
                echo "\n";
            }

            //分类描述，
            $category = get_queried_object(); // 获取当前分类对象
            if ($category) {
                $description_data = $category->description; // 获取分类描述
            }
           
            if ($description_data !== '' &&  $description_data !== false) {
                $description = mb_substr($description_data, 0, 55, 'utf-8'); //只取前40个字
                echo '<meta name="description" content="' . $description . '" />';
                echo "\n";
            }
        }
    }
}
