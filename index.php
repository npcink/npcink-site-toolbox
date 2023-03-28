<?php //沉默是金

//定义 REST API 端点（Endpoint）
add_action('rest_api_init', 'mytheme_register_rest_endpoints');

function mytheme_register_rest_endpoints()
{
    // Get theme options
    register_rest_route('carbon-fields/v1', 'h5-options', array(
        'methods' => 'GET',
        'callback' => 'mytheme_get_theme_options',
        //权限控制
        //'permission_callback' => function () {
        //    return current_user_can('manage_options');
        //},
    ));

}

//返回选项值
function mytheme_get_theme_options($request)
{
    $fields = [
        'comm_h5_index_tone',
        'comm_h5_index_tone_cat',
        'comm_h5_index_category',
        'comm_h5_single_contact_title',
        'comm_h5_single_contact_one_title',
        'comm_h5_single_contact_one_content',
        'comm_h5_single_contact_two_title',
        'comm_h5_single_contact_two_content',
        'comm_h5_singel_featured_link',
        'comm_h5_singel_featured_logo',
        'comm_h5_singel_featured_msg',
    ];

    $options = [];
    foreach ($fields as $field) {
        $options[$field] = carbon_get_theme_option($field);
    }
    return $options;

}
