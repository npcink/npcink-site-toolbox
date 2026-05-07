<?php
/*
Plugin Name: 链接统计
Plugin URI: https://ds17.cn/3154.html
Description: 统计链接被点击的次数，并在链接旁边显示计数。
Version: 1.0
Author: 大神博客
Author URI: https://ds17.cn/3154.html
License: GPLv2 or later
License URI: https://ds17.cn/3154.html
*/

function link_counter_install() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'link_counter';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        link_url VARCHAR(255) NOT NULL,
        visit_count mediumint(9) NOT NULL DEFAULT '0',
        PRIMARY KEY (id),
        UNIQUE KEY link_url (link_url)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}
register_activation_hook(__FILE__, 'link_counter_install');

function update_link_visit_count($link_url) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'link_counter';

    $wpdb->query($wpdb->prepare("INSERT INTO $table_name (link_url, visit_count) VALUES (%s, 1) ON DUPLICATE KEY UPDATE visit_count = visit_count + 1", $link_url));
}

function display_link_visit_count($link_url) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'link_counter';

    $visit_count = $wpdb->get_var($wpdb->prepare("SELECT visit_count FROM $table_name WHERE link_url = %s", $link_url));

    if ($visit_count) {
        return '已访问' . $visit_count . '次';
    } else {
        return '已访问0次';
    }
}

function add_visit_count_to_link($content) {
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'));
    libxml_clear_errors();

    $links = $dom->getElementsByTagName('a');

    foreach ($links as $link) {
        $link_url = $link->getAttribute('href');
        $visit_count = display_link_visit_count($link_url);
        $link->nodeValue .= ' ' . $visit_count;
    }

    return $dom->saveHTML();
}
add_filter('the_content', 'add_visit_count_to_link', 99);

function add_link_counter_script() {
    wp_enqueue_script('link-counter', plugins_url('/link-counter.js', __FILE__), array('jquery'), '1.0', true);
    wp_localize_script('link-counter', 'linkCounter', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('link_counter_nonce'),
    ));
}
add_action('wp_enqueue_scripts', 'add_link_counter_script');

function link_counter_ajax_callback() {
    // Nonce 验证
    check_ajax_referer('link_counter_nonce', 'nonce');

    $link_url = isset($_POST['link_url']) ? esc_url_raw(wp_unslash($_POST['link_url'])) : '';
    if (empty($link_url)) {
        wp_die('Invalid link URL');
    }
    update_link_visit_count($link_url);
    wp_die();
}
add_action('wp_ajax_update_link_visit_count', 'link_counter_ajax_callback');
add_action('wp_ajax_nopriv_update_link_visit_count', 'link_counter_ajax_callback');