<?php

/**
 * 优化选项
 */
if (!class_exists('Magick_Mixtrue_Optimize')) {
    class Magick_Mixtrue_Optimize
    {


        //加载
        public static function run()
        {
            add_action('init', array(__CLASS__, 'load'));
        }
        //准备
        public static function load()
        {
            /**
             * 优化 - 站点
             */
            require_once plugin_dir_path(__FILE__) . 'optimize/site.php';
            Mami_Optimize_Site::run();






            //文章管理添加作者筛选
            if (carbon_get_theme_option('cmma_filter_single_user')) {
                add_action('restrict_manage_posts', array(__CLASS__, 'rudr_filter_by_the_author'));
            };

            //各个列表显示ID
            if (carbon_get_theme_option('cmma_single_show_id')) {
                add_action('admin_init', array(__CLASS__, 'add_list_id_run'));
            };

            //文章和媒体添加日期筛选
            if (carbon_get_theme_option('cmma_filter_single_time')) {
                self::filter_time_run();
            }

            //评论时间间隔
            if (carbon_get_theme_option('cmma_opt_com_time') === "yes") {
                add_filter('comment_flood_filter', array(__CLASS__, 'suren_comment_flood_filter'), 10, 3);
            }

            //评论最少和最多字数
            if (carbon_get_theme_option('cmma_opt_com_number') === "yes") {
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


            /**
             * 媒体
             */
            //自动给图片添加Alt标签
            if (carbon_get_theme_option('cmma_medium_img_add_alt')) {
                add_filter('the_content', array(__CLASS__, 'image_alt_tag'), 99999);
            }
            // 禁用自动生成的图片尺寸
            if (carbon_get_theme_option('cmma_medium_ban_auto_size')) {
                self::run_ban_auto_size();
            }

            //添加媒体库 SVG 图标支持
            if (carbon_get_theme_option('cmma_medium_add_svg')) {
                self::run_add_svg();
            }

            //媒体文件重命名
            switch (carbon_get_theme_option('cmma_opt_medium_rename')) {
                    //时间
                case 'math':
                    add_filter('wp_handle_upload_prefilter', array(__CLASS__, 'custom_upload_filter_time'));
                    break;
                    //md5重命名
                case 'md5':
                    add_filter('wp_handle_upload_prefilter', array(__CLASS__, 'custom_upload_filter_md5'));
                    break;
                    //默认值
                default:
                    return;
            }

            /**
             * 禁用
             */

            //禁用更新
            if (carbon_get_theme_option('cmma_opt_ban_update')) {
                self::run_ban_update();
            }



            //未登录模糊文章内图片
            if (carbon_get_theme_option('cmma_control_login_dim_content_img')) {
                //判断，没有登录
                if (!is_user_logged_in()) {
                    add_action('wp_footer', array(__CLASS__, 'n_yingcang_css'));
                }
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
                #ssid {
                    width: 50px;
                }

                /* Simply Show IDs */
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



        /**
         * 优化 - 媒体
         */
        //自动给图片添加Alt标签
        public static function image_alt_tag($content)
        {
            global $post;
            preg_match_all('/<img (.*?)\/>/', $content, $images);
            if (!is_null($images)) {
                foreach ($images[1] as $index => $value) {
                    $new_img = str_replace('<img', '<img alt="' . get_the_title() . '-' . get_bloginfo('name') . '"', $images[0][$index]);
                    $content = str_replace($images[0][$index], $new_img, $content);
                }
            }
            return $content;
        }

        // 禁用自动生成的图片尺寸
        public static function run_ban_auto_size()
        {

            // 禁用自动生成的图片尺寸
            add_action('intermediate_image_sizes_advanced', array(__CLASS__, 'shapeSpace_disable_image_sizes'));
            // 禁用缩放尺寸
            add_filter('big_image_size_threshold', '__return_false');
            // 禁用其他图片尺寸
            add_action('init', array(__CLASS__, 'shapeSpace_disable_other_image_sizes'));
        }
        public static function shapeSpace_disable_image_sizes($sizes)
        {
            unset($sizes['thumbnail']); // disable thumbnail size
            unset($sizes['medium']); // disable medium size
            unset($sizes['large']); // disable large size
            unset($sizes['medium_large']); // disable medium-large size
            unset($sizes['1536x1536']); // disable 2x medium-large size
            unset($sizes['2048x2048']); // disable 2x large size return $sizes;
        }

        public static function shapeSpace_disable_other_image_sizes()
        {
            remove_image_size('post-thumbnail');
            // 禁用通过 set_post_thumbnail_size()  添加的图像
            remove_image_size('another-size');
            // 禁用任何其他添加的图像大小
        }

        //添加媒体库 SVG 图标支持
        public static function run_add_svg()
        {
            add_filter('upload_mimes', array(__CLASS__, 'salong_mime_types'));
            add_action('admin_head', array(__CLASS__, 'salong_admin_svg_css'));
        }

        //添加媒体库 SVG 图标支持
        public static function salong_mime_types($mimes)
        {
            $mimes['svg'] = 'image/svg+xml';
            return $mimes;
        }

        //在媒体库显示 SVG 图标
        public static function salong_admin_svg_css()
        {
            echo "
            <style>
            table.media .column-title .media-icon img[src*='.svg']{
             width: 100%;
             height: auto;
                    }
        </style>";
        }

        /**
         * 重命名
         */

        /*图片按时间自动重命名*/
        public static function custom_upload_filter_time($file)
        {
            $info = pathinfo($file['name']);
            $ext = $info['extension'];
            $filedate = date('YmdHis') . rand(10, 99); //为了避免时间重复，再加一段2位的随机数
            $file['name'] = $filedate . '.' . $ext;
            return $file;
        }

        /*使用md5转码重命名媒体文件名*/
        public static function custom_upload_filter_md5($file)
        {
            $info = pathinfo($file['name']);
            $ext = '.' . $info['extension'];
            $md5 = md5($file['name']);
            $file['name'] = $md5 . $ext;
            return $file;
        }

        /**
         * 禁用
         */

        /**
         * 效果：禁用更新
         * 来源：https://www.npc.ink/15932.html
         */
        public static function run_ban_update()
        {
            remove_action('init', 'wp_schedule_update_checks'); // 关闭更新检查定时作业
            wp_clear_scheduled_hook('wp_version_check'); // 移除已有的版本检查定时作业
            wp_clear_scheduled_hook('wp_update_plugins'); // 移除已有的插件更新定时作业
            wp_clear_scheduled_hook('wp_update_themes'); // 移除已有的主题更新定时作业
            wp_clear_scheduled_hook('wp_maybe_auto_update'); // 移除已有的自动更新定时作业
            add_filter('automatic_updater_disabled', '__return_true'); // 彻底关闭自动更新
            remove_action('admin_init', '_maybe_update_core'); // 移除后台内核更新检查
            remove_action('load-plugins.php', 'wp_update_plugins'); // 移除后台插件更新检查
            remove_action('load-update.php', 'wp_update_plugins');
            remove_action('load-update-core.php', 'wp_update_plugins');
            remove_action('admin_init', '_maybe_update_plugins');
            remove_action('load-themes.php', 'wp_update_themes'); // 移除后台主题更新检查
            remove_action('load-update.php', 'wp_update_themes');
            remove_action('load-update-core.php', 'wp_update_themes');
            remove_action('admin_init', '_maybe_update_themes');
        }



        /**
         * 未登录模糊文章内图片
         */
        public static function n_yingcang_css()
        {
            echo '<style>

    /*仅模糊文章内图片*/
    .entry-content img {
    -webkit-filter: blur(10px)!important;
      -moz-filter: blur(10px)!important;
      -ms-filter: blur(10px)!important;
      filter: blur(6px)!important;}
      .entry-content img:before{
        content:"登录可见";
      }
      </style>';
        }
    }
}
