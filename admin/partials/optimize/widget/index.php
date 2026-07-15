<?php
/**
 * 魔法工具箱小工具
 *
 * 提供多个实用小工具，可在侧边栏或页脚使用。
 */
if (!class_exists('MaBox_Widgets')) {
    class MaBox_Widgets implements MaBox_Module_Interface {

        public static function run($config = array()) {
            add_action('widgets_init', array(__CLASS__, 'register_widgets'));
        }

        public static function register_widgets() {
            register_widget('MaBox_Widget_Site_Stats');
            register_widget('MaBox_Widget_Recent_Posts_With_Thumb');
        }
    }

    /**
     * 站点统计小工具
     */
    class MaBox_Widget_Site_Stats extends WP_Widget {

        public function __construct() {
            parent::__construct(
                'mabox_site_stats',
                '魔法工具箱 - 站点统计',
                array('description' => '显示站点文章、评论、用户等统计信息')
            );
        }

        public function widget($args, $instance) {
            echo $args['before_widget'];
            if (!empty($instance['title'])) {
                echo $args['before_title'] . esc_html($instance['title']) . $args['after_title'];
            }

            $post_count = wp_count_posts('post')->publish;
            $comment_count = wp_count_comments()->approved;
            $user_count = count_users();
            $user_total = $user_count['total_users'];
            $category_count = wp_count_terms('category');

            echo '<ul class="mabox-widget-stats">';
            echo '<li><span class="stat-label">文章</span><span class="stat-value">' . esc_html($post_count) . '</span></li>';
            echo '<li><span class="stat-label">评论</span><span class="stat-value">' . esc_html($comment_count) . '</span></li>';
            echo '<li><span class="stat-label">分类</span><span class="stat-value">' . esc_html($category_count) . '</span></li>';
            echo '<li><span class="stat-label">用户</span><span class="stat-value">' . esc_html($user_total) . '</span></li>';
            echo '</ul>';

            echo $args['after_widget'];
        }

        public function form($instance) {
            $title = !empty($instance['title']) ? $instance['title'] : '站点统计';
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">标题：</label>
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
    class MaBox_Widget_Recent_Posts_With_Thumb extends WP_Widget {

        public function __construct() {
            parent::__construct(
                'mabox_recent_posts_thumb',
                '魔法工具箱 - 最新文章（带图）',
                array('description' => '显示最新文章列表，带特色图缩略图')
            );
        }

        public function widget($args, $instance) {
            echo $args['before_widget'];
            if (!empty($instance['title'])) {
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

            echo $args['after_widget'];
        }

        public function form($instance) {
            $title = !empty($instance['title']) ? $instance['title'] : '最新文章';
            $number = !empty($instance['number']) ? absint($instance['number']) : 5;
            ?>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('title')); ?>">标题：</label>
                <input class="widefat" id="<?php echo esc_attr($this->get_field_id('title')); ?>" name="<?php echo esc_attr($this->get_field_name('title')); ?>" type="text" value="<?php echo esc_attr($title); ?>">
            </p>
            <p>
                <label for="<?php echo esc_attr($this->get_field_id('number')); ?>">显示数量：</label>
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
