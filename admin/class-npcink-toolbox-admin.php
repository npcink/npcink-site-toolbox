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
class Npcink_Toolbox_Admin
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
            'Npcink Site Toolbox 设置',   // 要在此页面的浏览器窗口中显示的标题。
            'Npcink 站点工具箱',           // 要为此菜单项显示的文本
            'manage_options',            // 哪种类型的用户可以看到此菜单项
            'npcink-site-toolbox', // The unique ID - that is, the slug - for this menu item.
            array(__CLASS__, 'Npcink_Toolbox_display'),   // 呈现此菜单的页面时要调用的函数的名称
            '200.2'
        );
    }

    /**
     * 菜单回调
     */
    public static function Npcink_Toolbox_display()
    {
        echo '<div class="wrap"> <h2>';
        echo '</h2><div id="root"></div>';
    }

    /**
     * 加载JS和CSS资源
     */
    public static  function load_admin_script($hook)
    {
        $name = self::$plugin_name;

        //是否是指定页面
        if ('plugins_page_npcink-site-toolbox' != $hook) {
            return;
        }

        //准备地址
        $index_css_path = plugin_dir_path(__DIR__) . 'vite/admin/dist/index.css';
        $index_js_path = plugin_dir_path(__DIR__) . 'vite/admin/dist/index.js';
        $index_css = plugin_dir_url(__DIR__) . 'vite/admin/dist/index.css';
        $index_js = plugin_dir_url(__DIR__) . 'vite/admin/dist/index.js';
        $index_css_version = is_file($index_css_path)
            ? self::$version . '-' . (string) filemtime($index_css_path)
            : self::$version;
        $index_js_version = is_file($index_js_path)
            ? self::$version . '-' . (string) filemtime($index_js_path)
            : self::$version;

        wp_enqueue_style($name, $index_css, array(), $index_css_version, false);
        wp_enqueue_script($name, $index_js, array(), $index_js_version, true);

        $npcink_site_toolbox_array = array(
            'cat_arr' => self::get_cat_data(),
            'single_arr' => self::get_single_data(),
            'url_site' => get_site_url(),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'connectorsUrl' => admin_url('options-connectors.php'),
            'apiBase' => esc_url_raw(rest_url('npcink-site-toolbox/v1')),
            'restNonce' => wp_create_nonce('wp_rest'),
            'webpSupported' => function_exists('wp_image_editor_supports')
                && wp_image_editor_supports(array('mime_type' => 'image/webp')),
        );
        wp_localize_script($name, 'dataLocal', $npcink_site_toolbox_array);


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
        $merge = Npcink_Toolbox_Config_Manager::merge_secret_changes($settings, $secret_changes);
        if (!$merge['success']) {
            return array('success' => false, 'message' => $merge['error'], 'status' => 400);
        }

        $config = $merge['data'];
        $validation = Npcink_Toolbox_Config_Schema::validate_full_config($config);
        $config = $validation['data'];

        $result = Npcink_Toolbox_Config_Manager::save_full_config($config);

        if (!$result['success']) {
            $rollback_complete = isset($result['rollback_complete']) && $result['rollback_complete'] === true;
            $fallback_message = $rollback_complete
                ? '保存失败，已恢复为之前的设置'
                : '保存失败，无法确认所有设置已恢复。请重新读取并核对设置后再保存';
            $message = isset($result['error']) && is_string($result['error']) && trim($result['error']) !== ''
                ? $result['error']
                : $fallback_message;

            // 回滚未确认时，即使底层意外返回了过度乐观的旧文案，也不能对外宣称已经恢复。
            if (!$rollback_complete && strpos($message, '已恢复') !== false) {
                $message = $fallback_message;
            }

            if (class_exists('Npcink_Toolbox_Audit_Logger')) {
                Npcink_Toolbox_Audit_Logger::config(
                    $rollback_complete ? '保存配置失败，已确认回滚' : '保存配置失败，回滚未能完整确认',
                    array(
                        'failed_modules' => isset($result['failed_modules']) ? $result['failed_modules'] : array(),
                        'rollback_failed_modules' => isset($result['rollback_failed_modules'])
                            ? $result['rollback_failed_modules']
                            : array(),
                    )
                );
            }
            return array('success' => false, 'message' => $message, 'status' => 500);
        }

        $active_modules = Npcink_Toolbox_Module_Loader::get_active_modules($config);
        update_option(NPCINK_SITE_TOOLBOX_ACTIVE_MODULES, $active_modules);

        if (function_exists('wp_cache_set')) {
            wp_cache_set('npcink_site_toolbox_active_modules', $active_modules, 'npcink_site_toolbox', HOUR_IN_SECONDS);
        }

        self::clear_config_cache();

        if (class_exists('Npcink_Toolbox_Audit_Logger')) {
            Npcink_Toolbox_Audit_Logger::config('插件配置已更新', array(
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

        $browser_config = Npcink_Toolbox_Config_Manager::get_browser_config();
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

        $schema = Npcink_Toolbox_Config_Schema::get_schema();
        $defaults = Npcink_Toolbox_Config_Schema::get_defaults();
        $uiSchema = Npcink_Toolbox_Config_Schema::get_ui_schema();

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
        if (!class_exists('Npcink_Toolbox_Diagnostics')) {
            return new \WP_Error(
                'diagnostics_not_available',
                __('诊断服务暂不可用', 'npcink-site-toolbox'),
                array('status' => 500)
            );
        }

        $summary = Npcink_Toolbox_Diagnostics::get_summary();

        return rest_ensure_response(array(
            'success' => true,
            'data'    => $summary,
        ));
    }

    /**
     * 获取脱敏的功能与运行状态。
     */
    public static function rest_get_feature_status(\WP_REST_Request $request)
    {
        if (!class_exists('Npcink_Toolbox_Diagnostics')) {
            return new \WP_Error(
                'diagnostics_not_available',
                __('诊断服务暂不可用', 'npcink-site-toolbox'),
                array('status' => 500)
            );
        }

        return rest_ensure_response(array(
            'success' => true,
            'data'    => Npcink_Toolbox_Diagnostics::get_feature_status(),
        ));
    }

    /**
     * 按需生成脱敏支持报告；不会保存或发送报告内容。
     */
    public static function rest_get_support_report(\WP_REST_Request $request)
    {
        if (!class_exists('Npcink_Toolbox_Diagnostics')) {
            return new \WP_Error(
                'diagnostics_not_available',
                __('诊断服务暂不可用', 'npcink-site-toolbox'),
                array('status' => 500)
            );
        }

        $report = Npcink_Toolbox_Diagnostics::get_support_report();
        if (is_wp_error($report)) {
            return $report;
        }

        return rest_ensure_response(array(
            'success' => true,
            'data'    => $report,
        ));
    }

    /**
     * 将最新脱敏诊断包一次性发送给 DeepSeek 进行只读分析。
     */
    public static function rest_analyze_support_report(\WP_REST_Request $request)
    {
        if (!class_exists('Npcink_Toolbox_Diagnostics')) {
            return new \WP_Error(
                'diagnostics_not_available',
                __('诊断服务暂不可用', 'npcink-site-toolbox'),
                array('status' => 500)
            );
        }

        $analysis = Npcink_Toolbox_Diagnostics::analyze_support_report(
            (string) $request->get_param('problem')
        );
        if (is_wp_error($analysis)) {
            return $analysis;
        }

        return rest_ensure_response(array(
            'success' => true,
            'data'    => $analysis,
        ));
    }

    /**
     * 生成性能或维护场景的只读预览包；不会调用 AI。
     */
    public static function rest_get_review_pack(\WP_REST_Request $request)
    {
        if (!class_exists('Npcink_Toolbox_Diagnostics')) {
            return new \WP_Error('diagnostics_not_available', __('诊断服务暂不可用', 'npcink-site-toolbox'), array('status' => 500));
        }

        $pack = Npcink_Toolbox_Diagnostics::get_review_pack((string) $request->get_param('scope'));
        if (is_wp_error($pack)) {
            return $pack;
        }

        return rest_ensure_response(array('success' => true, 'data' => $pack));
    }

    /**
     * 按场景将最新白名单数据包一次性发送给 DeepSeek。
     */
    public static function rest_analyze_review(\WP_REST_Request $request)
    {
        if (!class_exists('Npcink_Toolbox_Diagnostics')) {
            return new \WP_Error('diagnostics_not_available', __('诊断服务暂不可用', 'npcink-site-toolbox'), array('status' => 500));
        }

        $analysis = Npcink_Toolbox_Diagnostics::analyze_review(
            (string) $request->get_param('scenario'),
            (string) $request->get_param('problem'),
            $request->get_param('changes'),
            $request->get_param('baseline')
        );
        if (is_wp_error($analysis)) {
            return $analysis;
        }

        return rest_ensure_response(array('success' => true, 'data' => $analysis));
    }

    /**
     * 在当前页面临时上下文中创建一次受限追问。
     */
    public static function rest_create_follow_up(\WP_REST_Request $request)
    {
        if (!class_exists('Npcink_Toolbox_Diagnostics')) {
            return new \WP_Error('diagnostics_not_available', __('诊断服务暂不可用', 'npcink-site-toolbox'), array('status' => 500));
        }

        $answer = Npcink_Toolbox_Diagnostics::analyze_follow_up(
            (string) $request->get_param('scenario'),
            (string) $request->get_param('question'),
            $request->get_param('context'),
            (string) $request->get_param('initial_analysis'),
            $request->get_param('turns')
        );
        if (is_wp_error($answer)) {
            return $answer;
        }

        return rest_ensure_response(array('success' => true, 'data' => $answer));
    }

    /**
     * 仅保留设置风险分析需要的路径与前后值；凭据路径仍由诊断层再次排除。
     *
     * @param mixed $value 请求值。
     * @return array<int,array<string,mixed>>
     */
    public static function sanitize_review_changes($value)
    {
        if (!is_array($value)) {
            return array();
        }

        $changes = array();
        foreach (array_slice($value, 0, 50) as $change) {
            if (!is_array($change) || empty($change['path']) || !is_string($change['path'])) {
                continue;
            }
            $changes[] = array(
                'path'   => sanitize_text_field($change['path']),
                'before' => array_key_exists('before', $change) ? $change['before'] : null,
                'after'  => array_key_exists('after', $change) ? $change['after'] : null,
            );
        }
        return $changes;
    }

    /**
     * 基线会在诊断层按合同、范围、数量、长度和字段白名单重新规范化。
     *
     * @param mixed $value 请求值。
     * @return array<string,mixed>|null
     */
    public static function sanitize_review_baseline($value)
    {
        return is_array($value) ? $value : null;
    }

    /**
     * 追问上下文会在诊断层按固定合同重新构建。
     *
     * @param mixed $value 请求值。
     * @return array<string,mixed>|null
     */
    public static function sanitize_follow_up_context($value)
    {
        return is_array($value) ? $value : null;
    }

    /**
     * @param mixed $value 请求值。
     * @return array<int,array<string,string>>
     */
    public static function sanitize_follow_up_turns($value)
    {
        if (!is_array($value)) {
            return array();
        }

        $turns = array();
        foreach (array_slice($value, 0, 2) as $turn) {
            if (!is_array($turn)) {
                continue;
            }
            $question = isset($turn['question']) && is_string($turn['question']) ? $turn['question'] : '';
            $answer = isset($turn['answer']) && is_string($turn['answer']) ? $turn['answer'] : '';
            $turns[] = array(
                'question' => sanitize_textarea_field($question),
                'answer'   => sanitize_textarea_field($answer),
            );
        }
        return $turns;
    }

    /**
     * 注册 REST API 路由
     */
    public static function register_rest_routes()
    {
        Npcink_Toolbox_Rest_Route_Registry::clear();

        self::register_settings_routes();
        self::register_performance_routes();
        self::register_tools_routes();
        self::register_public_routes();
        self::register_domestic_routes();
        self::register_diagnostics_routes();

        Npcink_Toolbox_Rest_Route_Registry::register_all();

        do_action('npcink_site_toolbox_register_rest_routes');
    }

    private static function register_settings_routes()
    {
        Npcink_Toolbox_Rest_Route_Registry::add('/settings', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'rest_get_settings'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            ),
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array(__CLASS__, 'rest_save_settings'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
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

        Npcink_Toolbox_Rest_Route_Registry::add('/settings/schema', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'rest_get_schema'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            ),
        ), 'settings');

    }

    private static function register_performance_routes()
    {
        Npcink_Toolbox_Rest_Route_Registry::add('/performance/oss/test', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Oss', 'rest_test_connection'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
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
                    'description'       => '对象存储凭据 replace/clear 操作',
                    'default'           => array(),
                    'sanitize_callback' => function ($value) {
                        return is_array($value) ? $value : array();
                    },
                    'validate_callback' => function ($value) {
                        return is_array($value);
                    },
                ),
            ),
        ), 'performance');

        Npcink_Toolbox_Rest_Route_Registry::add('/performance/media/check', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Media_Health', 'ajax_check'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
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

        Npcink_Toolbox_Rest_Route_Registry::add('/performance/media/fix-alt', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Media_Health', 'ajax_fix_alt'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'post_id' => array(
                    'required'          => false,
                    'validate_callback' => function ($value) {
                        return empty($value) || (is_numeric($value) && $value > 0);
                    },
                ),
            ),
        ), 'performance');

        $webp_attachment_args = array(
            'attachment_ids' => array(
                'required'          => true,
                'type'              => 'array',
                'items'             => array('type' => 'integer', 'minimum' => 1),
                'description'       => '每批最多 5 个 JPEG 附件 ID',
                'validate_callback' => array('Npcink_Toolbox_Performance_Media_Health', 'validate_attachment_ids'),
                'sanitize_callback' => array('Npcink_Toolbox_Performance_Media_Health', 'sanitize_attachment_ids'),
            ),
        );

        Npcink_Toolbox_Rest_Route_Registry::add('/performance/media/webp/convert', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Media_Health', 'ajax_convert_webp'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            'args'                => $webp_attachment_args,
        ), 'performance');

        Npcink_Toolbox_Rest_Route_Registry::add('/performance/media/webp/restore', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Media_Health', 'ajax_restore_webp'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            'args'                => $webp_attachment_args,
        ), 'performance');

        Npcink_Toolbox_Rest_Route_Registry::add('/performance/seo/check', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Seo_Checker', 'ajax_check'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'post_id' => array(
                    'required'          => false,
                    'validate_callback' => function ($value) {
                        return empty($value) || (is_numeric($value) && $value > 0);
                    },
                ),
            ),
        ), 'performance');

        Npcink_Toolbox_Rest_Route_Registry::add('/performance/seo/fix-alt', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Seo_Checker', 'ajax_fix_alt'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
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

        Npcink_Toolbox_Rest_Route_Registry::add('/performance/db/stats', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Db_Clean', 'ajax_stats'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
        ), 'performance');

        Npcink_Toolbox_Rest_Route_Registry::add('/performance/db/preview', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Db_Clean', 'ajax_preview'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'type' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        $allowed = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'pending', 'trash');
                        return is_string($value) && in_array($value, $allowed, true);
                    },
                ),
            ),
        ), 'performance');

        Npcink_Toolbox_Rest_Route_Registry::add('/performance/db/clean', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Db_Clean', 'ajax_clean'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            'args'                => array(
                'type' => array(
                    'required'          => true,
                    'validate_callback' => function ($value) {
                        $allowed = array('revisions', 'drafts', 'spam', 'transients', 'optimize', 'pending', 'trash');
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

    private static function register_tools_routes()
    {
        Npcink_Toolbox_Rest_Route_Registry::add('/tools/categories', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array('Npcink_Toolbox_Interface_Category_Data', 'get_all_category_names'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
        ), 'tools');
    }

    private static function register_public_routes()
    {
        Npcink_Toolbox_Rest_Route_Registry::add('/public/search-log', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('Npcink_Toolbox_Performance_Search_Enhance', 'rest_log_search'),
            'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::public_nonce_rate_limited('search-log', 'npcink_site_toolbox_public_api', array('max_requests' => 30, 'time_window' => 60)),
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

    }

    private static function register_domestic_routes()
    {
        Npcink_Toolbox_Rest_Route_Registry::add('/domestic/environment/check', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array('Npcink_Toolbox_Domestic_Environment', 'rest_check'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            ),
        ), 'domestic');

        Npcink_Toolbox_Rest_Route_Registry::add('/domestic/environment/apply', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array('Npcink_Toolbox_Domestic_Environment', 'rest_apply'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
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
        Npcink_Toolbox_Rest_Route_Registry::add('/diagnostics/summary', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'rest_get_diagnostics_summary'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            ),
        ), 'diagnostics');

        Npcink_Toolbox_Rest_Route_Registry::add('/diagnostics/features', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'rest_get_feature_status'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            ),
        ), 'diagnostics');

        Npcink_Toolbox_Rest_Route_Registry::add('/diagnostics/support-report', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'rest_get_support_report'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
            ),
        ), 'diagnostics');

        Npcink_Toolbox_Rest_Route_Registry::add('/diagnostics/analyses', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array(__CLASS__, 'rest_analyze_support_report'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
                'args'                => array(
                    'problem' => array(
                        'required'          => false,
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'validate_callback' => function ($value) {
                            return is_string($value) && strlen($value) <= 8000;
                        },
                    ),
                ),
            ),
        ), 'diagnostics');

        Npcink_Toolbox_Rest_Route_Registry::add('/diagnostics/review-packs', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'rest_get_review_pack'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
                'args'                => array(
                    'scope' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'enum'              => array('performance', 'maintenance'),
                        'sanitize_callback' => 'sanitize_key',
                    ),
                ),
            ),
        ), 'diagnostics');

        Npcink_Toolbox_Rest_Route_Registry::add('/diagnostics/reviews', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array(__CLASS__, 'rest_analyze_review'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
                'args'                => array(
                    'scenario' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'enum'              => array('performance', 'maintenance', 'settings_risk', 'verification'),
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'problem' => array(
                        'required'          => false,
                        'type'              => 'string',
                        'default'           => '',
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'validate_callback' => function ($value) {
                            return is_string($value) && strlen($value) <= 2000;
                        },
                    ),
                    'changes' => array(
                        'required'          => false,
                        'type'              => 'array',
                        'default'           => array(),
                        'sanitize_callback' => array(__CLASS__, 'sanitize_review_changes'),
                        'validate_callback' => function ($value) {
                            return is_array($value) && count($value) <= 50;
                        },
                    ),
                    'baseline' => array(
                        'required'          => false,
                        'type'              => 'object',
                        'sanitize_callback' => array(__CLASS__, 'sanitize_review_baseline'),
                    ),
                ),
            ),
        ), 'diagnostics');

        Npcink_Toolbox_Rest_Route_Registry::add('/diagnostics/follow-ups', array(
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array(__CLASS__, 'rest_create_follow_up'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
                'args'                => array(
                    'scenario' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'enum'              => array('troubleshooting', 'performance', 'maintenance', 'settings_risk', 'verification'),
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'question' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'validate_callback' => function ($value) {
                            if (!is_string($value)) {
                                return false;
                            }
                            $length = function_exists('mb_strlen') ? mb_strlen($value, 'UTF-8') : strlen($value);
                            return $length > 0 && $length <= 1000;
                        },
                    ),
                    'context' => array(
                        'required'          => true,
                        'type'              => 'object',
                        'sanitize_callback' => array(__CLASS__, 'sanitize_follow_up_context'),
                    ),
                    'initial_analysis' => array(
                        'required'          => true,
                        'type'              => 'string',
                        'sanitize_callback' => 'sanitize_textarea_field',
                        'validate_callback' => function ($value) {
                            return is_string($value) && trim($value) !== '' && strlen($value) <= 24000;
                        },
                    ),
                    'turns' => array(
                        'required'          => false,
                        'type'              => 'array',
                        'default'           => array(),
                        'items'             => array(
                            'type'       => 'object',
                            'required'   => array('question', 'answer'),
                            'properties' => array(
                                'question' => array('type' => 'string'),
                                'answer'   => array('type' => 'string'),
                            ),
                        ),
                        'sanitize_callback' => array(__CLASS__, 'sanitize_follow_up_turns'),
                        'validate_callback' => function ($value) {
                            return is_array($value) && count($value) <= 2;
                        },
                    ),
                ),
            ),
        ), 'diagnostics');

        Npcink_Toolbox_Rest_Route_Registry::add('/search-health/summary', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array('Npcink_Toolbox_Search_Health', 'rest_get_summary'),
                'permission_callback' => Npcink_Toolbox_Rest_Route_Registry::admin_permission(),
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
        $config = Npcink_Toolbox_Config_Manager::get_merged_config();
        $value = self::get_config($config, $option);
        return $value;
    }

    public static function clear_config_cache()
    {
        Npcink_Toolbox_Config_Manager::clear_cache();
    }

    /**
     * 验证公开 REST API 端点的 nonce（防 CSRF/滥用）
     */
    public static function verify_public_nonce($request) {
        $nonce = $request->get_header('x-npcink-site-toolbox-nonce');
        if (empty($nonce)) {
            $nonce = $request->get_param('nonce');
        }
        return wp_verify_nonce($nonce, 'npcink_site_toolbox_public_api') !== false;
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
        $option = Npcink_Toolbox_Config_Manager::get_merged_config();
        if (empty($option)) {
            return;
        }

        $active_modules = false;
        if (function_exists('wp_cache_get')) {
            $active_modules = wp_cache_get('npcink_site_toolbox_active_modules', 'npcink_site_toolbox');
        }

        if ($active_modules === false) {
            $active_modules = Npcink_Toolbox_Module_Loader::get_active_modules($option);
            update_option(NPCINK_SITE_TOOLBOX_ACTIVE_MODULES, $active_modules);

            if (function_exists('wp_cache_set')) {
                wp_cache_set('npcink_site_toolbox_active_modules', $active_modules, 'npcink_site_toolbox', HOUR_IN_SECONDS);
            }
        }

        foreach ($active_modules as $module_id) {
            Npcink_Toolbox_Module_Loader::load_module($module_id, $option);
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
