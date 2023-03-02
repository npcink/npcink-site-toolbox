<?php

/**
 * 优化选项
 */
if (!class_exists('Magick_Mixtrue_Optimize')) {
    class Magick_Mixtrue_Optimize
    {

        public function __construct()
        {

        }

        //加载
        public static function run()
        {
            add_action('init', array(__CLASS__, 'load_run'));

        }
        //准备
        public static function load_run()
        {
            //文章管理添加作者筛选
            if (carbon_get_theme_option('cmma_filter_single_user')) {
                add_action('restrict_manage_posts', array(__CLASS__, 'rudr_filter_by_the_author'));
            };

            //各个列表显示ID
            if (carbon_get_theme_option('cmma_single_show_id')) {
                //self::add_list_id_run();
                add_action('admin_init', array(__CLASS__, 'add_list_id_run'));

            };

            //文章和媒体添加日期筛选
            if (carbon_get_theme_option('cmma_filter_single_time')) {
                self::filter_time_run();
            }

            //评论时间间隔
            if (carbon_get_theme_option('cmma_opt_com_time')) {
                add_filter('comment_flood_filter', array(__CLASS__, 'suren_comment_flood_filter'), 10, 3);
            }

            //评论最少和最多字数
            if (carbon_get_theme_option('cmma_opt_com_number')) {
                add_filter('preprocess_comment', array(__CLASS__, 'set_comments_length'), 10, 3);
            }

            //禁止纯英文或纯日文评论
            if (carbon_get_theme_option('cmma_opt_com_language')) {
                add_filter('preprocess_comment', array(__CLASS__, 'refused_english_comments'));
            }

            //一篇文章只能评论一次
            if (carbon_get_theme_option('cmma_opt_com_once')) {
                add_action('preprocess_comment', array(__CLASS__, 'ludou_only_one_comment'), 20);
            }

            //登录页LOGO改为首页链接
            if (carbon_get_theme_option('cmma_opt_com_logo_home')) {
                add_filter('login_headerurl', array(__CLASS__, 'admin_logo_home'));
            }

            //移除登录页语言选择器
            //https://www.iowen.cn/yichuwordpress59dengluyemianzhongdeyuyanqiehuankuang/
            if (carbon_get_theme_option('cmma_opt_rem_sign_lang')) {
                add_filter('login_display_language_dropdown', '__return_false');
            }

        }
        /**
         * 优化 - 筛选
         */

        /**
         * 用途;后台文章管理中添加作者过滤器
         * 来源：https://rudrastyh.com/wordpress/filter-posts-by-author.html
         */
        public static function rudr_filter_by_the_author($post_type)
        {

            // 可以为特定的帖子类型添加条件
            // if( 'my_type' !== $post_type ) {
            //     return;
            // }

            $selected = isset($_GET['user']) && $_GET['user'] ? $_GET['user'] : '';

            wp_dropdown_users(
                array(
                    'role__in' => array(
                        'administrator',
                        'editor',
                        'author',
                        'contributor',
                    ),
                    'name' => 'author',
                    'show_option_all' => '全部作者',
                    'selected' => $selected,
                )
            );

        }

        /**
         * 优化 - 按日期筛选媒体和图片
         * 来源：https://rudrastyh.com/wordpress/date-range-filter.html
         */
        public static function filter_time_run()
        {
            // 如果不想删除默认的“按月筛选”，请删除/注释此行
            add_filter('months_dropdown_results', '__return_empty_array');

            // 包括CSS/JS，在我们的例子中是jQuery UI日期选择器
            add_action('admin_enqueue_scripts', array(__CLASS__, 'jqueryui'));

            // 过滤器的HTML
            add_action('restrict_manage_posts', array(__CLASS__, 'form'));

            // 过滤帖子的函数
            add_action('pre_get_posts', array(__CLASS__, 'filterquery'));
        }
        /*
         * 添加jQuery UI CSS和日期选择器脚本
         *其他所有内容都应该包含在/wp-admin/中，如jquery、jquery ui核心等
         *如果您使用WooCommerce，您可以完全跳过此功能
         */
        public static function jqueryui($hook)
        {
            if (('upload.php' != $hook) && ('edit.php' != $hook)) {
                return;
            }

            //http://code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.min.css
            wp_enqueue_style('jquery-ui', plugin_dir_url(\dirname(__FILE__)) . 'css/jquery-ui.min.css');
            wp_enqueue_script('jquery-ui-datepicker');
        }

        /*
         * 带有CSS/JS的两个输入字段
         *如果您想将CSS和JavaScript移动到外部文件-欢迎。
         */
        public static function form()
        {

            $from = (isset($_GET['mishaDateFrom']) && $_GET['mishaDateFrom']) ? $_GET['mishaDateFrom'] : '';
            $to = (isset($_GET['mishaDateTo']) && $_GET['mishaDateTo']) ? $_GET['mishaDateTo'] : '';

            echo '<style>
		input[name="mishaDateFrom"], input[name="mishaDateTo"]{
			line-height: 28px;
			height: 28px;
			margin: 0;
			width:125px;
		}
		</style>

		<input type="text" name="mishaDateFrom" placeholder="开始于" value="' . esc_attr($from) . '" />
		<input type="text" name="mishaDateTo" placeholder="结束于" value="' . esc_attr($to) . '" />

		<script>
		jQuery( function($) {
			var from = $(\'input[name="mishaDateFrom"]\'),
			    to = $(\'input[name="mishaDateTo"]\');

			$( \'input[name="mishaDateFrom"], input[name="mishaDateTo"]\' ).datepicker( {dateFormat : "yy-mm-dd"} );
			// by default, the dates look like this "April 3, 2017"
    			// I decided to make it 2017-04-03 with this parameter datepicker({dateFormat : "yy-mm-dd"});


    			// the rest part of the script prevents from choosing incorrect date interval
    			from.on( \'change\', function() {
				to.datepicker( \'option\', \'minDate\', from.val() );
			});

			to.on( \'change\', function() {
				from.datepicker( \'option\', \'maxDate\', to.val() );
			});

		});
		</script>';

        }

        /*
         * 实际过滤帖子的主要功能
         */
        public static function filterquery($admin_query)
        {
            global $pagenow;

            if (
                is_admin()
                && $admin_query->is_main_query()
                // 默认情况下，过滤器将被添加到所有post类型中，您可以使用$_GET['post_type']来限制某些类型的过滤器
                 && in_array($pagenow, array('edit.php', 'upload.php'))
                && (!empty($_GET['mishaDateFrom']) || !empty($_GET['mishaDateTo']))
            ) {

                $admin_query->set(
                    'date_query', //我喜欢WordPress 3.7中出现的日期查询！
                    array(
                        'after' => sanitize_text_field($_GET['mishaDateFrom']), // any strtotime()-acceptable format!
                        'before' => sanitize_text_field($_GET['mishaDateTo']),
                        'inclusive' => true, // 还包括选定的日期
                        'column' => 'post_date', // 'post_modified', 'post_date_gmt', 'post_modified_gmt'
                    )
                );

            }

            return $admin_query;

        }

        /**
         * 优化-显示
         */
        /**
         * 效果：各个列表显示ID
         * 来源：https://blog.csdn.net/qq_39339179/article/details/119135050
         */
        public static function add_list_id_run()
        {
            //ID 显示在第10行

            //添加样式
            add_action('admin_head', array(__CLASS__, 'ssid_css'));

            add_filter('manage_posts_columns', array(__CLASS__, 'ssid_column'));
            add_action('manage_posts_custom_column', array(__CLASS__, 'ssid_value'), 10, 2);

            add_filter('manage_pages_columns', array(__CLASS__, 'ssid_column'));
            add_action('manage_pages_custom_column', array(__CLASS__, 'ssid_value'), 10, 2);

            add_filter('manage_media_columns', array(__CLASS__, 'ssid_column'));
            add_action('manage_media_custom_column', array(__CLASS__, 'ssid_value'), 10, 2);

            add_filter('manage_link-manager_columns', array(__CLASS__, 'ssid_column'));
            add_action('manage_link_custom_column', array(__CLASS__, 'ssid_value'), 10, 2);

            add_action('manage_edit-link-categories_columns', array(__CLASS__, 'ssid_column'));
            add_filter('manage_link_categories_custom_column', array(__CLASS__, 'ssid_return_value'), 10, 3);

            foreach (get_taxonomies() as $taxonomy) {
                add_action("manage_edit-${taxonomy}_columns", array(__CLASS__, 'ssid_column'));
                add_filter("manage_${taxonomy}_custom_column", array(__CLASS__, 'ssid_return_value'), 10, 3);
            }

            add_action('manage_users_columns', array(__CLASS__, 'ssid_column'));
            add_filter('manage_users_custom_column', array(__CLASS__, 'ssid_return_value'), 10, 3);

            add_action('manage_edit-comments_columns', array(__CLASS__, 'ssid_column'));
            add_action('manage_comments_custom_column', array(__CLASS__, 'ssid_value'), 10, 2);
        }

        public static function ssid_column($cols)
        {
            $cols['ssid'] = 'ID';
            return $cols;
        }

// 显示 ID
        public static function ssid_value($column_name, $id)
        {
            if ($column_name == 'ssid') {
                echo $id;
            }

        }

        public static function ssid_return_value($value, $column_name, $id)
        {
            if ($column_name == 'ssid') {
                $value = $id;
            }

            return $value;
        }

// 为 ID 这列添加css
        public static function ssid_css()
        {
            ?>
                <style type="text/css">
                	#ssid { width: 50px; } /* Simply Show IDs */
                </style>
         <?php
}

        /**
         * 优化-评论
         */

        /**
         * 效果：两次评论之间间隔
         * 来源：https://www.npc.ink/19960.html
         */
        public static function suren_comment_flood_filter($flood_control, $time_last, $time_new)
        {
            $seconds = carbon_get_theme_option('cmma_opt_com_times'); //间隔时间
            if (($time_new - $time_last) < $seconds) {
                $time = $seconds - ($time_new - $time_last);
                wp_die('评论过快！请' . $time . '秒后再来评论');
            } else {
                return false;
            }
        }

        /**
         * 效果：评论所需的最少和最多字数
         * 来源：https://www.npc.ink/17995.html
         */
        public static function set_comments_length($commentdata)
        {
            $minCommentlength = carbon_get_theme_option('cmma_opt_com_num_min'); //最少字數限制
            $maxCommentlength = carbon_get_theme_option('cmma_opt_com_num_max'); //最多字數限制
            $pointCommentlength = mb_strlen($commentdata['comment_content'], 'UTF8'); //mb_strlen 1個中文字符當作1個長度
            if ($pointCommentlength < $minCommentlength) {
                header("Content-type: text/html; charset=utf-8");
                wp_die('抱歉，您的评论字数过少，请至少输入' . $minCommentlength . '个字（目前字数：' . $pointCommentlength . '个字）');
                exit;
            }
            if ($pointCommentlength > $maxCommentlength) {
                header("Content-type: text/html; charset=utf-8");
                wp_die('对不起，您的评论字数过多，请少于' . $maxCommentlength . '个字（目前字数：' . $pointCommentlength . '个字）');
                exit;
            }
            return $commentdata;
        }

        /* 作用：禁止纯英文、纯日文评论
         * 来源：https://www.npc.ink/18129.html
         * */
        public static function refused_english_comments($incoming_comment)
        {
            $pattern = '/[一-龥]/u';
            // 禁止全英文评论
            if (!preg_match($pattern, $incoming_comment['comment_content'])) {
                wp_die("您的评论中必须包含汉字!");
            }
            $pattern = '/[あ-んア-ン]/u';
            // 禁止日文评论
            if (preg_match($pattern, $incoming_comment['comment_content'])) {
                wp_die("评论禁止包含日文!");
            }
            return ($incoming_comment);
        }

        /* 作用：一篇文章只能评论一次，管理员不受影响
         * 来源：https://www.npc.ink/13477.html
         * */
        // 获取评论用户的ip，参考wp-includes/comment.php
        public static function ludou_getIP()
        {
            $ip = $_SERVER['REMOTE_ADDR'];
            $ip = preg_replace('/[^0-9a-fA-F:., ]/', '', $ip);

            return $ip;
        }
        public static function ludou_only_one_comment($commentdata)
        {
            global $wpdb;
            $currentUser = wp_get_current_user();

            // 不限制管理员发表评论
            if (empty($currentUser->roles) || !in_array('administrator', $currentUser->roles)) {
                $bool = $wpdb->get_var("SELECT comment_ID FROM $wpdb->comments WHERE comment_post_ID = " . $commentdata['comment_post_ID'] . "  AND (comment_author = '" . $commentdata['comment_author'] . "' OR comment_author_email = '" . $commentdata['comment_author_email'] . "' OR comment_author_IP = '" . self::ludou_getIP() . "') LIMIT 0, 1;");

                if ($bool) {
                    wp_die('本站每篇文章只允许评论一次。<a href="' . get_permalink($commentdata['comment_post_ID']) . '">点此返回</a>');
                }

            }

            return $commentdata;
        }

        /* 作用：登录页LOGO改为首页链接
         * 来源：https://www.iowen.cn/chundaimameihuawordpressmorendengluye/
         * */
        public static function admin_logo_home()
        {
            return esc_url(home_url());
        }

    }
}
