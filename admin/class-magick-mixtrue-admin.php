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
class MaMi_Admin
{

    /**
     * 选项
     */
    public static $option = "mami_object_option_f";
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

        //权限设置
        require_once plugin_dir_path(__FILE__) . 'partials/auxiliary.php';

        //h5设置
        require_once plugin_dir_path(__FILE__) . 'partials/h5.php';
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

        //对js文件进行module接入
        add_filter('script_loader_tag', array(__CLASS__, 'refund_type_script'), 10, 2);

        //加载主题选项
        add_action('carbon_fields_register_fields', array(__CLASS__, 'load_admin_settings'));

        // 添加Ajax请求处理函数
        add_action('wp_ajax_save_object_option', array(__CLASS__, 'save_object_option_callback'));

        /**
         * 优化
         */
        MaMi_Optimize::run();


        /**
         * 权限
         */
        MaMi_Auxiliary::run();

        /**
         * H5
         */
        MaMi_H5::run();
    }




    /**
     * 添加菜单
     */
    public static function add_menu()
    {
        //添加插件菜单

        add_plugins_page(
            '魔法合剂设置选项',             // 要在此页面的浏览器窗口中显示的标题。
            '魔法合剂',            // 要为此菜单项显示的文本
            'administrator',            // 哪种类型的用户可以看到此菜单项
            'mami_config',    // The unique ID - that is, the slug - for this menu item 
            array(__CLASS__, 'mami_display')   // 呈现此菜单的页面时要调用的函数的名称
        );
    }

    /**
     * 菜单回调
     */
    public static function mami_display()
    {
        //准备默认样式
        echo '<div class="wrap"> <h2>';

        //准备菜单标题
        echo esc_html(get_admin_page_title());
        //准备节点
        echo '</h2><div id="root"></div>';
       
        $value = get_option(self::$option);
        echo "<h2>设置选项的值</h2>";
        $jsonString = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonString !== "false") {
            echo '<pre>' . $jsonString . '</pre>';
        } else {
            echo '<pre>暂无对象值</pre>';
        }
    }

    /**
     * 加载JS和CSS资源
     */
    public static  function load_admin_script($hook)
    {
        $ver = self::$version;
        $name = self::$plugin_name;

        //是否是指定页面
        if ('plugins_page_mami_config' != $hook) {
            return;
        }

        //准备地址
        $index_css = plugin_dir_url(__DIR__) . 'vite/admin/dist/index.css';
        $index_js = plugin_dir_url(__DIR__) . 'vite/admin/dist/index.js';

        wp_enqueue_style($name, $index_css, array(), $ver, false);
        wp_enqueue_script($name, $index_js, array(), $ver, true);



        $mami_array = array(
            'option' => get_option(self::$option), //传递选项
            'cat_arr' => self::get_cat_data(), //分类信息
        );
        wp_localize_script($name, 'dataLocal', $mami_array); //传给vite项目


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

        // 获取通过 Ajax POST 请求传递的对象数据
        $object_data = $_POST['object_data'];

        // 将 JSON 字符串解析为 PHP 对象
        $object = json_decode(stripslashes($object_data));

        // 保存设置选项
        update_option(self::$option, $object);

        // 发送成功响应
        $response = array(
            'message' => '设置选项已保存！',
            'object' => $object,
        );



        // 使用 wp_send_json 函数发送 JSON 响应，避免汉字转义
        wp_send_json($response, 200, JSON_UNESCAPED_UNICODE);
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


    /**
     * 设置选项组
     */
    public static function load_admin_settings()
    {
        Container::make('theme_options', __('魔法合剂'))
            ->set_icon('dashicons-carrot')
            ->set_page_menu_position(200.6)
            /**
             * 优化
             */
            ->add_tab(__('优化'), array(
                /**
                 * 站点
                 */
                Field::make('separator', 'cmma_optimize_site_msg', __('站点')),
                Field::make('checkbox', 'cmma_opt_site_transferred', __('禁止网站title中的 “-” 被转义'))
                    ->set_option_value('yes'),

                Field::make('checkbox', 'cmma_opt_site_content_add_tag', __('文章关键词自动添加内链链接代码'))
                    ->set_option_value('yes')
                    ->set_help_text('
                    撰写文章，内容中添加1个以上标签文本，发文章时添加标签，
                    <a href="https://www.npc.ink/15286.html?=magick-plugin" target="_blank">详细介绍</a>
                    '),

                /**
                 * 筛选
                 */
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

                /**
                 * 优化 - 媒体
                 */
                Field::make('separator', 'cmma_opt_medium_title', __('媒体')),
                Field::make('checkbox', 'cmma_medium_img_add_alt', __('自动给图片添加Alt标签'))
                    ->set_option_value('yes')
                    ->set_help_text("标签值为当前文章名 - 网站名"),

                Field::make('checkbox', 'cmma_medium_ban_auto_size', __('禁用自动生成的图片尺寸'))
                    ->set_option_value('yes')
                    ->set_help_text("禁用自动生成的图片尺寸、禁用缩放尺寸、禁用其他图片尺寸"),

                Field::make('checkbox', 'cmma_medium_add_svg', __('添加媒体库 SVG 图标支持'))
                    ->set_option_value('yes'),

                Field::make('select', 'cmma_opt_medium_rename', __('媒体图片上传自动重命名'))
                    ->set_options(array(
                        'no' => '关闭',
                        'math' => '数字重命名',
                        'md5' => 'MD5重命名',
                    ))
                    ->set_default_value('no')
                    ->set_help_text("数字重命名类似：<code>2023030303095446</code>，MD5重命名类似<code>a9193c211c6c991528f29fb7acfee31a</code>"),

                //优化 - 评论
                Field::make('separator', 'cmma_optimize_commont', __('评论')),
                Field::make('select', 'cmma_opt_com_time', __('两次评论间需指定间隔'))
                    ->set_options(array(
                        'no' => '关闭',
                        'yes' => '开启',
                    ))
                    ->set_default_value('no')
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
                        'no' => '关闭',
                        'yes' => '开启',
                    ))
                    ->set_default_value('no'),

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

                /**
                 * 禁用
                 */
                Field::make('separator', 'cmma_opt_ban_svg', __('禁用'))
                    ->set_help_text("<b style='color:red;'>若您不知道会发生什么，还请慎重</b>"),

                Field::make('checkbox', 'cmma_opt_ban_update', __('禁用更新'))
                    ->set_option_value('yes')
                    ->set_help_text("WordPress、主题和插件不再提示更新"),

            ))

            /**
             * 安全
             */
            ->add_tab(__('安全'), array(
                Field::make('separator', 'cmma_safe_login', __('登录')),
                Field::make('checkbox', 'cmma_safe_login_errors', __('替换默认账号密码报错信息，会影响验证码错误提示！'))
                    ->set_option_value('yes')
                    ->set_help_text("默认报错信息会透露用户名错误还是密码错误，统一信息后，可改善此情况"),

                Field::make('checkbox', 'cmma_safe_comment_style_name', __('修改评论中的用户名'))
                    ->set_option_value('yes')
                    ->set_help_text("默认的评论样式中，会包含管理员登录ID，修改后，可改善此情况"),

                Field::make('checkbox', 'cmma_safe_head_version', __('从RSS源和网站中删除WordPress版本'))
                    ->set_option_value('yes')
                    ->set_help_text("如果您无法保持您的WordPres版本为最新，推荐开启"),

            ))

            /**
             * 控制
             */
            ->add_tab(__('控制'), array(
                Field::make('separator', 'comm_control_login_msg', __('登录控制')),

                Field::make('checkbox', 'cmma_control_login_dim_content_img', __('未登录模糊文章图片内容'))
                    ->set_option_value('yes'),

            ))

            /**
             * 其他
             */
            ->add_tab(__('其他'), array(

                Field::make('separator', 'comm_separator_fun_switch', __('功能开关')),

                Field::make('checkbox', 'cmma_fun_census_single', __('文章统计'))
                    ->set_option_value('yes')
                    ->set_width(20)
                    ->set_help_text("开启后显示在仪表盘下"),
                Field::make('checkbox', 'cmma_fun_census_shop', __('B2商城统计'))
                    ->set_option_value('yes')
                    ->set_width(20)
                    ->set_help_text('开启后显示在仪表盘下,<a href="https://7b2.com/shop/35736.html?=Npcink" target="_blank">了解B2主题</a>'),

                Field::make('select', 'cmma_ban_search_keywords', __('屏蔽恶意关键词搜索'))
                    ->set_options(array(
                        'no' => '关闭',
                        'yes' => '开启',
                    ))
                    ->set_default_value('no')
                    ->set_help_text("禁止某些词在本站搜索"),

                Field::make('textarea', 'cmma_ban_search_keywords_content', __('输入您的关键词，以“回车键”分隔'))

                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_ban_search_keywords',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),

                /**
                 * 页面特效
                 */
                Field::make('separator', 'crb_separator_page', __('页面特效')),
                Field::make('checkbox', 'cmma_page_show_particle', __('添加粒子特效'))
                    ->set_option_value('yes')
                    ->set_help_text("考虑到性能以及操作问题，移动端不加载此特效"),

                Field::make('checkbox', 'cmma_page_label_cloud', __('添加圆角彩色背景标签云'))
                    ->set_option_value('yes')
                    ->set_help_text("可在小工具中添加标签云，前台即可看到效果"),

                Field::make('separator', 'crb_separator', __('评论区')),
                Field::make('checkbox', 'cmma_show_owo', __('评论区添加OWO表情包'))
                    ->set_option_value('yes'),

                /**
                 * 登录页
                 */
                Field::make('separator', 'crb_separator_login', __('登录页')),

                Field::make('checkbox', 'cmma_opt_com_logo_home', __('登录页LOGO改为首页链接'))
                    ->set_option_value('yes')
                    ->set_width(20),

                Field::make('checkbox', 'cmma_opt_rem_sign_lang', __('移除登录页面语言选择框'))
                    ->set_option_value('yes')
                    ->set_width(20),

                Field::make('select', 'cmma_abt_style_login', __('更改为自定义登录页'))
                    ->set_options(array(
                        'no' => '关闭',
                        'yes' => '开启',
                    ))
                    ->set_default_value('no'),

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

                /**
                 * 登录验证码
                 */
                Field::make('select', 'cmma_login_verify', __('登录验证码'))
                    ->set_options(array(
                        'no' => '关闭',
                        'math_results' => '数学验证码',
                        'random_mixing' => '随机混合验证码',
                        'tx_vcode' => '腾讯验证码-功能未验证',
                    ))
                    ->set_default_value('no')
                    ->set_help_text('

                    '),
                //数学验证码
                Field::make('html', 'cmma_login_verify_msg_math')
                    ->set_html('<h2>需输入指定数学运算的结果才可登录</h2><p>

                </p>')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_login_verify',
                            'value' => 'math_results',
                            'compare' => '=',
                        ),
                    )),
                //随机混合验证码
                Field::make('html', 'cmma_login_verify_msg_random')
                    ->set_html('<h2>需输入指定的文本才可登录</h2><p>

                </p>')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_login_verify',
                            'value' => 'random_mixing',
                            'compare' => '=',
                        ),
                    )),
                //腾讯验证码

                Field::make('html', 'cmma_login_verify_msg_tx')
                    ->set_html('<h2>接入腾讯防水墙，给网站登录加上图形验证功能</h2><p>
                    点击这里注册
                    <a href="https://cloud.tencent.com/act/cps/redirect?redirect=10717&cps_key=c4baec70ed3f429838d86e2682a46f63" target="_blank">
                    <b">T-Sec 天御 验证码</b>
                    </a>
                    ，使用方法可参考 <a href="https://www.iowen.cn/wordpress-access-to-tencent-captcha-service/" target="_blank">
                    <b">使用教程</b>
                    </a>
                    </p>')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_login_verify',
                            'value' => 'tx_vcode',
                            'compare' => '=',
                        ),
                    )),

                Field::make('text', 'cmma_login_verify_tx_id', 'App ID')
                    ->set_attribute('type', 'number')
                    ->set_width(50)
                    ->set_help_text('貌似随便填也能用')
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_login_verify',
                            'value' => 'tx_vcode',
                            'compare' => '=',
                        ),
                    )),

                Field::make('text', 'cmma_login_verify_tx_key', ' App Secret Key')
                    ->set_attribute('type', 'password')
                    ->set_help_text('貌似随便填也能用')
                    ->set_width(50)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'cmma_login_verify',
                            'value' => 'tx_vcode',
                            'compare' => '=',
                        ),
                    )),

                Field::make('time', 'crb_event_start', 'Event Start')
                    ->set_attribute('placeholder', 'Time of event start'),
            ))

            /**
             * H5
             */

            ->add_tab(__('H5'), array(

                Field::make('separator', 'comm_h5_intro_msg', __('H5介绍'))
                    ->set_help_text("使用WordPress提供的Rest API，可通过H5单页来展示有趣的内容。详情可见<a href='https://www.npc.ink/276746.html' target='_blank'>H5介绍</a>"),
                Field::make('select', 'comm_h5_switch', __('启用H5选项'))
                    ->set_options(array(
                        'no' => '关闭',
                        'yes' => '开启',
                    ))
                    ->set_default_value('no'),

                Field::make('separator', 'comm_h5_index_tone_msg', __('H5首页 - 特写'))
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),

                Field::make('association', 'comm_h5_index_tone', __('幻灯片展示的特色文章'))
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    ))
                    ->set_types(array(

                        array(
                            'type' => 'post',
                            'post_type' => 'post',
                        ),

                    )),
                Field::make('select', 'comm_h5_index_tone_cat', __('幻灯片-查看全部'))
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    ))
                    ->set_options(self::get_categoriess()),

                Field::make('separator', 'comm_h5_index_more_msg', __('H5首页 - 更多'))
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),
                Field::make('select', 'comm_h5_index_category', __('首页展示的文章分类'))
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    ))
                    ->set_options(self::get_categoriess()),

                Field::make('separator', 'comm_h5_single_contact', __('H5内容页 - 联系'))
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),
                Field::make('text', 'comm_h5_single_contact_title', __('联系标题'))
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),
                Field::make('text', 'comm_h5_single_contact_one_title', __('内容标题-1'))
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    ))
                    ->set_width(25),
                Field::make('rich_text', 'comm_h5_single_contact_one_content', __('内容标题-1'))
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    ))
                    ->set_width(25),
                Field::make('text', 'comm_h5_single_contact_two_title', __('内容标题-2'))
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    ))
                    ->set_width(25),
                Field::make('rich_text', 'comm_h5_single_contact_two_content', __('内容标题-2'))
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    ))
                    ->set_width(25),
                Field::make('separator', 'comm_h5_singel_featured', __('H5内容页 - 品牌'))
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    )),
                Field::make('text', 'comm_h5_singel_featured_link', __('跳转链接'))
                    ->set_attribute('type', 'url')
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    ))
                    ->set_width(33),
                Field::make('image', 'comm_h5_singel_featured_logo', __('品牌LOGO'))
                    ->set_value_type('url')
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    ))
                    ->set_width(33),
                Field::make('text', 'comm_h5_singel_featured_msg', __('一段介绍'))
                    ->set_visible_in_rest_api($visible = true)
                    ->set_conditional_logic(array(
                        array(
                            'field' => 'comm_h5_switch',
                            'value' => 'yes',
                            'compare' => '=',
                        ),
                    ))
                    ->set_width(33),

            )); //结束选项组

    } //结束选项

    // 获取分类列表作为选项
    public static function get_categoriess()
    {
        $categories = get_categories(array(
            'orderby' => 'name',
            'order' => 'ASC',
            'hide_empty' => false,
        ));

        $options = array();
        foreach ($categories as $category) {
            $options[$category->term_id] = $category->name;
        }
        return $options;
    }
}
