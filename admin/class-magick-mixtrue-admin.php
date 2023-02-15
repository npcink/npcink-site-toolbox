<?php
function testttt()
{
    wp_enqueue_style('111', plugin_dir_url(__FILE__) . 'css/demo.css', array(), '1.1', 'all');
}
//add_action('admin_enqueue_scripts', 'testttt');

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
class Magick_Mixtrue_Admin
{

    /**
     * 此插件的ID。
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    此插件的ID。
     */
    private $plugin_name;

    /**
     * 此插件的版本。
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version   此插件的当前版本。
     */
    private $version;

    /**
     * 初始化类并设置其财产。
     *
     * @since    1.0.0
     * @param      string    $plugin_name       此插件的名称。
     * @param      string    $version    此插件的版本。
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->load(); //加载所需的依赖项
        $this->run(); //跑起来

    }

    /**
     *
     * 加载一些文件吧
     */
    public function load()
    {
        require_once plugin_dir_path(__FILE__) . 'partials/magick-mixtrue-admin-census.php';
    }

    /**
     * 启动
     */
    public function run()
    {
        //实例化一下，会自动跑起来
        $census = new Magick_Mixtrue_Admin_Census();

    }

    /**
     * 注册管理区域的样式表。
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * 此功能仅用于演示。
         *
         *此类的实例应传递给run（）函数
         *在Plugin_Name_Loader中定义，因为定义了所有钩子
         *在那个特定的班级里。
         *
         *然后Plugin_Name_Loader将创建关系
         *在定义的钩子和在此定义的函数之间
         *类。
         */

        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/magick-mixtrue-admin.css', array(), $this->version, 'all');

    }

    /**
     * 注册管理区域的JavaScript。
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * 此功能仅用于演示。
         *
         *此类的实例应传递给run（）函数
         *在Plugin_Name_Loader中定义，因为定义了所有钩子
         *在那个特定的班级里。
         *
         *然后Plugin_Name_Loader将创建关系
         *在定义的钩子和在此定义的函数之间
         *类。
         */

        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/magick-mixtrue-admin.js', array('jquery'), $this->version, false);

    }

}
