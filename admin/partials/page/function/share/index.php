<?php

/**
 * 功能：添加分享按钮
 * 参考：https://demo.zaxu.com/
 */

if (!class_exists('Npcink_Public_Add_Share')) {
    class Npcink_Public_Add_Share
    {
        //private static $config; //分类数组
        public static function run()
        {
            //self::$config = $option;
            //加载HTML
            add_action('wp_footer', array(__CLASS__, 'add_share_html'));

            //加载公共样式
            add_action('wp_enqueue_scripts', array(__CLASS__, 'public_dist'));
        }

        //添加HTML
        public static function add_share_html()
        {
            echo '<div id="react_public"></div>';
        }

        //添加公共前端资源
        public static function public_dist()
        {
            //判断下，是否在前端页中
            if (is_admin()) {
                return;
            }

            //准备打包后的数据
            $build_css =  plugin_dir_url(dirname(dirname(dirname(dirname(__DIR__))))) . 'vite/public/dist/index.css';
            $build_js =  plugin_dir_url(dirname(dirname(dirname(dirname(__DIR__))))) . 'vite/public/dist/index.js';


            wp_enqueue_style(
                MAGICK_MIXTURE_NAME . '_public_index_css',
                $build_css,
                array(),
                MAGICK_MIXTURE_VERSION,
                'all'
            );
            wp_enqueue_script(
                MAGICK_MIXTURE_NAME . '_public_index_js',
                $build_js,
                array(),
                MAGICK_MIXTURE_VERSION,
                false
            );

            //  //传输数据给JS
            $MaBox_array = self::get_page_meat();

            wp_localize_script(MAGICK_MIXTURE_NAME . '_public_index_js', 'dataLocal', $MaBox_array); //传给vite项目
        }

        //对js文件进行module接入



        /**
         * 准备各种页面的相关信息
         */
        public static function get_page_meat()
        {

            $info = array();

            //文章页、单页
            if (is_single() || is_singular()) {
                // 当前是单篇文章页

                $info = self::get_page_info();
                $info['type'] = "single"; //类型

            }

            if (is_page()) {
                // 当前是单页
                $info = self::get_page_info();
                $info['type'] = "page";
            }
            if (is_category()) {
                // 当前是分类页
                $info = self::get_category_info();
                $info['type'] = "category";
            }
            if (is_tag()) {
                // 当前是标签页
                $info = self::get_tag_info();
                $info['type'] = "tag";
            }

            if (is_home()) {
                // 当前是首页
                $info = self::get_home_info();
                $info['type'] = "home";
            }

            //准备默认图
            $default_image = plugin_dir_url(__FILE__) . 'file-light-1920x1280.jpg';

            $info['image'] = $info['image'] ?: $default_image;



            return $info;
        }

        /**
         * 获取文章页、页面 的相关信息
         */
        public static function get_page_info()
        {
            // 获取页面标题
            $pageTitle = get_the_title();

            // 获取页面描述
            $pageDescription = get_the_excerpt();
            if (empty($pageDescription)) {
                // 如果没有手动设置摘要，则使用文章内容的前几个句子作为描述
                $content = get_the_content();
                $pageDescription = wp_strip_all_tags(wp_trim_words($content, 55)); // 截取前55个单词
            }

            // 获取页面特色图
            if (has_post_thumbnail()) {
                $pageImage = get_the_post_thumbnail_url();
            } else {
                $pageImage = ''; // 如果没有特色图像，可以设置一个默认值
            }

            // 获取页面链接
            $pageUrl = get_permalink();

            $page_info = array();
            $page_info['title'] = $pageTitle;
            $page_info['description'] = $pageDescription;
            $page_info['image'] = $pageImage;
            $page_info['url'] = $pageUrl;
            return $page_info;
        }

        /**
         * 获取分类页相关信息
         */
        public static function get_category_info()
        {
            // 获取当前分类对象
            $currentCategory = get_queried_object();

            // 获取分类标题
            $categoryTitle = $currentCategory->name;

            // 获取分类描述
            $categoryDescription = $currentCategory->description;

            // 获取分类第一个文章的特色图
            $categoryImage = '';
            $category_posts = get_posts(array(
                'cat' => $currentCategory->term_id,
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
            ));

            if (!empty($category_posts)) {
                $category_post = $category_posts[0];
                if (has_post_thumbnail($category_post->ID)) {
                    $categoryImage = get_the_post_thumbnail_url($category_post->ID);
                } else {
                    // 如果文章没有特色图，使用默认特色图
                    $categoryImage = 'URL_TO_DEFAULT_IMAGE'; // 替换为你的默认特色图像的URL
                }
            }

            // 获取分类链接
            $categoryUrl = get_category_link($currentCategory);

            $page_info = array();
            $page_info['title'] = $categoryTitle;
            $page_info['description'] = $categoryDescription;
            $page_info['image'] = $categoryImage;
            $page_info['url'] = $categoryUrl;
            return $page_info;
        }

        /**
         * 获取标签页相关信息
         */
        public static function get_tag_info()
        {
            // 获取当前标签对象
            $currentTag = get_queried_object();

            // 获取标签标题
            $tagTitle = $currentTag->name;

            // 获取标签描述
            $tagDescription = $currentTag->description;

            // 获取标签第一个文章的特色图
            $tagImage = '';
            $tag_posts = get_posts(array(
                'tag_id' => $currentTag->term_id,
                'posts_per_page' => 1,
                'orderby' => 'date',
                'order' => 'DESC',
            ));

            if (!empty($tag_posts)) {
                $tag_post = $tag_posts[0];
                if (has_post_thumbnail($tag_post->ID)) {
                    $tagImage = get_the_post_thumbnail_url($tag_post->ID);
                }
            }

            // 如果没有找到特色图，使用默认特色图
            if (empty($tagImage)) {
                $tagImage = 'URL_TO_DEFAULT_IMAGE_TAG'; // 替换为你的默认特色图像的URL
            }

            // 获取标签链接
            $tagUrl = get_tag_link($currentTag);

            $page_info = array();
            $page_info['title'] = $tagTitle;
            $page_info['description'] = $tagDescription;
            $page_info['image'] = $tagImage;
            $page_info['url'] = $tagUrl;
            return $page_info;
        }

        /**
         * 获取首页相关信息
         */
        public static function get_home_info()
        {
            // 获取站点标题
            $site_title = get_bloginfo('name');

            // 获取站点描述
            $site_description = get_bloginfo('description');

            // 获取页面特色图
            $page_thumbnail = '';

            // 获取首页链接
            $home_url = home_url('/');

            $page_info = array();
            $page_info['title'] = $site_title;
            $page_info['description'] = $site_description;
            $page_info['image'] = $page_thumbnail;
            $page_info['url'] = $home_url;
            return $page_info;
        }
    }
}
