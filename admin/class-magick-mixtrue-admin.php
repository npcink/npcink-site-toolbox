<?php
use Carbon_Fields\Container;
use Carbon_Fields\Field;

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

        //加载主题选项
        add_action('carbon_fields_register_fields', array($this, 'load_admin_settings'));

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
        //优化设置
        require_once plugin_dir_path(__FILE__) . 'partials/option-optimize.php';
    }

    /**
     * 启动
     */
    public function run()
    {

        //实例化一下，会自动跑起来
        $census = new Magick_Mixtrue_Admin_Census();
        //优化
        Magick_Mixtrue_Optimize::run();

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
        //wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/demo.css', array(), $this->version, 'all');

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
//加载echarts 用于图标绘制
        // wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/echarts_v5.4.0.js', array(), $this->version, false);
        //wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/magick-mixtrue-admin.js', array('jquery'), $this->version, false);

    }

    /**
     * 设置选项组
     */
    public function load_admin_settings()
    {
        Container::make('theme_options', __('魔法合剂'))
            ->set_icon('dashicons-carrot')
            ->add_tab(__('优化'), array(
                Field::make('separator', 'cmma_optimize_filter', __('筛选')),
                Field::make('checkbox', 'cmma_filter_single_user', __('文章菜单添加作者筛选项'))
                    ->set_option_value('yes'),
                Field::make('checkbox', 'cmma_filter_single_time', __('文章和媒体菜单添加时间筛选项'))
                    ->set_option_value('yes')
                    ->set_help_text("媒体菜单需为列表布局"),
                Field::make('separator', 'cmma_optimize_show_id', __('显示ID')),
                Field::make('checkbox', 'cmma_single_show_id', __('文章菜单显示文章ID'))
                    ->set_option_value('yes'),
                Field::make('text', 'crb_first_name', __('First Name')),
                Field::make('text', 'crb_last_name', __('Last Name')),
                Field::make('text', 'crb_position', __('Position')),
                Field::make('html', 'crb_information_text')
                    ->set_html('<h2>Lorem ipsum</h2><p>Quisque mattis ligula.</p>'),
            ))
            ->add_tab(__('安全'), array(
                Field::make('text', 'crb_email', __('Notification Email')),
                Field::make('text', 'crb_phone', __('Phone Number')),
            ))
            ->add_tab(__('其他'), array(
                Field::make('separator', 'crb_separator', __('评论区')),
                Field::make('checkbox', 'cmma_show_owo', __('评论区添加OWO表情包'))
                    ->set_option_value('yes'),
                Field::make('text', 'crb_emails', __('Notification Emails')),
                Field::make('text', 'crb_phones', __('Phone Numbers')),
            ));
    }

}
