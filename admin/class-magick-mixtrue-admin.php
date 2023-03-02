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
        //安全设置
        require_once plugin_dir_path(__FILE__) . 'partials/option-safe.php';
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
        //安全
        Magick_Mixtrue_Safe::run();

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
                Field::make('checkbox', 'cmma_single_show_id', __('各个列表显示链接ID'))
                    ->set_option_value('yes')
                    ->set_help_text("支持 文章、页面、链接、多媒体、评论、分类、标签、用户 等"),

                Field::make('separator', 'cmma_opt_', __('媒体')),

                //优化 - 评论
                Field::make('separator', 'cmma_optimize_commont', __('评论')),
                Field::make('select', 'cmma_opt_com_time', __('两次评论间需指定间隔'))
                    ->set_options(array(
                        'yes' => '开启',
                        'no' => '关闭',
                    ))
                    ->set_help_text("避免短时间内重复灌水评论，对管理员无效"),

                Field::make('text', 'cmma_opt_com_times', '时间间隔（秒）')
                    ->set_attribute('type', 'number')
                    ->set_attribute('placeholder', '指定时间后才能再次评论')
                    ->set_width(40)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_opt_com_time',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),

                Field::make('select', 'cmma_opt_com_number', __('指定最小和最大评论字数'))
                    ->set_options(array(
                        'yes' => '开启',
                        'no' => '关闭',
                    )),

                Field::make('text', 'cmma_opt_com_num_min', '最少字数（个）')
                    ->set_required(true)
                    ->set_attribute('type', 'number')
                    ->set_attribute('placeholder', '评论所需最少字数')
                    ->set_width(33)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_opt_com_number',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),
                Field::make('text', 'cmma_opt_com_num_max', '最多字数（个）')
                    ->set_required(true)
                    ->set_attribute('type', 'number')
                    ->set_attribute('placeholder', '评论所需最多字数')
                    ->set_width(33)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_opt_com_number',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),

                Field::make('checkbox', 'cmma_opt_com_language', __('禁止纯英文和纯日文评论'))
                    ->set_option_value('yes'),

                Field::make('checkbox', 'cmma_opt_com_once', __('单篇文章只允许评论一次'))
                    ->set_option_value('yes')
                    ->set_help_text("管理员不受此影响"),

                Field::make('separator', 'cmma_opt_page', __('页面')),
                Field::make('checkbox', 'cmma_opt_com_logo_home', __('登录页LOGO改为首页链接'))
                    ->set_option_value('yes'),

                Field::make('separator', 'cmma_opt_remove', __('移除'))
                    ->set_help_text("<b style='color:red;'>若您不知道会发生什么，还请慎重</b>"),
                Field::make('checkbox', 'cmma_opt_rem_sign_lang', __('移除登录页面语言选择框'))
                    ->set_option_value('yes'),

                Field::make('html', 'crb_information_text')
                    ->set_html('<h2>Lorem ipsum</h2><p>Quisque mattis ligula.</p>'),
            ))
            ->add_tab(__('安全'), array(
                Field::make('separator', 'cmma_safe_login', __('登录')),
                Field::make('checkbox', 'cmma_safe_login_errors', __('替换默认账号密码报错信息'))
                    ->set_option_value('yes')
                    ->set_help_text("默认报错信息会透露用户名错误还是密码错误，统一信息后，可改善此情况"),

                Field::make('checkbox', 'cmma_safe_comment_style_name', __('修改评论中的用户名'))
                    ->set_option_value('yes')
                    ->set_help_text("默认的评论样式中，会包含管理员登录ID，修改后，可改善此情况"),

                Field::make('checkbox', 'cmma_safe_head_version', __('从RSS源和网站中删除WordPress版本'))
                    ->set_option_value('yes')
                    ->set_help_text("如果您无法保持您的WordPres版本为最新，推荐开启"),

            ))
            ->add_tab(__('其他'), array(
                Field::make('separator', 'crb_separator_page', __('页面特效')),
                Field::make('checkbox', 'cmma_page_show_particle', __('页面添加粒子特效'))
                    ->set_option_value('yes'),

                Field::make('separator', 'crb_separator', __('评论区')),
                Field::make('checkbox', 'cmma_show_owo', __('评论区添加OWO表情包'))
                    ->set_option_value('yes'),
                Field::make('separator', 'crb_separator_login', __('登录页')),
                Field::make('select', 'cmma_abt_style_login', __('更改为自定义登录页'))
                    ->set_options(array(
                        'yes' => '开启',
                        'no' => '关闭',
                    ))
                    ->set_width(40),
                Field::make('color', 'cmma_opt_login_bgcolor_left', '背景色（左下角）')
                    ->set_palette(array('#181d23', '#960a9b', '#0000FF'))
                    ->set_width(20)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_abt_style_login',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),
                Field::make('color', 'cmma_opt_login_bgcolor_right', '背景色（右上角）')
                    ->set_palette(array('#3c3e42', '#ac1394', '#0000FF'))
                    ->set_width(20)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_abt_style_login',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),

                Field::make('text', 'cmma_opt_login_logo_size', '标志尺寸（像素px）')
                    ->set_default_value('84')
                    ->set_attribute('type', 'number')
                    ->set_attribute('placeholder', 'LOGO的尺寸')
                    ->set_help_text("默认尺寸是84px")
                    ->set_width(20)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_abt_style_login',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),

                Field::make('image', 'cmma_opt_login_logo', '顶部标志')
                    ->set_type(array('image'))
                    ->set_value_type('url')
                    ->set_help_text("推荐是圆形")
                    ->set_width(50)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_abt_style_login',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),

                Field::make('image', 'cmma_opt_login_bg_left', '左边文字背景图')
                    ->set_type(array('image'))
                    ->set_value_type('url')
                    ->set_help_text("推荐尺寸是900X600，安全边距100像素")
                    ->set_width(50)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_abt_style_login',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),

                Field::make('text', 'crb_emails', __('Notification Emails')),
                Field::make('text', 'crb_phones', __('Phone Numbers')),

                Field::make('time', 'crb_event_start', 'Event Start')
                    ->set_attribute('placeholder', 'Time of event start'),
            ));
    }

}
