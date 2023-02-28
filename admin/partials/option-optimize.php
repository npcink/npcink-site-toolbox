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
            add_action('wp', array(__CLASS__, 'load_run'));

        }
        //准备
        public static function load_run()
        {
            //文章管理添加作者筛选
            if (carbon_get_theme_option('cmma_filter_single_user')) {
                add_action('restrict_manage_posts', array(__CLASS__, 'rudr_filter_by_the_author'));
            };

            //文章管理显示ID
            if (carbon_get_theme_option('cmma_single_show_id')) {
                self::add_single_id_run();
            };

            //文章和媒体添加日期筛选
            if (carbon_get_theme_option('cmma_filter_single_time')) {
                self::filter_time_run();
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
        public static function jqueryui()
        {
            wp_enqueue_style('jquery-ui', '//code.jquery.com/ui/1.11.4/themes/smoothness/jquery-ui.min.css');
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
         * 效果：显示文章ID
         * 来源：https://kinsta.com/blog/wordpress-get-post-id/#2-use-custom-code-to-display-post-ids-in-the-posts-tab
         */
        public static function add_single_id_run()
        {
            //ID 显示在第五行
            add_filter('manage_posts_columns', array(__CLASS__, 'add_column'), 5);
            add_action('manage_posts_custom_column', array(__CLASS__, 'column_content'), 5, 2);
        }
        public static function add_column($columns)
        {
            $columns['post_id_clmn'] = 'ID'; // $columns['Column ID'] = 'Column Title';
            return $columns;
        }

        public static function column_content($column, $id)
        {
            if ($column === 'post_id_clmn') {
                echo $id;
            }

        }

    }
}
