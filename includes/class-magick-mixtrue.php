<?php
//核心插件类。

class Magick_Mixtrue
{
    /**
     * 负责维护和注册所有电源挂钩的加载器
     *插件。
     *
     * @since    1.0.0
     * @access   protected
     * @var      Plugin_Name_Loader    $loader   维护并注册插件的所有钩子。
     */
    protected $loader;

    /**
     * 此插件的唯一标识符。
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $plugin_name    用于唯一标识此插件的字符串。
     */
    protected $plugin_name;

    /**
     * 插件的当前版本。
     *
     * @since    1.0.0
     * @access   protected
     * @var      string    $version   插件的当前版本。
     */
    protected $version;

    /**
     *定义插件的核心功能。
     */
    public function __construct()
    {
        if (defined('MAGICK_MIXTURE_VERSION')) {
            //有的话，拿到值
            $this->version = MAGICK_MIXTURE_VERSION;
        } else {
            //没有的话，设置默认插件版本号值
            $this->version = '1.0.3';
        }
        $this->plugin_name = 'magick-mixtrue';

        $this->load_dependencies(); //加载此插件所需的依赖项
        $this->define_admin_hooks(); //注册与后台功能相关的所有挂钩
        //$this->define_public_hooks(); //注册与前台功能相关的所有挂钩

    }

    /**
     *加载此插件所需的依赖项。
     *
     *包括组成插件的以下文件：
     *
     *-_Loader。编排插件的挂钩。
     *-_i18n。定义国际化功能。
     *-管理。定义管理区域的所有挂钩。
     *-公共。定义站点公共端的所有挂钩。
     *
     *创建一个将用于注册钩子的加载器实例
     *使用WordPress。
     *
     * @since    1.0.0
     * @access   private
     */
    //私有的，只有本类内部可以使用
    private function load_dependencies()
    {

        /**
         * 负责编排
         *核心插件。
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-magick-mixtrue-loader.php';



        /**
         * 负责定义后台中发生的所有操作的类。
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-magick-mixtrue-admin.php';

        /**
         * 负责定义面向公众的所有行为的类
         *现场一侧。
         */
        //require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-magick-mixtrue-public.php';

        /**
         * 公共工具类
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-magick-mixtrue-tool.php';

        $this->loader = new Magick_Mixtrue_Loader();
    }

    /**
     * 注册与后台功能相关的所有挂钩
     *插件的。
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new MaMi_Admin($this->get_plugin_name(), $this->get_version());


        //01 要向其添加回调的操作的名称。
        //02 调用操作时要运行的回调。
        //03 用于指定与特定操作关联的函数的执行顺序
        //04 函数接受的参数数


    }

    /**
     * 注册与面向公共功能相关的所有挂钩
     *插件的。
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Magick_Mixtrue_Public($this->get_plugin_name(), $this->get_version());
    }

    /**
     * 运行加载程序以使用WordPress执行所有钩子。
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
        //对js文件进行module接入
        add_filter('script_loader_tag', array(__CLASS__, 'refund_type_script'), 10, 2);
    }

    /**
     * 用于在上下文中唯一标识它的插件的名称
     *WordPress和定义国际化功能。
     *
     * @since     1.0.0
     * @return    string    The name of the plugin.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * 对用插件编排钩子的类的引用。
     *
     * @since     1.0.0
     * @return    Magick_Mixtrue_Loader    编排插件的挂钩。
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * 检索插件的版本号。
     *
     * @since     1.0.0
     * @return    string    插件的版本号。
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * 对js文件进行module接入
     */
    public static function refund_type_script($tag, $handle)
    {
        // 在这里判断需要添加 type 属性的 JS 文件，比如文件名包含 xxx.js
        if (strpos($tag, 'index.js') !== false) {
            // 在 script 标签中添加 type 属性
            $tag = str_replace('<script', '<script type="module"', $tag);
        }
        return $tag;
    }
}
