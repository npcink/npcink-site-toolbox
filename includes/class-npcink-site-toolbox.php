<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;
//核心插件类。

class Npcink_Site_Toolbox
{
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
        if (defined('NPCINK_SITE_TOOLBOX_VERSION')) {
            //有的话，拿到值
            $this->version = NPCINK_SITE_TOOLBOX_VERSION;
        } else {
            //没有的话，设置默认插件版本号值
            $this->version = '1.0.3';
        }
        $this->plugin_name = defined('NPCINK_SITE_TOOLBOX_NAME')
            ? NPCINK_SITE_TOOLBOX_NAME
            : 'npcink-site-toolbox';

        $this->load_dependencies(); //加载此插件所需的依赖项
        $this->define_admin_hooks(); //注册与后台功能相关的所有挂钩
        $this->define_public_hooks(); //注册与前台功能相关的所有挂钩

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
        require_once plugin_dir_path(__FILE__) . 'class-npcink-toolbox-helpers.php';

        require_once plugin_dir_path(__FILE__) . 'class-npcink-toolbox-rate-limiter.php';

        require_once plugin_dir_path(__FILE__) . 'class-npcink-toolbox-audit-logger.php';

        require_once plugin_dir_path(__FILE__) . 'class-npcink-toolbox-site-health.php';

        require_once plugin_dir_path(__FILE__) . 'class-npcink-toolbox-tool.php';

        require_once plugin_dir_path(__FILE__) . 'class-npcink-toolbox-config-schema.php';

        require_once plugin_dir_path(__FILE__) . 'class-npcink-toolbox-config-manager.php';

        require_once plugin_dir_path(__FILE__) . '../admin/modules/loader.php';

        require_once plugin_dir_path(__FILE__) . '../admin/class-npcink-toolbox-admin.php';

        require_once plugin_dir_path(__FILE__) . '../public/class-npcink-toolbox-public.php';
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

        $plugin_admin = new Npcink_Toolbox_Admin($this->get_plugin_name(), $this->get_version());

        // 站点健康检测
        if (class_exists('Npcink_Toolbox_Site_Health')) {
            Npcink_Toolbox_Site_Health::run();
        }


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

        $plugin_public = new Npcink_Toolbox_Public($this->get_plugin_name(), $this->get_version());
    }

    /**
     * 运行加载程序以使用WordPress执行所有钩子。
     *
     * @since    1.0.0
     */
    public function run()
    {
        add_action('init', array($this, 'load_textdomain'), 0);
        add_filter('block_categories_all', array('Npcink_Toolbox_Block_Patterns', 'add_block_category'));
        add_action('init', array('Npcink_Toolbox_Block_Patterns', 'register'));
        add_action('init', array('Npcink_Toolbox_Github_Project', 'register_block'));
        add_action('init', array('Npcink_Toolbox_Site_Stats', 'register_block'));

        //对js文件进行module接入
        add_filter('script_loader_tag', array(__CLASS__, 'refund_type_script'), 10, 2);
    }

    /**
     * Load bundled translations after WordPress has established the request locale.
     */
    public function load_textdomain()
    {
        $locale = determine_locale();
        if (!preg_match('/^[A-Za-z0-9_@.-]+$/', $locale)) {
            return;
        }

        $mofile = dirname(__DIR__) . '/languages/npcink-site-toolbox-' . $locale . '.mo';
        if (is_readable($mofile)) {
            load_textdomain('npcink-site-toolbox', $mofile);
        }
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
        // 仅匹配本插件的 index.js（通过 handle 名称精确匹配）
        if (strpos($handle, 'npcink-site-toolbox') !== false && strpos($tag, 'index.js') !== false) {
            // 在 script 标签中添加 type 属性
            $tag = str_replace('<script', '<script type="module"', $tag);
        }
        return $tag;
    }
}
