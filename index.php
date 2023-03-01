<?php //沉默是金

//载入所需VUE框架
function magick_load_vue()
{
    wp_enqueue_script(
        MAGICK_MIXTURE_NAME,
        plugin_dir_url(__FILE__) . 'public/js/style-click-particle.js',
        array(),
        MAGICK_MIXTURE_VERSION,
        true
    );
}
add_action('wp_enqueue_scripts', 'magick_load_vue', 3);

//载入所需VUE框架
function magick_load_vues()
{
    wp_enqueue_script(
        MAGICK_MIXTURE_NAME,
        plugin_dir_url(__FILE__) . 'public/js/OwO.min.js',
        array(),
        MAGICK_MIXTURE_VERSION,
        false
    );

    wp_enqueue_style(
        MAGICK_MIXTURE_NAME,
        plugin_dir_url(__FILE__) . 'public/css/OwO.min.css',
        array(),
        MAGICK_MIXTURE_VERSION,
        'all'
    );

}
add_action('wp_enqueue_scripts', 'magick_load_vues', 6);
