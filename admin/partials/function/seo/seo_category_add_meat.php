<?php

/**
 * 效果：简单SEO - 分类和标签添加输入框
 * 来源：https://www.npc.ink/4596.html
 */
if (!class_exists('Npcink_Seo_Category_Add_Meat')) {
    class Npcink_Seo_Category_Add_Meat
    {
        public static function run()
        {
            //添加分类的关键词
            add_action('category_add_form_fields', array(__CLASS__, 'add_category_field'), 10, 2); // 分类添加字段
            add_action('category_edit_form_fields', array(__CLASS__, 'edit_category_field'), 10, 2); // 分类编辑字段
            add_action('created_category', array(__CLASS__, 'taxonomy_metadate'), 10, 1); // 保存数据
            add_action('edited_category', array(__CLASS__, 'taxonomy_metadate'), 10, 1); // 保存数据
            //添加标签的关键词
        }

        // 分类添加字段
        public static  function add_category_field()
        {
            echo '<div class="form-field">
            <label for="cat-title">分类标题</label>
            <input name="cat-title" id="cat-title" type="text" value="" size="40">
            <p>用于SEO自定义标题</p>
          </div>';

            echo '<div class="form-field">
			<label for="cat-words">分类关键字</label>
            <input name="cat-words" id="cat-words" type="text" value="" size="40">
            <p>用于SEO自定义关键字</p>
          </div>';
        }


        // 分类编辑字段
        public static function edit_category_field($tag)
        {
            echo '<tr class="form-field">
            <th scope="row"><label for="cat-title">分类标题</label></th>
            <td>
                <input name="cat-title" id="cat-title" type="text" value="';
            echo get_option('cat-title-' . $tag->term_id) . '" size="40"/><br>
                <span class="cat-title">用于' . $tag->name . '分类SEO自定义标题</span>
            </td>
        </tr>';

            echo '<tr class="form-field">
            <th scope="row"><label for="cat-words">分类关键字</label></th>
            <td>
                <input name="cat-words" id="cat-words" type="text" value="';
            echo get_option('cat-words-' . $tag->term_id) . '" size="40"/><br>
                <span class="cat-words">用于' . $tag->name . '分类SEO自定义关键字，用英文逗号分隔，如：keyword1,keyword2,keyword3</span>
            </td>
        </tr>';
        }


        // 保存数据
        public static function taxonomy_metadate($term_id)
        {
            if (isset($_POST['cat-title']) && isset($_POST['cat-words'])) {
                //判断权限--可改
                if (!current_user_can('manage_categories')) {
                    return $term_id;
                }
                // 标题
                $title_key = 'cat-title-' . $term_id; // key
                $title_value = sanitize_text_field(wp_unslash($_POST['cat-title'])); // value

                // 关键字
                $words_key = 'cat-words-' . $term_id;
                $words_value = sanitize_text_field(wp_unslash($_POST['cat-words']));

                // 更新选项值
                update_option($title_key, $title_value);
                update_option($words_key, $words_value);
            }
        }
    }
}
