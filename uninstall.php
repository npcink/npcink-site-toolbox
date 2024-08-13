<?php

/**
 * 在卸载插件时激发。
 *
 * When populating this file, consider the following flow
 * of control:
 *
 * - This method should be static
 * - Check if the $_REQUEST content actually is the plugin name
 * - Run an admin referrer check to make sure it goes through authentication
 * - Verify the output of $_GET makes sense
 * - Repeat with other user roles. Best directly by using the links/query string parameters.
 * - Repeat things for multisite. Once for a single site in the network, once sitewide.
 *
 * This file may be updated more in future version of the Boilerplate; however, this is the
 * general skeleton and outline for how the file should work.
 *
 * For more information, see the following discussion:
 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/pull/123#issuecomment-28541913
 *
 * @link       https://www.npc.ink
 * @since      1.0.0
 *
 * @package    Dema
 */

// 如果未从WordPress调用卸载，请退出。
if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}


/**
 * mabox_trends_special：专题页面 - 搜索词，
 */
//删除自定义字段
delete_post_meta_by_key('mabox_trends_special');


//删除选项数据
delete_option("Magick_ToolBox_Option");
