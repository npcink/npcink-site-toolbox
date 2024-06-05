<?php

/**
 * The public-facing functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    magick_mixtrue
 * @subpackage magick_mixtrue/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    magick_mixtrue
 * @subpackage magick_mixtrue/public
 * @author     Your Name <email@example.com>
 */
class MaBox_Public
{

    /**
     * The ID of this plugin.
     *
     */
    private $magick_mixtrue;

    /**
     * The version of this plugin.
     *
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     */
    public function __construct($magick_mixtrue, $version)
    {

        $this->magick_mixtrue = $magick_mixtrue;
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

        //添加分享按钮
        require_once plugin_dir_path(__FILE__) . 'add_share.php';
        Npcink_Public_Add_Share::run();
    }

    //添加公共样式
    public static function public_css()
    {
        //准备地址
        $url_css = plugin_dir_url(__FILE__) . 'css/mami-public.css';
        wp_enqueue_style(
            MAGICK_MIXTURE_NAME . '_mami-public',
            $url_css,
            array(),
            MAGICK_MIXTURE_VERSION,
            'all'
        );
    }
}
