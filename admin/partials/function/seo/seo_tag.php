<?php
defined('ABSPATH') || exit;
//简单SEO - 文章SEO
/**
 * title：标签标题
 * description：标签描述，
 * keywords：标签关键词
 * 只做了描述，标签标题和标签关键词没做
 */
if (!class_exists('Npcink_Toolbox_Seo_Tag')) {
    class Npcink_Toolbox_Seo_Tag implements Npcink_Toolbox_Module_Interface
    {

        public static function run($config = array())
        {
            add_action('wp_head', array(__CLASS__, 'seo_tag'), 1);
        }
        public static function seo_tag()
        {
            //是标签
            if (is_tag()) {
                if (get_query_var('paged') < 2) {
                    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- 保留的历史模板参考注释
                    //标签ID
                    // $term_id = get_query_var('tag_id');

                    // phpcs:ignore Squiz.PHP.CommentedOutCode.Found -- 保留的历史模板参考注释
                    //分类关键词
                    // $keywords =  get_option('tag-words-' . $term_id);
                    // if ($keywords !== '' && $keywords !== false) {
                    //     echo '<meta name="keywords" content="' . $keywords . '" />';
                    //     echo "\n";
                    // }

                    //拿到标签的描述，关键词
                    $description_data = trim(wp_strip_all_tags(tag_description()));
                    if ($description_data !== '' && $description_data !== false) {
                        $description = mb_substr($description_data, 0, 55, 'utf-8'); //只取前40个字
                        echo '<meta name="description" content="' . esc_attr($description) . '" />';
                        echo "\n";
                    }
                }
            }
        }
    }
}
