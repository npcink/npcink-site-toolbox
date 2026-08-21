<?php

defined('ABSPATH') || exit;

/**
 * Npcink Site Toolbox小工具
 *
 * 提供多个实用小工具，可在侧边栏或页脚使用。
 */
if (!class_exists('Npcink_Toolbox_Widgets')) {
    class Npcink_Toolbox_Widgets implements Npcink_Toolbox_Module_Interface {

        public static function run($config = array()) {
            add_action('widgets_init', array(__CLASS__, 'register_widgets'));
        }

        public static function register_widgets() {
            register_widget('Npcink_Toolbox_Widget_Site_Stats');
            register_widget('Npcink_Toolbox_Widget_Recent_Posts_With_Thumb');
        }
    }

    /**
     * 站点统计小工具
     */
    class Npcink_Toolbox_Widget_Site_Stats extends WP_Widget {

        public function __construct() {
            parent::__construct(
                'npcink_site_toolbox_site_stats',
                __('Npcink Site Toolbox - 站点统计', 'npcink-site-toolbox'),
                array('description' => __('显示站点文章、评论、用户等统计信息', 'npcink-site-toolbox'))
            );
        }

        public function widget($args, $instance) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted wrapper HTML supplied by the active theme via the Widgets API.
            echo $args['before_widget'];
            if (!empty($instance['title'])) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted title wrappers supplied by the active theme; the widget title itself is escaped.
                echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'];
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Shared renderer escapes labels, values, and class attributes.
            echo Npcink_Toolbox_Site_Stats::render_items(array(), 'mabox-widget-stats');

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted wrapper HTML supplied by the active theme via the Widgets API.
            echo $args['after_widget'];
        }

        public function form($instance) {
            $title = !empty($instance['title']) ? $instance['title'] : __('站点统计', 'npcink-site-toolbox');
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('标题：', 'npcink-site-toolbox'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
            </p>
            <?php
        }

        public function update($new_instance, $old_instance) {
            $instance = array();
            $instance['title'] = sanitize_text_field($new_instance['title']);
            return $instance;
        }
    }

    /**
     * 带缩略图的最新文章小工具
     */
    class Npcink_Toolbox_Widget_Recent_Posts_With_Thumb extends WP_Widget {

        public function __construct() {
            parent::__construct(
                'npcink_site_toolbox_recent_posts_thumb',
                __('Npcink Site Toolbox - 最新文章（带图）', 'npcink-site-toolbox'),
                array('description' => __('显示最新文章列表，带特色图缩略图', 'npcink-site-toolbox'))
            );
        }

        public function widget($args, $instance) {
            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted wrapper HTML supplied by the active theme via the Widgets API.
            echo $args['before_widget'];
            if (!empty($instance['title'])) {
                // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted title wrappers supplied by the active theme; the widget title itself is escaped.
                echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'];
            }

            $number = !empty($instance['number']) ? absint($instance['number']) : 5;
            $posts = get_posts(array(
                'numberposts' => $number,
                'post_status' => 'publish',
                'post_type' => 'post',
            ));

            if ($posts) {
                echo '<ul class="mabox-widget-recent-posts">';
                foreach ($posts as $post) {
                    $thumb = get_the_post_thumbnail_url($post->ID, 'thumbnail');
                    echo '<li class="mabox-recent-post-item">';
                    if ($thumb) {
                        echo '<a href="' . esc_url(get_permalink($post->ID)) . '" class="mabox-recent-thumb" style="background-image:url(' . esc_url($thumb) . ')"></a>';
                    }
                    echo '<a href="' . esc_url(get_permalink($post->ID)) . '" class="mabox-recent-title">' . esc_html($post->post_title) . '</a>';
                    echo '</li>';
                }
                echo '</ul>';
            }

            // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Trusted wrapper HTML supplied by the active theme via the Widgets API.
            echo $args['after_widget'];
        }

        public function form($instance) {
            $title = !empty($instance['title']) ? $instance['title'] : __('最新文章', 'npcink-site-toolbox');
            $number = !empty($instance['number']) ? absint($instance['number']) : 5;
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>"><?php esc_html_e('标题：', 'npcink-site-toolbox'); ?></label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('number')); ?>"><?php esc_html_e('显示数量：', 'npcink-site-toolbox'); ?></label>
                <input class="tiny-text" id="<?php echo esc_attr($this->get_field_id('number')); ?>" name="<?php echo esc_attr($this->get_field_name('number')); ?>" type="number" min="1" max="20" value="<?php echo esc_attr($number); ?>">
            </p>
            <?php
        }

        public function update($new_instance, $old_instance) {
            $instance = array();
            $instance['title'] = sanitize_text_field($new_instance['title']);
            $instance['number'] = absint($new_instance['number']);
            return $instance;
        }
    }
}
