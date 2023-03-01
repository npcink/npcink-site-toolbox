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
class Magick_Mixtrue_Public
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $magick_mixtrue    The ID of this plugin.
     */
    private $magick_mixtrue;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private $version;

    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $magick_mixtrue       The name of the plugin.
     * @param      string    $version    The version of this plugin.
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
        //加载表情包
        require_once plugin_dir_path(__FILE__) . 'partials/class-mm-display.php';
        //自定义的一为登录页
        require_once plugin_dir_path(__FILE__) . 'partials/class-mm-login.php';
    }
    public function run()
    {
        //加载前台表情
        Magick_Mixtrue_Display::run();

        //加载登录页
        Magick_Mixtrue_Login::run();
    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in magick_mixtrue_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The magick_mixtrue_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        //wp_enqueue_style($this->magick_mixtrue, plugin_dir_url(__FILE__) . 'css/magick-mixtrue-public.css', array(), $this->version, 'all');

    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {

        /**
         * This function is provided for demonstration purposes only.
         *
         * An instance of this class should be passed to the run() function
         * defined in magick_mixtrue_Loader as all of the hooks are defined
         * in that particular class.
         *
         * The magick_mixtrue_Loader will then create the relationship
         * between the defined hooks and the functions defined in this
         * class.
         */

        //wp_enqueue_script($this->magick_mixtrue, plugin_dir_url(__FILE__) . 'js/magick-mixtrue-public.js', array('jquery'), $this->version, true);

    }

}
