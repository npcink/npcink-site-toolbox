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
    public static $option = "MaBox_object_option_f";
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

        //优化设置
        require_once plugin_dir_path(__FILE__) . 'partials/optimize.php';
        MaBox_Optimize::run();

        //功能设置
        require_once plugin_dir_path(__FILE__) . 'partials/function.php';
        MaBox_Function::run();

        //h5设置
        require_once plugin_dir_path(__FILE__) . 'partials/h5.php';
        MaBox_H5::run();

        //登录页
        require_once plugin_dir_path(__FILE__) . 'partials/login.php';
        Npcink_Login::run();

        //页面设置
        require_once plugin_dir_path(__FILE__) . 'partials/page.php';
        Npcink_Page::run();
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
        add_action('wp_ajax_save_object_option', array(__CLASS__, 'save_object_option_callback'));
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
            'option' => get_option(self::$option), //传递选项
            'cat_arr' => self::get_cat_data(), //分类信息
            'single_arr' => self::get_single_data(), //文章信息
            'url_site'=> get_site_url(),//当前首页网址

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
     * 添加选项接口
     */


    public static  function save_object_option_callback()
    {
        global $wpdb;
        // 获取通过 Ajax POST 请求传递的对象数据
        $object_data = isset($_POST['object_data']) ? sanitize_text_field($_POST['object_data']) : null;

        // 将 JSON 字符串解析为 PHP 对象
        $object = json_decode(stripslashes($object_data));

        if (empty($object)) {
            return wp_send_json_error([
                'error' => '设置选项为空',
            ], 403);
        }


        // 保存设置选项
        $result =  update_option(self::$option, $object);
        if ($result !== false) {
            // 发送成功响应
            return wp_send_json_success(['message' => '设置选项已保存', 'msg' => $object,]);
        } else {
            // 选项未改变会返回false
            return wp_send_json_error(['error' => '没有保存，没有设置新选项', 'reason' => $wpdb->last_error, 'msg' => $result, 'msg2' => $object], 500);
        }
    }

    /**
     * 提供选项
     */
    public static function get_seting($option)
    {
        //拿到选项值
        $config = get_option(self::$option);
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
        /**
         * 是否是对象
         * 对象中是否有此键名
         * 在对象中的此值是否为空
         */
        if (is_object($config) && property_exists($config, $property) && !empty($config->$property)) {
            return $config->$property;
        } else {
            //不存在则输出默认值
            return $defaultValue;
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
