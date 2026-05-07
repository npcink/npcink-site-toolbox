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
     *
     * 加载一些文件吧
     */
    public function load()
    {
        //获取选项
        $option = get_option(MAGICK_MIXTURE_OPTION);
        //选项为空，不执行下拉代码
        if (empty($option)) {
            return;
        }

        //优化设置
        require_once plugin_dir_path(__FILE__) . 'partials/optimize/index.php';
        MaBox_Optimize::run();

        //功能设置
        require_once plugin_dir_path(__FILE__) . 'partials/function/index.php';
        MaBox_Function::run();

        //h5设置
        require_once plugin_dir_path(__FILE__) . 'partials/h5.php';
        MaBox_H5::run();

        //登录页
        require_once plugin_dir_path(__FILE__) . 'partials/login/index.php';
        Npcink_Login::run();

        //页面设置
        require_once plugin_dir_path(__FILE__) . 'partials/page/index.php';
        Npcink_Page::run();

        //短代码设置
        require_once plugin_dir_path(__FILE__) . 'partials/shortcode/index.php';
        MaBox_ShortCode::run();

        //页面模版设置
        require_once plugin_dir_path(__FILE__) . 'partials/template/index.php';
        Npcink_Template::run();
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
            'administrator',            // 哪种类型的用户可以看到此菜单项
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
        //准备默认样式
        echo '<div class="wrap"> <h2>';

        //准备菜单标题
        //echo esc_html(get_admin_page_title());
        //准备节点
        echo '</h2><div id="root"></div>';


        // $value = get_option(self::$option);
        // echo "<h2>设置选项的值</h2>";
        // $jsonString = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        // if ($jsonString !== "false") {
        //     echo '<pre>' . $jsonString . '</pre>';
        // } else {
        //     echo '<pre>暂无对象值</pre>';
        // }
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



        $MaBox_array = array(
            'option' => get_option(MAGICK_MIXTURE_OPTION), //传递选项
            'cat_arr' => self::get_cat_data(), //分类信息
            'single_arr' => self::get_single_data(), //文章信息
            'url_site' => get_site_url(), //当前首页网址
            'ajaxurl' => admin_url('admin-ajax.php'), // AJAX 地址
            'nonce' => wp_create_nonce('mabox_save_nonce'), // 安全令牌

        );
        wp_localize_script($name, 'dataLocal', $MaBox_array); //传给vite项目


    }


    /**
     * 整理文章数据
     */
    public static function get_single_data()
    {
        $posts = get_posts();

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
        // 1. 权限检查
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['error' => '权限不足，仅管理员可操作'], 403);
        }

        // 2. Nonce 验证
        check_ajax_referer('mabox_save_nonce', 'nonce');

        // 3. 接收并解析数据
        if (empty($_POST['object_data'])) {
            wp_send_json_error(['error' => '未接收到有效的设置数据'], 400);
        }

        $raw_data = wp_unslash($_POST['object_data']);
        $object = json_decode($raw_data, true); // 解码为关联数组

        if (!is_array($object) || empty($object)) {
            wp_send_json_error(['error' => '设置数据格式无效'], 400);
        }

        // 4. 备份旧数据（失败时回滚）
        $old_option_backup = get_option(MAGICK_MIXTURE_OPTION);

        // 5. 保存新选项
        $result = update_option(MAGICK_MIXTURE_OPTION, $object);

        if ($result === false) {
            // 保存失败，恢复旧数据
            update_option(MAGICK_MIXTURE_OPTION, $old_option_backup);
            error_log('[MaBox] Failed to update option, rolled back to previous state');
            wp_send_json_error(['error' => '保存失败，已恢复为之前的设置'], 500);
        }

        wp_send_json_success(['message' => '保存成功']);
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

        $old_option_backup = get_option(MAGICK_MIXTURE_OPTION);
        $result = update_option(MAGICK_MIXTURE_OPTION, $body);

        if ($result === false) {
            update_option(MAGICK_MIXTURE_OPTION, $old_option_backup);
            error_log('[MaBox] REST API: Failed to update option, rolled back');
            return new \WP_Error('rest_save_failed', '保存失败，已恢复为之前的设置', array('status' => 500));
        }

        return rest_ensure_response([
            'success' => true,
            'message' => '保存成功',
        ]);
    }

    /**
     * REST API: 获取设置
     */
    public static function rest_get_settings($request)
    {
        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', '权限不足', array('status' => 403));
        }

        $settings = get_option(MAGICK_MIXTURE_OPTION, array());
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
    }

    /**
     * 提供选项
     */
    public static function get_seting($option)
    {
        //拿到选项值
        $config = get_option(MAGICK_MIXTURE_OPTION);
        $value =  self::get_config($config, $option);
        return $value;
    }
    /**
     * 从对象中获取属性值
     *
     * @param object $config 对象
     * @param string $property 从对象中获取的属性名
     * @param string $defaultValue 默认值（可选）
     * @return mixed 属性值或默认值
     */
    public static function get_config($config, $property, $defaultValue = false)
    {
        if (is_array($config) && isset($config[$property]) && !empty($config[$property])) {
            return $config[$property];
        }
        if (is_object($config) && property_exists($config, $property) && !empty($config->$property)) {
            return $config->$property;
        }
        return $defaultValue;
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
