<?php



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

        // 添加Ajax请求处理函数
        add_action('wp_ajax_save_option_wmt', array(__CLASS__, 'save_option_wmt_callback'));

        // 设置导入导出
        add_action('wp_ajax_export_settings', array(__CLASS__, 'export_settings_callback'));
        add_action('wp_ajax_import_settings', array(__CLASS__, 'import_settings_callback'));

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

        if (isset($_GET['mabox_debug'])) {
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
            'option' => MaBox_Config_Manager::get_merged_config(),
            'cat_arr' => self::get_cat_data(),
            'single_arr' => self::get_single_data(),
            'url_site' => get_site_url(),
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('mabox_save_nonce'),

        );
        wp_localize_script($name, 'dataLocal', $MaBox_array); //传给vite项目


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
            $category_obj->value = $category->cat_ID;
            $category_list[] = $category_obj;
        }
        return $category_list;
    }




    /**
     * 添加选项接口 (AJAX)
     */
    public static function save_option_wmt_callback()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => '权限不足，仅管理员可操作'], 403);
        }

        check_ajax_referer('mabox_save_nonce', 'nonce');

        if (empty($_POST['object_data'])) {
            wp_send_json_error(['error' => '未接收到有效的设置数据'], 400);
        }

        $raw_data = wp_unslash($_POST['object_data']);
        $object = json_decode($raw_data, true);

        if (!is_array($object) || empty($object)) {
            wp_send_json_error(['error' => '设置数据格式无效'], 400);
        }

        $old_option_backup = MaBox_Config_Manager::get_merged_config();

        $result = MaBox_Config_Manager::save_full_config($object);

        if (!$result['success']) {
            MaBox_Config_Manager::save_full_config($old_option_backup);
            error_log('[MaBox] Failed to update option, rolled back to previous state');
            wp_send_json_error(['error' => '保存失败，已恢复为之前的设置'], 500);
        }

        $active_modules = MaBox_Module_Loader::get_active_modules($object);
        update_option(MAGICK_TOOLBOX_ACTIVE_MODULES, $active_modules);

        if (function_exists('wp_cache_set')) {
            wp_cache_set('mabox_active_modules', $active_modules, 'mabox', HOUR_IN_SECONDS);
        }

        self::clear_config_cache();

        wp_send_json_success(['message' => '保存成功']);
    }

    /**
     * 导出设置
     */
    public static function export_settings_callback()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('权限不足', 403);
        }

        $settings = MaBox_Config_Manager::export_config();
        $json = json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="mabox-settings-' . date('Y-m-d') . '.json"');
        header('Content-Length: ' . strlen($json));
        echo $json;
        wp_die();
    }

    /**
     * 导入设置
     */
    public static function import_settings_callback()
    {
        if (!current_user_can('manage_options')) {
            wp_send_json_error('权限不足', 403);
        }

        if (empty($_FILES['settings_file'])) {
            wp_send_json_error('未上传文件', 400);
        }

        $file = $_FILES['settings_file'];
        if ($file['type'] !== 'application/json' && pathinfo($file['name'], PATHINFO_EXTENSION) !== 'json') {
            wp_send_json_error('仅支持 JSON 文件', 400);
        }

        $json_content = file_get_contents($file['tmp_name']);
        $settings = json_decode($json_content, true);

        if (!is_array($settings)) {
            wp_send_json_error('JSON 格式无效', 400);
        }

        $result = MaBox_Config_Manager::import_config($settings);

        if (!$result['success']) {
            wp_send_json_error($result['error'], 500);
        }

        $active_modules = MaBox_Module_Loader::get_active_modules($settings);
        update_option(MAGICK_TOOLBOX_ACTIVE_MODULES, $active_modules);

        if (function_exists('wp_cache_set')) {
            wp_cache_set('mabox_active_modules', $active_modules, 'mabox', HOUR_IN_SECONDS);
        }

        self::clear_config_cache();

        wp_send_json_success('导入成功');
    }

    /**
     * REST API: 保存设置
     */
    public static function rest_save_settings($request)
    {
        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', '权限不足', array('status' => 403));
        }

        $body = $request->get_json_params();
        if (empty($body) || !is_array($body)) {
            return new \WP_Error('rest_invalid_data', '设置数据格式无效', array('status' => 400));
        }

        $old_option_backup = MaBox_Config_Manager::get_merged_config();
        $result = MaBox_Config_Manager::save_full_config($body);

        if (!$result['success']) {
            MaBox_Config_Manager::save_full_config($old_option_backup);
            error_log('[MaBox] REST API: Failed to update option, rolled back');
            return new \WP_Error('rest_save_failed', '保存失败，已恢复为之前的设置', array('status' => 500));
        }

        $active_modules = MaBox_Module_Loader::get_active_modules($body);
        update_option(MAGICK_TOOLBOX_ACTIVE_MODULES, $active_modules);

        if (function_exists('wp_cache_set')) {
            wp_cache_set('mabox_active_modules', $active_modules, 'mabox', HOUR_IN_SECONDS);
        }

        self::clear_config_cache();

        return rest_ensure_response([
            'success' => true,
            'message' => '保存成功',
        ]);
    }

    public static function rest_get_settings($request)
    {
        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', '权限不足', array('status' => 403));
        }

        $settings = MaBox_Config_Manager::get_merged_config();
        return rest_ensure_response([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * 注册 REST API 路由
     */
    public static function register_rest_routes()
    {
        // 原有设置路由
        register_rest_route('mabox/v1', '/settings', array(
            array(
                'methods'             => \WP_REST_Server::READABLE,
                'callback'            => array(__CLASS__, 'rest_get_settings'),
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            ),
            array(
                'methods'             => \WP_REST_Server::CREATABLE,
                'callback'            => array(__CLASS__, 'rest_save_settings'),
                'permission_callback' => function () {
                    return current_user_can('manage_options');
                },
            ),
        ));

        // ===== Performance: Media Health =====
        register_rest_route('mabox/v1', '/performance/media/check', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Media_Health', 'ajax_check'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));
        register_rest_route('mabox/v1', '/performance/media/fix-alt', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Media_Health', 'ajax_fix_alt'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        // ===== Performance: DB Clean =====
        register_rest_route('mabox/v1', '/performance/db/stats', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array('MaBox_Performance_Db_Clean', 'ajax_stats'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));
        register_rest_route('mabox/v1', '/performance/db/clean', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Db_Clean', 'ajax_clean'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        // ===== Performance: SEO Checker =====
        register_rest_route('mabox/v1', '/performance/seo/check', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Seo_Checker', 'ajax_check'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));
        register_rest_route('mabox/v1', '/performance/seo/fix-alt', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Seo_Checker', 'ajax_fix_alt'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        // ===== Domestic: Baidu Push =====
        register_rest_route('mabox/v1', '/domestic/baidu/push', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Domestic_Baidu_Push', 'ajax_batch_push'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        // ===== Page: Batch Replace =====
        register_rest_route('mabox/v1', '/page/batch-replace', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Page_Batch_Replace', 'manual_replace'),
            'permission_callback' => function () {
                return current_user_can('edit_posts');
            },
        ));

        // ===== Tools: Database Tables =====
        register_rest_route('mabox/v1', '/tools/tables', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array('MaBox_Download_SQL_Table', 'get_all_table_names'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));
        register_rest_route('mabox/v1', '/tools/table-data', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Download_SQL_Table', 'get_table_data'),
            'permission_callback' => function () {
                return current_user_can('manage_options');
            },
        ));

        // ===== Tools: Categories =====
        register_rest_route('mabox/v1', '/tools/categories', array(
            'methods'             => \WP_REST_Server::READABLE,
            'callback'            => array('MaBox_Interface_Category_Data', 'get_all_category_names'),
            'permission_callback' => '__return_true', // 公开分类数据，用于前端展示
        ));

        // ===== Public: Search Log =====
        register_rest_route('mabox/v1', '/public/search-log', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Performance_Search_Enhance', 'ajax_log_search'),
            'permission_callback' => array(__CLASS__, 'verify_public_nonce'),
        ));

        // ===== Public: Anti-Crawler Verify =====
        register_rest_route('mabox/v1', '/public/anti-crawler/verify', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Page_Anti_Crawler', 'ajax_verify'),
            'permission_callback' => array(__CLASS__, 'verify_public_nonce'),
        ));

        // ===== Public: Article Rating =====
        register_rest_route('mabox/v1', '/public/rating', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_Page_Article_Rating', 'handle_rating'),
            'permission_callback' => array(__CLASS__, 'verify_public_nonce'),
        ));

        // ===== Public: WX Unlock Verify =====
        register_rest_route('mabox/v1', '/public/wx-unlock/verify', array(
            'methods'             => \WP_REST_Server::CREATABLE,
            'callback'            => array('MaBox_ShortCode_Wx_Unlock', 'ajax_verify'),
            'permission_callback' => array(__CLASS__, 'verify_public_nonce'),
        ));

        // 触发模块路由注册钩子（统一路由注册模式）
        do_action('mabox_register_rest_routes');
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
        MaBox_Config_Manager::migrate();

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
