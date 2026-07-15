<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;



/**
 *插件的管理员特定功能。
 *
 *定义插件名称、版本和两个示例挂钩
 *将管理员特定的样式表和JavaScript排入队列。
 *
 * @package    Plugin_Name
 * @subpackage Plugin_Name/admin
 * @author     Your Name <email@example.com>
 */
class MaBox_Admin
{

    /**
     * 选项
     */

    /**
     * 此插件的ID。
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    此插件的ID。
     */
    private static $plugin_name;

    /**
     * 此插件的版本。
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version   此插件的当前版本。
     */
    private static $version;

    /**
     * 初始化类并设置其财产。
     *
     * @since    1.0.0
     * @param      string    $plugin_name       此插件的名称。
     * @param      string    $version    此插件的版本。
     */
    public function __construct($plugin_name, $version)
    {

        self::$plugin_name = $plugin_name;
        self::$version = $version;

        $this->load(); //加载所需的依赖项
        $this->run(); //跑起来

    }


    /**
     * 启动
     */
    public function run()
    {

        //加载菜单
        add_action('admin_menu',  array(__CLASS__, 'add_menu'));

        //加载菜单用的 CSS 和 JS 资源
        add_action('admin_enqueue_scripts', array(__CLASS__, 'load_admin_script'));

        // 注册 REST API 端点
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
    }




    /**
     * 添加菜单
     */
    public static function add_menu()
    {
        //添加插件菜单

        add_plugins_page(
            '魔法工具箱设置',             // 要在此页面的浏览器窗口中显示的标题。
            '魔法工具箱',            // 要为此菜单项显示的文本
            'manage_options',            // 哪种类型的用户可以看到此菜单项
            'MaBox_config',    // The unique ID - that is, the slug - for this menu item 
            array(__CLASS__, 'MaBox_display'),   // 呈现此菜单的页面时要调用的函数的名称
            '200.2'
        );
    }

    /**
     * 菜单回调
     */
    public static function MaBox_display()
    {
        echo '<div class="wrap"> <h2>';
        echo '</h2><div id="root"></div>';

        if (isset($_GET['mabox_debug']) && current_user_can('manage_options')) {
            self::render_debug_panel();
        }
    }

    /**
     * 路由表调试面板
     */
    private static function render_debug_panel()
    {
        $active_modules = get_option(MAGICK_TOOLBOX_ACTIVE_MODULES, array());
        $cache = false;
        if (function_exists('wp_cache_get')) {
            $cache = wp_cache_get('mabox_active_modules', 'mabox');
        }

        echo '<div class="postbox" style="margin-top:20px;padding:15px;">';
        echo '<h3>🔧 按需加载调试面板</h3>';
        echo '<p>访问地址添加 <code>?mabox_debug=1</code> 查看此面板</p>';
        echo '<table class="widefat" style="margin-top:10px;">';
        echo '<tr><th style="width:200px;">路由表模块数</th><td>' . count($active_modules) . ' 个</td></tr>';
        echo '<tr><th>缓存命中</th><td>' . ($cache !== false ? '✅ 是' : '❌ 否（从数据库读取）') . '</td></tr>';
        echo '<tr><th>加载模式</th><td>' . (empty($active_modules) ? '⚠️ 传统模式（降级回退）' : '✅ 按需加载模式') . '</td></tr>';

        if (!empty($active_modules)) {
            echo '<tr><th>已激活模块</th><td><ul style="margin:5px 0;columns:2;">';
            foreach ($active_modules as $module) {
                echo '<li>' . esc_html($module) . '</li>';
            }
            echo '</ul></td></tr>';
        }

        echo '</table>';
        echo '<p style="margin-top:10px;"><a href="' . esc_url(remove_query_arg('mabox_debug')) . '" class="button">关闭调试面板</a>';
        echo ' <a href="' . esc_url(add_query_arg('mabox_debug', '1')) . '" class="button">刷新路由表</a></p>';
        echo '</div>';
    }

    /**
     * 加载JS和CSS资源
     */
    public static  function load_admin_script($hook)
    {
        $ver = self::$version;
        $name = self::$plugin_name;

        //是否是指定页面
        if ('plugins_page_MaBox_config' != $hook) {
            return;
        }

        //准备地址
        $index_css = plugin_dir_url(__DIR__) . 'vite/admin/dist/index.css';
        $index_js = plugin_dir_url(__DIR__) . 'vite/admin/dist/index.js';

        wp_enqueue_style($name, $index_css, array(), $ver, false);
        wp_enqueue_script($name, $index_js, array(), $ver, true);

        // 移动端适配
        wp_add_inline_style($name, '
            @media (max-width: 768px) {
                #root { margin: 0 -10px; }
                .ant-form-item-label { width: 100% !important; padding-right: 0 !important; }
                .ant-form-item-control-wrapper { width: 100% !important; }
                .ant-tabs-nav { overflow-x: auto !important; }
                .ant-tabs-nav-list { white-space: nowrap !important; }
                .ant-anchor { display: none !important; }
                .ant-form { max-width: 100% !important; }
                .ant-card-body { padding: 12px !important; }
                .ant-statistic { margin-bottom: 12px !important; }
                .ant-layout-header { padding-inline: 16px !important; height: auto !important; line-height: 1.5 !important; flex-wrap: wrap !important; }
                .ant-layout-header h1 { font-size: 18px !important; width: 100%; margin-bottom: 8px; }
                .ant-tabs-tabpane { padding: 8px !important; }
                .ant-form-item { margin-bottom: 16px !important; }
                .ant-switch { min-width: 36px; height: 20px; }
                .ant-btn { font-size: 13px; padding: 4px 12px; }
            }
            @media (min-width: 769px) and (max-width: 1024px) {
                .ant-form-item-label { width: 120px !important; }
                .ant-form-item-control-wrapper { width: calc(100% - 120px) !important; }
            }
        ');



        $MaBox_array = array(
            'cat_arr' => self::get_cat_data(),
            'single_arr' => self::get_single_data(),
            'url_site' => get_site_url(),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'apiBase' => esc_url_raw(rest_url('mabox/v1')),
            'restNonce' => wp_create_nonce('wp_rest'),
        );
        wp_localize_script($name, 'dataLocal', $MaBox_array);


    }


    /**
     * 整理文章数据
     */
    public static function get_single_data()
    {
        $posts = get_posts(array('posts_per_page' => 200, 'orderby' => 'date', 'order' => 'DESC'));

        $post_list = array();

        foreach ($posts as $post) {
            $post_obj = new stdClass();
            $post_obj->label = $post->post_title;
            $post_obj->value = $post->ID;
            $post_list[] = $post_obj;
        }

        return $post_list;
    }

    /**
     * 整理分类数据
     */
    public static function get_cat_data()
    {
        $categories = get_categories();

        $category_list = array();

        foreach ($categories as $category) {
            $category_obj = new stdClass();
            $category_obj->label = $category->name;
            $category_obj->value = $category->term_id;
            $category_list[] = $category_obj;
        }
        return $category_list;
    }




    /**
     * 统一配置保存入口
     *
     * @param array $settings 不含凭据的完整配置数据
     * @param array $secret_changes 凭据 replace/clear 操作
     * @return array ['success' => bool, 'message' => string, 'status' => int]
     */
    private static function do_save_config($settings, $secret_changes)
    {
        $merge = MaBox_Config_Manager::merge_secret_changes($settings, $secret_changes);
        if (!$merge['success']) {
            return array('success' => false, 'message' => $merge['error'], 'status' => 400);
        }

        $config = $merge['data'];
        $validation = MaBox_Config_Schema::validate_full_config($config);
        $config = $validation['data'];

        $result = MaBox_Config_Manager::save_full_config($config);

        if (!$result['success']) {
            if (class_exists('MaBox_Audit_Logger')) {
                MaBox_Audit_Logger::config('保存配置失败，已回滚');
            } else {
                error_log('[MaBox] Failed to update option, rolled back to previous state');
            }
            return array('success' => false, 'message' => '保存失败，已恢复为之前的设置', 'status' => 500);
        }

        $active_modules = MaBox_Module_Loader::get_active_modules($config);
        update_option(MAGICK_TOOLBOX_ACTIVE_MODULES, $active_modules);

        if (function_exists('wp_cache_set')) {
            wp_cache_set('mabox_active_modules', $active_modules, 'mabox', HOUR_IN_SECONDS);
        }

        self::clear_config_cache();

        if (class_exists('MaBox_Audit_Logger')) {
            MaBox_Audit_Logger::config('插件配置已更新', array(
                'user_id' => get_current_user_id(),
            ));
        }

        $message = '保存成功';
        if (!$validation['valid']) {
            $message .= '（部分字段已自动修正）';
        }

        return array('success' => true, 'message' => $message, 'status' => 200);
    }

    /**
     * REST API: 保存设置（唯一权威写入口）
     */
    public static function rest_save_settings($request)
    {
        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', '权限不足', array('status' => 403));
        }

        $body = $request->get_json_params();
        if (!is_array($body)) {
            return new \WP_Error('rest_invalid_data', '设置数据格式无效', array('status' => 400));
        }

        $allowed_keys = array('settings', 'secretChanges');
        if (!empty(array_diff(array_keys($body), $allowed_keys))
            || !array_key_exists('settings', $body)
            || !is_array($body['settings'])
            || (isset($body['secretChanges']) && !is_array($body['secretChanges']))) {
            return new \WP_Error('rest_invalid_data', '请求仅允许 settings 和 secretChanges', array('status' => 400));
        }

        $secret_changes = isset($body['secretChanges']) ? $body['secretChanges'] : array();
        $result = self::do_save_config($body['settings'], $secret_changes);

        if (!$result['success']) {
            $code = $result['status'] === 400 ? 'rest_invalid_data' : 'rest_save_failed';
            return new \WP_Error($code, $result['message'], array('status' => $result['status']));
        }

        return rest_ensure_response([
            'success' => true,
            'message' => $result['message'],
        ]);
    }

    public static function rest_get_settings($request)
    {
        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', '权限不足', array('status' => 403));
        }

        $browser_config = MaBox_Config_Manager::get_browser_config();
        return rest_ensure_response([
            'success' => true,
            'data' => $browser_config['data'],
            'secretStatus' => $browser_config['secretStatus'],
        ]);
    }

    /**
     * REST API: 获取配置 Schema
     */
    public static function rest_get_schema($request)
    {
        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', '权限不足', array('status' => 403));
        }

        $schema = MaBox_Config_Schema::get_schema();
        $defaults = MaBox_Config_Schema::get_defaults();
        $uiSchema = MaBox_Config_Schema::get_ui_schema();

        return rest_ensure_response([
            'success' => true,
            'data' => array(
                'schema' => $schema,
                'defaults' => $defaults,
                'uiSchema' => $uiSchema,
            ),
        ]);
    }

    /**
     * 获取诊断摘要
     */
    public static function rest_get_diagnostics_summary(\WP_REST_Request $request)
    {
        if (!class_exists('MaBox_Diagnostics')) {
            return new \WP_Error(
                'diagnostics_not_available',
                __('诊断服务暂不可用', 'magick-toolbox'),
                array('status' => 500)
            );
        }

        $summary = MaBox_Diagnostics::get_summary();

        return rest_ensure_response(array(
            'success' => true,
            'data'    => $summary,
        ));
    }

    /**
     * 注册 REST API 路由
     */
    public static function register_rest_routes()
    {
        MaBox_Rest_Route_Registry::clear();

        self::register_settings_routes();
        self::register_performance_routes();
        self::register_page_routes();
        self::register_tools_routes();
        self::register_public_routes();
        self::register_domestic_routes();
        self::register_diagnostics_routes();

        MaBox_Rest_Route_Registry::register_all();

        do_action('mabox_register_rest_routes');
    }

    private static function register_settings_routes()
    {
        MaBox_Rest_Route_Registry::add('/settings', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'rest_get_settings'),
                'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            ),
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array(__CLASS__, 'rest_save_settings'),
                'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
                'args'                => array(
                    'settings' => array(
                        'required'          => true,
                        'type'              => 'object',
                        'description'       => '不含凭据的完整设置',
                        'sanitize_callback' => function ($value) {
                            return is_array($value) ? $value : array();
                        },
                        'validate_callback' => function ($value) {
                            return is_array($value);
                        },
                    ),
                    'secretChanges' => array(
                        'required'          => false,
                        'type'              => 'object',
                        'description'       => '凭据 replace/clear 操作',
                        'default'           => array(),
                        'sanitize_callback' => function ($value) {
                            return is_array($value) ? $value : array();
                        },
                        'validate_callback' => function ($value) {
                            return is_array($value);
                        },
                    ),
                ),
            ),
        ), 'settings');

        MaBox_Rest_Route_Registry::add('/settings/schema', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'rest_get_schema'),
                'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            ),
        ), 'settings');

    }

    private static function register_performance_routes()
    {
        MaBox_Rest_Route_Registry::add('/performance/media/check', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Media_Health', 'ajax_check'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'post_id' => array(
                    'required'          => false,
                    'validate_callback' => function ($value) {
                        return empty($value) || (is_numeric($value) && $value > 0);
                    },
                    'sanitize_callback' => array(__CLASS__, 'sanitize_int_arg'),
                ),
            ),
        ), 'performance');

        MaBox_Rest_Route_Registry::add('/performance/media/fix-alt', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Media_Health', 'ajax_fix_alt'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'post_id' => array(
                    'required'          => false,
                    'validate_callback' => function ($value) {
                        return empty($value) || (is_numeric($value) && $value > 0);
                    },
                ),
            ),
        ), 'performance');

        MaBox_Rest_Route_Registry::add('/performance/seo/check', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Seo_Checker', 'ajax_check'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'post_id' => array(
                    'required'          => false,
                    'validate_callback' => function ($value) {
                        return empty($value) || (is_numeric($value) && $value > 0);
                    },
                ),
            ),
        ), 'performance');

        MaBox_Rest_Route_Registry::add('/performance/seo/fix-alt', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Seo_Checker', 'ajax_fix_alt'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'post_id' => array(
                    'required'          => false,
                    'validate_callback' => function ($value) {
                        return empty($value) || (is_numeric($value) && $value > 0);
                    },
                    'sanitize_callback' => array(__CLASS__, 'sanitize_int_arg'),
                ),
            ),
        ), 'performance');

        MaBox_Rest_Route_Registry::add('/performance/db/stats', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array('MaBox_Performance_Db_Clean', 'ajax_stats'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
        ), 'performance');

        MaBox_Rest_Route_Registry::add('/performance/db/preview', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Db_Clean', 'ajax_preview'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'type' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        $allowed = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'all', 'pending', 'trash');
                        return is_string($value) && in_array($value, $allowed, true);
                    },
                ),
            ),
        ), 'performance');

        MaBox_Rest_Route_Registry::add('/performance/db/clean', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Db_Clean', 'ajax_clean'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'type' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        $allowed = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'all', 'pending', 'trash');
                        return is_string($value) && in_array($value, $allowed, true);
                    },
                ),
                'dry_run' => array(
                    'default'           => true,
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ),
            ),
        ), 'performance');
    }

    private static function register_page_routes()
    {
        MaBox_Rest_Route_Registry::add('/page/batch-replace', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Page_Batch_Replace', 'manual_replace'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'pairs' => array(
                    'required'          => true,
                    'type'              => 'array',
                    'description'       => '替换规则数组，每项含 search 和 replace',
                    'items'             => array(
                        'type'       => 'object',
                        'properties' => array(
                            'search'  => array('type' => 'string', 'required' => true),
                            'replace' => array('type' => 'string', 'required' => true),
                        ),
                    ),
                    'sanitize_callback' => function ($value) {
                        if (!is_array($value)) return array();
                        return array_map(function ($pair) {
                            return array(
                                'search'  => isset($pair['search']) ? wp_kses_post($pair['search']) : '',
                                'replace' => isset($pair['replace']) ? wp_kses_post($pair['replace']) : '',
                            );
                        }, $value);
                    },
                    'validate_callback' => function ($value) {
                        if (!is_array($value) || empty($value)) return false;
                        foreach ($value as $pair) {
                            if (!is_array($pair) || !isset($pair['search']) || !isset($pair['replace'])) {
                                return false;
                            }
                        }
                        return true;
                    },
                ),
                'dry_run' => array(
                    'default'           => true,
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ),
            ),
        ), 'page');

        MaBox_Rest_Route_Registry::add('/page/batch-replace/rollback', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Page_Batch_Replace', 'rollback_all'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'confirm' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        return $value === true || $value === 'true' || $value === 1;
                    },
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ),
            ),
        ), 'page');

        MaBox_Rest_Route_Registry::add('/page/batch-replace/rollback/(?P<post_id>\d+)', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Page_Batch_Replace', 'rollback'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'post_id' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value > 0;
                    },
                ),
            ),
        ), 'page');
    }

    private static function register_tools_routes()
    {
        MaBox_Rest_Route_Registry::add('/tools/tables', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array('MaBox_Download_SQL_Table', 'get_all_table_names'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
        ), 'tools');

        MaBox_Rest_Route_Registry::add('/tools/table-data', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Download_SQL_Table', 'get_table_data'),
            'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'databaseName' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        return is_string($value) && preg_match('/^[a-zA-Z0-9_]+$/', $value);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'limit' => array(
                    'default'           => 1000,
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value > 0 && $value <= 1000;
                    },
                    'sanitize_callback' => array(__CLASS__, 'sanitize_int_arg'),
                ),
                'offset' => array(
                    'default'           => 0,
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value >= 0;
                    },
                    'sanitize_callback' => array(__CLASS__, 'sanitize_int_arg'),
                ),
            ),
        ), 'tools');

        MaBox_Rest_Route_Registry::add('/tools/categories', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array('MaBox_Interface_Category_Data', 'get_all_category_names'),
            'permission_callback' => '__return_true',
        ), 'tools');
    }

    private static function register_public_routes()
    {
        MaBox_Rest_Route_Registry::add('/public/search-log', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Search_Enhance', 'rest_log_search'),
            'permission_callback' => MaBox_Rest_Route_Registry::public_nonce_rate_limited('search-log', 'mabox_public_api', array('max_requests' => 30, 'time_window' => 60)),
            'args'                => array(
                'keyword' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        return is_string($value) && strlen($value) <= 200;
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ), 'public');

        MaBox_Rest_Route_Registry::add('/public/rating', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Page_Article_Rating', 'handle_rating'),
            'permission_callback' => MaBox_Rest_Route_Registry::public_nonce_rate_limited('rating', 'mabox_public_api', array('max_requests' => 30, 'time_window' => 60)),
            'args'                => array(
                'post_id' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value > 0;
                    },
                    'sanitize_callback' => array(__CLASS__, 'sanitize_int_arg'),
                ),
                'score' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        return is_numeric($value) && $value >= 1 && $value <= 5;
                    },
                    'sanitize_callback' => array(__CLASS__, 'sanitize_int_arg'),
                ),
            ),
        ), 'public');

        MaBox_Rest_Route_Registry::add('/public/wx-unlock/verify', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_ShortCode_Wx_Unlock', 'ajax_verify'),
            'permission_callback' => MaBox_Rest_Route_Registry::public_nonce_rate_limited('wx-unlock', 'mabox_public_api', array('max_requests' => 20, 'time_window' => 60)),
            'args'                => array(
                'ticket' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        return is_string($value) && !empty($value);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'randstr' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        return is_string($value) && !empty($value);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ), 'public');
    }

    private static function register_domestic_routes()
    {
        MaBox_Rest_Route_Registry::add('/domestic/environment/check', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array('MaBox_Domestic_Environment', 'rest_check'),
                'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            ),
        ), 'domestic');

        MaBox_Rest_Route_Registry::add('/domestic/environment/apply', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array('MaBox_Domestic_Environment', 'rest_apply'),
                'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
                'args'                => array(
                    'fixes' => array(
                        'required'          => true,
                        'type'              => 'array',
                        'description'       => '要修复的项目列表',
                        'items'             => array('type' => 'string'),
                        'sanitize_callback' => function ($value) {
                            return is_array($value) ? array_map('sanitize_text_field', $value) : array();
                        },
                    ),
                ),
            ),
        ), 'domestic');
    }

    private static function register_diagnostics_routes()
    {
        MaBox_Rest_Route_Registry::add('/diagnostics/summary', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'rest_get_diagnostics_summary'),
                'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
            ),
        ), 'diagnostics');

        MaBox_Rest_Route_Registry::add('/search-health/summary', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array('MaBox_Search_Health', 'rest_get_summary'),
                'permission_callback' => MaBox_Rest_Route_Registry::admin_permission(),
                'args'                => array(
                    'days' => array(
                        'required'          => false,
                        'type'              => 'integer',
                        'description'       => '统计天数范围',
                        'default'           => 30,
                        'sanitize_callback' => array(__CLASS__, 'sanitize_int_arg'),
                        'validate_callback' => function ($value) {
                            return is_numeric($value) && (int) $value >= 1 && (int) $value <= 365;
                        },
                    ),
                ),
            ),
        ), 'diagnostics');
    }

    /**
     * 提供选项（直接使用 Config_Manager 的缓存）
     */
    public static function get_seting($option)
    {
        $config = MaBox_Config_Manager::get_merged_config();
        $value = self::get_config($config, $option);
        return $value;
    }

    public static function clear_config_cache()
    {
        MaBox_Config_Manager::clear_cache();
    }

    /**
     * 验证公开 REST API 端点的 nonce（防 CSRF/滥用）
     */
    public static function verify_public_nonce($request) {
        $nonce = $request->get_header('x-mabox-nonce');
        if (empty($nonce)) {
            $nonce = $request->get_param('nonce');
        }
        return wp_verify_nonce($nonce, 'mabox_public_api') !== false;
    }

    public static function sanitize_int_arg($value)
    {
        return intval($value);
    }

    public static function get_config($config, $property, $defaultValue = false)
    {
        if (is_array($config) && array_key_exists($property, $config)) {
            return $config[$property];
        }
        if (is_object($config) && property_exists($config, $property)) {
            return $config->$property;
        }
        return $defaultValue;
    }

    public function load()
    {
        $option = MaBox_Config_Manager::get_merged_config();
        if (empty($option)) {
            return;
        }

        $active_modules = false;
        if (function_exists('wp_cache_get')) {
            $active_modules = wp_cache_get('mabox_active_modules', 'mabox');
        }

        if ($active_modules === false) {
            $active_modules = MaBox_Module_Loader::get_active_modules($option);
            update_option(MAGICK_TOOLBOX_ACTIVE_MODULES, $active_modules);

            if (function_exists('wp_cache_set')) {
                wp_cache_set('mabox_active_modules', $active_modules, 'mabox', HOUR_IN_SECONDS);
            }
        }

        foreach ($active_modules as $module_id) {
            MaBox_Module_Loader::load_module($module_id, $option);
        }
    }

    //公用返回按钮
    public static function back_button($text = '返回')
    {
        $button = sprintf(
            '<br/><a href="javascript:void(0);" onclick="window.history.back();" class="back_box">
            <button class="back_button">%s</button>
        </a>
                <style>
                /**
         * 返回按钮
         */
        .back_button {
          padding: .2em 1em;
          margin: 10px 0 0 0;
          cursor: pointer;
        }
                </style>
        
        ',

            esc_html($text)
        );
        return $button;
    }
}//end
