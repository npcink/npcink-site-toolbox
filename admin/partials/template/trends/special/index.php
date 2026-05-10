<?php

/**
 * 专题页面模版
 * 介绍：页面中输入搜索的文本，页面会搜索保护该标题的文本，列成列表
 */
if (!class_exists('MaBox_Template_Special')) {
    class MaBox_Template_Special
    {
        public static function run()
        {
            //添加meta box
            add_action('add_meta_boxes', array(__CLASS__, 'custom_theme_options_metabox'));
            add_action('save_post', array(__CLASS__, 'save_custom_fields'));

            //加载样式
            add_action('wp_enqueue_scripts', array(__CLASS__, 'styles'));
        }

        public static function custom_theme_options_metabox()
        {
            global $post;

            // 获取当前页面的模板名称
            $page_template = get_post_meta($post->ID, '_wp_page_template', true);

            // 如果页面模板是你希望显示Meta Box的模板，这里假设模板名称为 'custom-template.php'
            if ($page_template == 'template-special.php') {
                add_meta_box(
                    'custom_fields', // 自定义字段框的 ID
                    '专题模版', // 自定义字段框的标题
                    array(__CLASS__, 'render_custom_fields'), // 渲染自定义字段的回调函数
                    'page', // 只在页面中显示
                    'normal', // 显示在默认位置
                    'high' // 设置优先级为高
                );
            }
        }

        // 渲染自定义字段的回调函数
        public static function render_custom_fields($post)
        {
            // 获取存储在自定义字段中的值
            $value = get_post_meta($post->ID, 'mabox_trends_special', true);
?>

            <label for="trends-special">标题关键词：</label>
            <input type="text" name="special_data" id="trends-special" value="<?php echo esc_attr($value); ?>">
            <p>根据填入的关键词搜索标题，将包含此关键词的标题列表</p>

<?php
        }

        public static function save_custom_fields($post_id)
        {
            if (isset($_POST['special_data'])) {
                update_post_meta($post_id, 'mabox_trends_special', sanitize_text_field($_POST['special_data']));
            }
        }

        //加载样式
        public static function styles()
        {
            $style_css =  plugin_dir_url(__DIR__) . 'special/style.css';
            // 如果当前页面模板是 template-aaa.php，则加载特定的 CSS 文件
            if (is_page_template('template-special.php')) {
                wp_enqueue_style(MAGICK_MIXTURE_NAME . '_special-style', $style_css, array(), MAGICK_MIXTURE_VERSION, 'all');
            }
        }
    }
}
