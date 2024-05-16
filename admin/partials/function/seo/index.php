<?php
//简单SEO
/**
 * 效果：
 * TODO: 检查，是否已存在相关标签，存在的则不添加
 * TODO: 分类、标签、文章、页面、等，添加TDK
 */
if (!class_exists('Npcink_Easy_Seo')) {
    class Npcink_Easy_Seo
    {
        private static $config;
        public static function run($option)
        {
            self::$config = $option;
            add_action('wp', array(__CLASS__, 'add_meta_home'));
        }

        public static function add_meta_home()
        {

            //静态或动态首页
            if (is_front_page()) {
                //翻页是第一页
                if (get_query_var('paged') < 2) {

                    //判断，是否已存在目标meta
                    // 在 wp_head 钩子中执行自定义函数
                    add_action('wp_head', array(__CLASS__, 'capture_and_output_head_content'));

                    //准备选项
                    $option = self::$config;
                    //站点标题
                    $title = MaBox_Admin::get_config($option, 'title');
                    if ($title !== '' && $title !== false) {
                        require_once plugin_dir_path(__FILE__) . 'site_title.php'; //载入文件
                        Npcink_Seo_Site_Title::run($title);
                    }

                    //站点描述
                    $description = MaBox_Admin::get_config($option, 'description');
                    if ($description !== '' && $description !== false) {
                        require_once plugin_dir_path(__FILE__) . 'site_description.php'; //载入文件
                        Npcink_Seo_Site_Description::run($description);
                    }

                    //站点关键词
                    $keywords = MaBox_Admin::get_config($option, 'keywords');
                    if ($keywords !== '' && $keywords !== false) {
                        require_once plugin_dir_path(__FILE__) . 'site_keywords.php'; //载入文件
                        Npcink_Seo_Site_Keywords::run($keywords);
                    }
                }
            }
        }

        // 添加一个标志变量来确保代码只运行一次
        private static  $head_content_captured = false;
        public static function capture_and_output_head_content()
        {

            // 检查标志变量，确保代码只运行一次
            if (self::$head_content_captured) {
                return;
            }

            // 设置标志变量为 true，表示代码即将运行
            self::$head_content_captured = true;

            ob_start(); // 开始输出缓存
            do_action('wp_head');
            $head_content = ob_get_clean(); // 获取缓存内容并清空缓存

            // 在这里可以对 $head_content 进行进一步处理
            $default_value = strpos($head_content, '<meta name="description"') !== false;
            printf('<script>console.log(%s)</script>', json_encode($default_value));
        }
        /*
          <meta name='description' content='SEO 描述' />
          <meta name='keywords' content='1,2222,3，5' />
          elseif(is_tag() || is_category() || is_tax()){
			if(get_query_var('paged') < 2){
				if(self::get_setting('individual')){
					$value	= get_term_meta(get_queried_object_id(), 'seo_'.$type, true);
				}

				if(empty($value) && $type == 'description'){
					$value	= term_description();
				}
			}
         */
    }
}
