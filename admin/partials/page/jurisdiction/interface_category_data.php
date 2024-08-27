<?php

/**
 * 接口 - 提供分类和标签数据接口
 */

if (!class_exists('Npcink_Interface_Category_Data')) {
    class Npcink_Interface_Category_Data
    {
        public static function run()
        {
            // 提供数据库表格数据
            add_action('wp_ajax_get_all_category_names', array(__CLASS__, 'get_all_category_names'));
        }

        //获取所有的数据库表名
        //名称，ID
        public static function get_all_category_names()
        {
            //获取所有分类
            $categories = get_terms(array(
                'taxonomy' => 'category', // 分类法的名称，默认是'category'
                'hide_empty' => false, // 是否隐藏没有文章的分类
            ));

            $category_array = array();

            foreach ($categories as $category) {
                $category_array[] = array(
                    'label' => $category->name,
                    'value' => $category->term_id,
                );
            }

            //获取所有标签
            $tags = get_terms(array(
                'taxonomy' => 'post_tag', // 标签法的名称，默认是'post_tag'
                'hide_empty' => false, // 是否隐藏没有文章的标签
            ));

            $tag_array = array();

            foreach ($tags as $tag) {
                $tag_array[] = array(
                    'label' => $tag->name,
                    'value' => $tag->term_id,
                );
            }

            // 获取所有页面
            $pages = get_pages();
            $page_array = array();

            foreach ($pages as $page) {
                $page_array[] = array(
                    'label' => $page->post_title,
                    'value' => $page->ID
                );
            }

            // 现在 $tag_array 就包含了所有标签的ID和名称
            $data_array = array(
                'categorys' => $category_array,
                'tags' => $tag_array,
                'pages' => $page_array,
            );

            // 现在 $category_array 就包含了所有分类的ID和名称
            if (empty($category_array)) {
                wp_send_json_error(['error' => '获取分类和标签失败', 'data' => []], 404);
            } else {
                // 返回响应数据
                wp_send_json_success(['data' => $data_array, 'msg' => '成功获取分类和标签数据']);
            }
        }
    }
}
