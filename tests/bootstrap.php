<?php
/**
 * PHPUnit Bootstrap
 *
 * 初始化 WordPress 测试环境
 */

// 定义 ABSPATH，防止测试文件中的访问守卫直接退出
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', dirname( __DIR__ ) . '/' );
}

// 定义插件常量（供纯单元测试使用）
if ( ! defined( 'MAGICK_MIXTURE_OPTION' ) ) {
	define( 'MAGICK_MIXTURE_OPTION', 'Magick_ToolBox_Option' );
}
if ( ! defined( 'MAGICK_MIXTURE_CONFIG_VERSION' ) ) {
	define( 'MAGICK_MIXTURE_CONFIG_VERSION', 'Magick_ToolBox_Config_Version' );
}
if ( ! defined( 'MAGICK_MIXTURE_CONFIG_BACKUP' ) ) {
	define( 'MAGICK_MIXTURE_CONFIG_BACKUP', 'Magick_ToolBox_Config_Backup' );
}
if ( ! defined( 'MAGICK_MIXTURE_OPTION_OPTIMIZE' ) ) {
	define( 'MAGICK_MIXTURE_OPTION_OPTIMIZE', 'Magick_ToolBox_Option_Optimize' );
	define( 'MAGICK_MIXTURE_OPTION_PAGE', 'Magick_ToolBox_Option_Page' );
	define( 'MAGICK_MIXTURE_OPTION_FUNCTION', 'Magick_ToolBox_Option_Function' );
	define( 'MAGICK_MIXTURE_OPTION_LOGIN', 'Magick_ToolBox_Option_Login' );
	define( 'MAGICK_MIXTURE_OPTION_DOMESTIC', 'Magick_ToolBox_Option_Domestic' );
	define( 'MAGICK_MIXTURE_OPTION_PERFORMANCE', 'Magick_ToolBox_Option_Performance' );
	define( 'MAGICK_MIXTURE_OPTION_AI_REVIEW', 'Magick_ToolBox_Option_AiReview' );

}
if ( ! defined( 'MAGICK_TOOLBOX_ACTIVE_MODULES' ) ) {
	define( 'MAGICK_TOOLBOX_ACTIVE_MODULES', 'Magick_ToolBox_Active_Modules' );
}
if ( ! defined( 'HOUR_IN_SECONDS' ) ) {
	define( 'HOUR_IN_SECONDS', 3600 );
}

// WordPress 核心函数 mock（纯单元测试环境）
if ( ! function_exists( 'get_option' ) ) {
	function get_option( $option, $default = false ) {
		global $_test_option_store;
		return $_test_option_store[ $option ] ?? $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $option, $value, $autoload = null ) {
		global $_test_option_store;
		$_test_option_store[ $option ] = $value;
		return true;
	}
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $option ) {
		global $_test_option_store;
		unset( $_test_option_store[ $option ] );
		return true;
	}
}
if ( ! function_exists( 'get_transient' ) ) {
	function get_transient( $transient ) {
		global $_test_transient_store;
		return $_test_transient_store[ $transient ] ?? false;
	}
}
if ( ! function_exists( 'set_transient' ) ) {
	function set_transient( $transient, $value, $expiration = 0 ) {
		global $_test_transient_store;
		$_test_transient_store[ $transient ] = $value;
		return true;
	}
}
if ( ! function_exists( 'delete_transient' ) ) {
	function delete_transient( $transient ) {
		global $_test_transient_store;
		unset( $_test_transient_store[ $transient ] );
		return true;
	}
}
if ( ! function_exists( 'plugin_dir_path' ) ) {
	function plugin_dir_path( $file ) {
		return trailingslashit( dirname( $file ) );
	}
}
if ( ! function_exists( 'trailingslashit' ) ) {
	function trailingslashit( $string ) {
		return rtrim( $string, '/\\' ) . '/';
	}
}
if ( ! function_exists( 'wp_parse_args' ) ) {
	function wp_parse_args( $args, $defaults = array() ) {
		if ( is_object( $args ) ) {
			$parsed_args = get_object_vars( $args );
		} elseif ( is_array( $args ) ) {
			$parsed_args = &$args;
		} else {
			wp_parse_str( $args, $parsed_args );
		}
		return array_merge( $defaults, $parsed_args );
	}
}
if ( ! function_exists( 'wp_parse_str' ) ) {
	function wp_parse_str( $string, &$array ) {
		parse_str( $string, $array );
	}
}
if ( ! function_exists( 'stripslashes_deep' ) ) {
	function stripslashes_deep( $value ) {
		return map_deep( $value, 'stripslashes_from_strings_only' );
	}
}
if ( ! function_exists( 'map_deep' ) ) {
	function map_deep( $value, $callback ) {
		if ( is_array( $value ) ) {
			foreach ( $value as $index => $item ) {
				$value[ $index ] = map_deep( $item, $callback );
			}
		} elseif ( is_object( $value ) ) {
			$object_vars = get_object_vars( $value );
			foreach ( $object_vars as $property_name => $property_value ) {
				$value->$property_name = map_deep( $property_value, $callback );
			}
		} else {
			$value = call_user_func( $callback, $value );
		}
		return $value;
	}
}
if ( ! function_exists( 'stripslashes_from_strings_only' ) ) {
	function stripslashes_from_strings_only( $value ) {
		return is_string( $value ) ? stripslashes( $value ) : $value;
	}
}
if ( ! function_exists( 'current_time' ) ) {
	function current_time( $type, $gmt = 0 ) {
		return date( $type === 'mysql' ? 'Y-m-d H:i:s' : $type );
	}
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action = '' ) {
		return 1;
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $capability ) {
		return true;
	}
}
if ( ! function_exists( 'get_current_user_id' ) ) {
	function get_current_user_id() {
		return 0;
	}
}
if ( ! function_exists( 'wp_get_current_user' ) ) {
	function wp_get_current_user() {
		return (object) array( 'user_login' => 'cli' );
	}
}
if ( ! function_exists( 'do_action' ) ) {
	function do_action( $tag, ...$args ) {
		// no-op for unit tests
	}
}
if ( ! class_exists( 'WP_REST_Server' ) ) {
	class WP_REST_Server {
		const READABLE  = 'GET';
		const CREATABLE = 'POST';
		const EDITABLE  = 'POST,PUT,PATCH';
		const DELETABLE = 'DELETE';
		const ALLMETHODS = 'GET,POST,PUT,PATCH,DELETE';
	}
}
if ( ! function_exists( 'register_rest_route' ) ) {
	function register_rest_route( $namespace, $route, $args = array(), $override = false ) {
		// no-op for unit tests
	}
}
if ( ! function_exists( 'wp_cache_get' ) ) {
	function wp_cache_get( $key, $group = '' ) {
		global $_test_cache_store;
		return $_test_cache_store[ $group ][ $key ] ?? false;
	}
}
if ( ! function_exists( 'wp_cache_set' ) ) {
	function wp_cache_set( $key, $data, $group = '', $expire = 0 ) {
		global $_test_cache_store;
		$_test_cache_store[ $group ][ $key ] = $data;
		return true;
	}
}
if ( ! function_exists( 'wp_cache_delete' ) ) {
	function wp_cache_delete( $key, $group = '' ) {
		global $_test_cache_store;
		unset( $_test_cache_store[ $group ][ $key ] );
		return true;
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $str ) {
		return strip_tags( trim( $str ) );
	}
}
if ( ! function_exists( '__' ) ) {
	function __( $text, $domain = 'default' ) {
		return $text;
	}
}
if ( ! function_exists( 'rest_sanitize_boolean' ) ) {
	function rest_sanitize_boolean( $value ) {
		return filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}
}
if ( ! function_exists( 'wp_kses_post' ) ) {
	function wp_kses_post( $data ) {
		return strip_tags( $data, '<p><a><strong><em><br><ul><ol><li><h1><h2><h3><h4><h5><h6><img><blockquote><pre><code><span><div>' );
	}
}
if ( ! function_exists( 'is_email' ) ) {
	function is_email( $email ) {
		return filter_var( $email, FILTER_VALIDATE_EMAIL ) !== false;
	}
}
if ( ! function_exists( 'rest_ensure_response' ) ) {
	function rest_ensure_response( $data ) {
		return $data;
	}
}
if ( ! function_exists( 'get_search_query' ) ) {
	function get_search_query() {
		global $_test_search_query;
		return $_test_search_query ?? '';
	}
}

// 加载插件自动加载器
if ( file_exists( dirname( __FILE__ ) . '/../includes/autoload.php' ) ) {
	require_once dirname( __FILE__ ) . '/../includes/autoload.php';
}

// 检查 WordPress 测试套件是否可用（完整集成测试路径）
if ( ! defined( 'WP_TESTS_DIR' ) ) {
	$wp_tests_dir = getenv( 'WP_TESTS_DIR' );
	if ( ! $wp_tests_dir ) {
		$possible_paths = array(
			'/tmp/wordpress-tests-lib',
			getenv( 'HOME' ) . '/wordpress-tests-lib',
		);
		foreach ( $possible_paths as $path ) {
			if ( file_exists( $path . '/includes/functions.php' ) ) {
				$wp_tests_dir = $path;
				break;
			}
		}
	}
}

if ( $wp_tests_dir && file_exists( $wp_tests_dir . '/includes/functions.php' ) ) {
	require_once $wp_tests_dir . '/includes/functions.php';

	function _manually_load_plugin() {
		require dirname( __FILE__ ) . '/../magick-tool-box.php';
	}
	tests_add_filter( 'muplugins_loaded', '_manually_load_plugin' );

	require $wp_tests_dir . '/includes/bootstrap.php';
} else {
	// 纯单元测试环境：提供 WP_UnitTestCase fallback
	if ( ! class_exists( 'WP_UnitTestCase' ) ) {
		class WP_UnitTestCase extends PHPUnit\Framework\TestCase {
		}
	}
}
