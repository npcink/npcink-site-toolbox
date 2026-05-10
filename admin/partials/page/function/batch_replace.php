<?php
/**
 * 文章批量替换
 * 保存文章时自动替换指定内容，也支持手动触发
 */
if (!class_exists('MaBox_Page_Batch_Replace')) {
    class MaBox_Page_Batch_Replace
    {
        private static $option;

        public static function run($config)
        {
            self::$option = $config;
            add_filter('content_save_pre', array(__CLASS__, 'replace_on_save'), 10, 1);
            add_action('wp_ajax_mabox_batch_replace', array(__CLASS__, 'manual_replace_deprecated'));
        }

        public static function replace_on_save($content)
        {
            $pairs = MaBox_Admin::get_config(self::$option, 'batch_replace_pairs', array());
            if (empty($pairs)) {
                return $content;
            }

            foreach ($pairs as $pair) {
                if (!empty($pair['find']) && isset($pair['replace'])) {
                    $content = str_replace($pair['find'], $pair['replace'], $content);
                }
            }

            return $content;
        }

        public static function manual_replace_deprecated() {
            _deprecated_function('wp_ajax_mabox_batch_replace', '2.1.0', 'REST API POST /mabox/v1/page/batch-replace');
            self::manual_replace();
        }

        public static function manual_replace()
        {
            if (!current_user_can('edit_posts')) {
                wp_send_json_error('权限不足');
            }

            $pairs = MaBox_Admin::get_config(self::$option, 'batch_replace_pairs', array());
            if (empty($pairs)) {
                wp_send_json_error('没有设置替换规则');
            }

            $args = array(
                'post_type' => 'post',
                'post_status' => 'publish',
                'posts_per_page' => -1,
            );
            $query = new WP_Query($args);
            $count = 0;

            foreach ($query->posts as $post) {
                $new_content = $post->post_content;
                foreach ($pairs as $pair) {
                    if (!empty($pair['find']) && isset($pair['replace'])) {
                        $new_content = str_replace($pair['find'], $pair['replace'], $new_content);
                    }
                }

                if ($new_content !== $post->post_content) {
                    wp_update_post(array(
                        'ID' => $post->ID,
                        'post_content' => $new_content,
                    ));
                    $count++;
                }
            }

            wp_send_json_success('成功替换 ' . $count . ' 篇文章的内容');
        }
    }
}
