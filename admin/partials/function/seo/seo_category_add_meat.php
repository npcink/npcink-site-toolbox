<?php
defined('ABSPATH') || exit;

/**
 * 效果：简单SEO - 分类和标签添加输入框
 * 来源：https://www.npc.ink/4596.html
 */
if (!class_exists('Npcink_Toolbox_Seo_Category_Add_Meat')) {
    class Npcink_Toolbox_Seo_Category_Add_Meat implements Npcink_Toolbox_Module_Interface
    {
        public static function run($config = array())
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
            ?>
            <div class="form-field">
            <label for="cat-title"><?php esc_html_e('分类标题', 'npcink-site-toolbox'); ?></label>
            <input name="cat-title" id="cat-title" type="text" value="" size="40">
            <p><?php esc_html_e('用于 SEO 自定义标题', 'npcink-site-toolbox'); ?></p>
          </div>

            <div class="form-field">
			<label for="cat-words"><?php esc_html_e('分类关键字', 'npcink-site-toolbox'); ?></label>
            <input name="cat-words" id="cat-words" type="text" value="" size="40">
            <p><?php esc_html_e('用于 SEO 自定义关键字', 'npcink-site-toolbox'); ?></p>
          </div>
            <?php
        }


        // 分类编辑字段
        public static function edit_category_field($tag)
        {
            ?>
            <tr class="form-field">
            <th scope="row"><label for="cat-title"><?php esc_html_e('分类标题', 'npcink-site-toolbox'); ?></label></th>
            <td>
                <input name="cat-title" id="cat-title" type="text" value="<?php echo esc_attr(get_option('npcink_site_toolbox_category_title_' . $tag->term_id)); ?>" size="40"/><br>
                <span class="cat-title">
                    <?php
                    printf(
                        /* translators: %s: Category name. */
                        esc_html__('用于 %s 分类的 SEO 自定义标题', 'npcink-site-toolbox'),
                        esc_html($tag->name)
                    );
                    ?>
                </span>
            </td>
        </tr>

            <tr class="form-field">
            <th scope="row"><label for="cat-words"><?php esc_html_e('分类关键字', 'npcink-site-toolbox'); ?></label></th>
            <td>
                <input name="cat-words" id="cat-words" type="text" value="<?php echo esc_attr(get_option('npcink_site_toolbox_category_keywords_' . $tag->term_id)); ?>" size="40"/><br>
                <span class="cat-words">
                    <?php
                    printf(
                        /* translators: %s: Category name. */
                        esc_html__('用于 %s 分类的 SEO 自定义关键字，用英文逗号分隔，如：keyword1,keyword2,keyword3', 'npcink-site-toolbox'),
                        esc_html($tag->name)
                    );
                    ?>
                </span>
            </td>
        </tr>
            <?php
        }


        // 保存数据
        public static function taxonomy_metadate($term_id)
        {
            if (!current_user_can('manage_categories')) {
                return $term_id;
            }

            $action = isset($_POST['action']) && is_string($_POST['action'])
                ? sanitize_key(wp_unslash($_POST['action']))
                : '';
            $nonce_valid = false;

            if ('add-tag' === $action && isset($_POST['_wpnonce_add-tag']) && is_string($_POST['_wpnonce_add-tag'])) {
                $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce_add-tag']));
                $nonce_valid = wp_verify_nonce($nonce, 'add-tag') !== false;
            } elseif ('editedtag' === $action && isset($_POST['_wpnonce']) && is_string($_POST['_wpnonce'])) {
                $nonce = sanitize_text_field(wp_unslash($_POST['_wpnonce']));
                $nonce_valid = wp_verify_nonce($nonce, 'update-tag_' . absint($term_id)) !== false;
            }

            if (
                !$nonce_valid
                || !isset($_POST['cat-title'], $_POST['cat-words'])
                || !is_string($_POST['cat-title'])
                || !is_string($_POST['cat-words'])
            ) {
                return $term_id;
            }

            $title_key = 'npcink_site_toolbox_category_title_' . absint($term_id);
            $title_value = sanitize_text_field(wp_unslash($_POST['cat-title']));
            $words_key = 'npcink_site_toolbox_category_keywords_' . absint($term_id);
            $words_value = sanitize_text_field(wp_unslash($_POST['cat-words']));

            update_option($title_key, $title_value);
            update_option($words_key, $words_value);

            return $term_id;
        }
    }
}
