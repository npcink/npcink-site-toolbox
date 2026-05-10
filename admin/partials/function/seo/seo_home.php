<?php
//简单SEO - 首页TDK 
if (!class_exists('MaBox_Seo_Home')) {
    class MaBox_Seo_Home
    {
        private static $config;
        public static function run($option)
        {
            self::$config = $option;
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
                    $title = MaBox_Admin::get_config($option, 'title');
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
            $title = MaBox_Admin::get_config($option, 'title');
            if ($title !== '' && $title !== false) {
                echo '<title>' . $title . '</title>';
                echo "\n";
            }

            //站点关键词
            $keywords = MaBox_Admin::get_config($option, 'keywords');
            if ($keywords !== '' && $keywords !== false) {
                echo '<meta name="keywords" content="' . $keywords . '" />';
                echo "\n";
            }

            //站点描述
            $description = MaBox_Admin::get_config($option, 'description');
            if ($description !== '' && $description !== false) {
                echo '<meta name="description" content="' . $description . '" />';
                echo "\n";
            }
        }
    }
}
