<?php
//优化 其他
if (!class_exists('MaMi_Optimize_Admin')) {
    class MaMi_Optimize_Admin
    {
        //加载
        public static function run($config)
        {
            //获取选项
            $option =  MaMi_Admin::get_config($config, 'other');

            //文章管理添加作者筛选
            $add_user = MaMi_Admin::get_config($option, 'add_user');
            if ($add_user === true) {
                require_once plugin_dir_path(__FILE__) . 'single_add_user_screen.php';
                Npcink_Admin_Single_Add_User_Screen::run();
            };

            //各个列表显示ID
            $add_time = MaMi_Admin::get_config($option, 'add_time');
            if ($add_time) {
                add_action('admin_init', array(__CLASS__, 'add_list_id_run'));
            };

            //文章和媒体添加日期筛选
            $show_id = MaMi_Admin::get_config($option, 'show_id');
            if ($show_id) {
                self::filter_time_run();
            }
        }



       

        /**
         * 优化 - 按日期筛选媒体和图片
         * 来源：https://rudrastyh.com/wordpress/date-range-filter.html
         */
        public static function filter_time_run()
        {
            // 如果不想删除默认的“按月筛选”，请删除/注释此行
            //add_filter('months_dropdown_results', '__return_empty_array');

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
            wp_enqueue_style('jquery-ui', plugin_dir_url(dirname(__DIR__)) . 'css/jquery-ui.min.css');
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
        .ui-icon{
            text-indent: inherit;
            cursor: pointer;
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
    } //end
}
