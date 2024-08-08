<?php

/**
 * 专题页面模版
 * 介绍：页面中输入搜索的文本，页面会搜索保护该标题的文本，列成列表
 */
if (!class_exists('Npcink_Template_Special')) {
    class Npcink_Template_Special
    {
        public static function run()
        {
            //添加meta box
            add_action('add_meta_boxes', array(__CLASS__, 'custom_theme_options_metabox'));
            add_action('save_post', array(__CLASS__, 'save_custom_fields'));
        }

        public static function custom_theme_options_metabox()
        {
            global $post;

            // 获取当前页面的模板名称
            $page_template = get_post_meta($post->ID, '_wp_page_template', true);

            // 如果页面模板是你希望显示Meta Box的模板，这里假设模板名称为 'custom-template.php'
            if ($page_template == 'template-one.php') {
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
            $custom_field_1_value = get_post_meta($post->ID, 'custom_field_1', true);
?>

            <label for="custom-field-1">沉浸阅读网址：</label>
            <input type="text" name="custom_field_1" id="custom-field-1" value="<?php echo esc_attr($custom_field_1_value); ?>">
            <p>根据填入的关键词搜索标题，将包含此关键词的标题列表</p>

<?php
        }

        public static function save_custom_fields($post_id)
        {
            if (isset($_POST['custom_field_1'])) {
                update_post_meta($post_id, 'custom_field_1', sanitize_text_field($_POST['custom_field_1']));
            }
        }
    }
}
