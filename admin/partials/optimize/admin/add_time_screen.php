<?php

defined('ABSPATH') || exit;

/**
 * 效果：按日期筛选文章和媒体
 * 来源：https://rudrastyh.com/wordpress/date-range-filter.html
 */
if (!class_exists('MaBox_Admin_Add_Time_Screen')) {
    class MaBox_Admin_Add_Time_Screen implements MaBox_Module_Interface
    {
        //加载
        public static function run($config = array())
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
            wp_enqueue_style('jquery-ui', plugin_dir_url(dirname(__DIR__)) . 'css/jquery-ui.min.css', array(), MAGICK_MIXTURE_VERSION);
            wp_enqueue_script('jquery-ui-datepicker');
        }

        /*
         * 带有CSS/JS的两个输入字段
         *如果您想将CSS和JavaScript移动到外部文件-欢迎。
         */
        public static function form()
        {
            $dates = self::requested_dates();
            $from = $dates['from'];
            $to = $dates['to'];

            echo '<style>
            #ui-datepicker-div{
                background-color: #efefef;
                padding: 1rem .8rem;
            }
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

            $dates = self::requested_dates();

            if (
                is_admin()
                && $admin_query->is_main_query()
                // 默认情况下，过滤器将被添加到所有post类型中，您可以使用$_GET['post_type']来限制某些类型的过滤器
                && in_array($pagenow, array('edit.php', 'upload.php'), true)
                && ('' !== $dates['from'] || '' !== $dates['to'])
            ) {

                $admin_query->set(
                    'date_query', //我喜欢WordPress 3.7中出现的日期查询！
                    array(
                        'after' => $dates['from'], // any strtotime()-acceptable format!
                        'before' => $dates['to'],
                        'inclusive' => true, // 还包括选定的日期
                        'column' => 'post_date', // 'post_modified', 'post_date_gmt', 'post_modified_gmt'
                    )
                );
            }

            return $admin_query;
        }

        /**
         * Read-only admin list filters do not change state, so they do not need a nonce.
         *
         * @return array{from: string, to: string}
         */
        private static function requested_dates()
        {
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only value is unslashed here, then type-checked and sanitized below.
            $from_value = wp_unslash($_GET['mishaDateFrom'] ?? '');
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended,WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Read-only value is unslashed here, then type-checked and sanitized below.
            $to_value = wp_unslash($_GET['mishaDateTo'] ?? '');

            $from = is_string($from_value) ? sanitize_text_field($from_value) : '';
            $to = is_string($to_value) ? sanitize_text_field($to_value) : '';

            return array(
                'from' => $from,
                'to' => $to,
            );
        }
    }
}
