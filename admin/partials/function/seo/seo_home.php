<?php
defined('ABSPATH') || exit;
//简单SEO - 首页TDK
if (!class_exists('Npcink_Toolbox_Seo_Home')) {
    class Npcink_Toolbox_Seo_Home implements Npcink_Toolbox_Module_Interface
    {
        private static $config;
        public static function run($config = array())
        {
            self::$config = $config;
            //添加首页标题需要先移除默认的，这里还做不到
            add_action('wp', array(__CLASS__, 'add_meta'));
        }

        public static function add_meta()
        {
            if (is_front_page()) {
                //翻页是第一页
                if (get_query_var('paged') < 2) {

                    //首页添加关键词和描述
                    add_action('wp_head', array(__CLASS__, 'add_dk'), 1);

                    //准备选项
                    $option = self::$config;
                    //站点标题
                    $title = Npcink_Toolbox_Admin::get_config($option, 'title');
                    if ($title !== '' && $title !== false) {
                        remove_action('wp_head', '_wp_render_title_tag', 1); //移除默认标题
                    }
                }
            }
        }

        //添加首页关键词和描述
        public static function add_dk()
        {
            //静态或动态首页

            //准备选项
            $option = self::$config;

            //站点标题
            $title = Npcink_Toolbox_Admin::get_config($option, 'title');
            if ($title !== '' && $title !== false) {
                echo '<title>' . esc_html($title) . '</title>';
                echo "\n";
            }

            //站点关键词
            $keywords = Npcink_Toolbox_Admin::get_config($option, 'keywords');
            if ($keywords !== '' && $keywords !== false) {
                echo '<meta name="keywords" content="' . esc_attr($keywords) . '" />';
                echo "\n";
            }

            //站点描述
            $description = Npcink_Toolbox_Admin::get_config($option, 'description');
            if ($description !== '' && $description !== false) {
                echo '<meta name="description" content="' . esc_attr($description) . '" />';
                echo "\n";
            }
        }
    }
}
