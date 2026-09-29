<?php
// 如果直接访问此文件，请中止。
defined('ABSPATH') || exit;

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Npcink_Site_Toolbox
 * @subpackage Npcink_Site_Toolbox/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    Npcink_Site_Toolbox
 * @subpackage Npcink_Site_Toolbox/public
 * @author     Your Name <email@example.com>
 */
class Npcink_Toolbox_Public
{

    /**
     * The ID of this plugin.
     *
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     */
    public function __construct($plugin_name, $version)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->version = $version;
        $this->load();
        $this->run();
    }
    public function load()
    {
    }
    public function run()
    {
        //加载公共样式
        add_action('wp_enqueue_scripts', array(__CLASS__, 'public_css'));

      

        
    }

    //添加公共样式
    public static function public_css()
    {
        // mami-public.css 只服务「添加最后更新时间」模块的 .npcink-last-updated，
        // 未启用该模块时不再向全站输出这个请求
        $page_config = Npcink_Toolbox_Helpers::get_config('page', 'function', array());
        if (!is_array($page_config) || empty($page_config['add_last_update'])) {
            return;
        }

        //准备地址
        $url_css = plugin_dir_url(__FILE__) . 'css/mami-public.css';
        wp_enqueue_style(
            NPCINK_SITE_TOOLBOX_NAME . '_mami-public',
            $url_css,
            array(),
            NPCINK_SITE_TOOLBOX_VERSION,
            'all'
        );
    }

    
}
